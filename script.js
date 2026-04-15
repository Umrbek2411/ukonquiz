const API = 'http://localhost/ukonquiz/api';

const TOTAL_TIME = 300;

let S = {
    user: null, token: null,
    pendEmail:'', pendName:'', pendPhone:'', pendPurpose:'register',
    subject: null, subjectId: null, questions:[], curQ:0, score:0,
    chosen:[], totalTimeLeft: TOTAL_TIME, timerID:null, startTime:null
};

(function initCanvas() {
    const canvas = document.getElementById('circuit-canvas');
    const ctx = canvas.getContext('2d');
    let W, H, nodes = [], pulses = [];
    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    window.addEventListener('resize', () => { resize(); buildNodes(); });
    resize();
    function buildNodes() {
        nodes = [];
        const count = Math.floor((W * H) / 22000);
        for (let i = 0; i < count; i++) nodes.push({ x: Math.random()*W, y: Math.random()*H });
    }
    buildNodes();
    function spawnPulse() {
        if (nodes.length < 2) return;
        const a = nodes[Math.floor(Math.random()*nodes.length)];
        const b = nodes[Math.floor(Math.random()*nodes.length)];
        if (a === b) return;
        pulses.push({ ax:a.x, ay:a.y, bx:b.x, by:b.y, t:0, speed:0.008+Math.random()*0.012 });
    }
    setInterval(spawnPulse, 300);
    function draw() {
        ctx.clearRect(0, 0, W, H);
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i+1; j < nodes.length; j++) {
                const dx = nodes[i].x-nodes[j].x, dy = nodes[i].y-nodes[j].y;
                const d = Math.sqrt(dx*dx+dy*dy);
                if (d < 160) {
                    ctx.beginPath(); ctx.strokeStyle='rgba(255,80,0,0.18)'; ctx.lineWidth=0.8;
                    ctx.moveTo(nodes[i].x,nodes[i].y); ctx.lineTo(nodes[j].x,nodes[j].y); ctx.stroke();
                }
            }
        }
        nodes.forEach(n => { ctx.beginPath(); ctx.arc(n.x,n.y,2.5,0,Math.PI*2); ctx.fillStyle='rgba(255,140,0,0.55)'; ctx.fill(); });
        pulses = pulses.filter(p => p.t <= 1);
        pulses.forEach(p => {
            p.t += p.speed;
            const x = p.ax+(p.bx-p.ax)*p.t, y = p.ay+(p.by-p.ay)*p.t;
            const grad = ctx.createRadialGradient(x,y,0,x,y,10);
            grad.addColorStop(0, p.t<0.5?'#ff6b00':'#ffd700'); grad.addColorStop(1,'transparent');
            ctx.beginPath(); ctx.arc(x,y,10,0,Math.PI*2); ctx.fillStyle=grad; ctx.fill();
            ctx.beginPath(); ctx.arc(x,y,3,0,Math.PI*2); ctx.fillStyle='#fff'; ctx.fill();
        });
        requestAnimationFrame(draw);
    }
    draw();
})();

window.addEventListener('load', () => {
    const saved = localStorage.getItem('ukon_session');
    if (saved) { try { const p=JSON.parse(saved); S.user=p.user; S.token=p.token; } catch(e) {} }
    setupOTP();
    loadSubjects();
});

// ===================== FANLARNI BAZADAN YUKLASH =====================
async function loadSubjects() {
    const res = await fetch(`${API}/admin_subjects.php?action=list`).then(r=>r.json()).catch(()=>null);
    if (!res?.success) return;

    const container = document.querySelector('.subjects');
    if (!container) return;

    container.innerHTML = res.subjects.map(s => `
        <div class="subject-card" onclick="pickSubject('${s.id}', '${s.name}', '${s.icon}', this)">
            <span class="subject-icon">${s.icon}</span>
            <div class="subject-title">${s.name}</div>
            <div class="subject-desc">${s.description || ''}</div>
            <span class="subject-count">${s.question_count} ta savol</span>
        </div>
    `).join('');
}

function showStage(id) {
    document.getElementById('landing').classList.remove('active');
    document.querySelectorAll('.stage,.quiz-stage').forEach(el => el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    if (id === 'stage-login') checkSavedOnLogin();
}

function backLanding() {
    document.querySelectorAll('.stage').forEach(el => el.classList.remove('active'));
    document.getElementById('landing').classList.add('active');
}

function checkSavedOnLogin() {
    if (!S.user) return;
    document.getElementById('saved-section').style.display = 'block';
    document.getElementById('login-form-section').style.display = 'none';
    document.getElementById('saved-name').textContent = S.user.full_name;
    document.getElementById('saved-email-txt').textContent = S.user.email;
    document.getElementById('saved-avatar').textContent = S.user.full_name[0].toUpperCase();
}

function clearSaved() {
    S.user=null; S.token=null; localStorage.removeItem('ukon_session');
    document.getElementById('saved-section').style.display='none';
    document.getElementById('login-form-section').style.display='block';
}

function continueSession() { goToSubject(); }

async function doRegister() {
    const name=v('reg-name'), phone=v('reg-phone'), email=v('reg-email');
    clearErr('reg-err');
    if (!name||name.split(' ').length<2) return showErr('reg-err',"To'liq ism familiyangizni kiriting");
    if (!phone||phone.length<7) return showErr('reg-err',"Telefon raqamini kiriting");
    if (!email||!email.includes('@')) return showErr('reg-err',"Gmail manzilini to'g'ri kiriting");
    setBusy('reg-btn',true);
    const res = await post('register.php',{full_name:name,phone,email});
    setBusy('reg-btn',false);
    if (!res) return showErr('reg-err',"Server bilan ulanishda xato");
    if (!res.success) return showErr('reg-err',res.message);
    S.pendEmail=email; S.pendName=name; S.pendPhone=phone; S.pendPurpose='register';
    document.getElementById('verify-email-show').textContent='📧 '+email;
    showStage('stage-verify'); clearOTP(); startResendTimer();
    notify('📧','Kod yuborildi!','Gmail spam papkasini ham tekshiring');
}

async function doLogin() {
    const email=v('login-email');
    clearErr('login-err');
    if (!email||!email.includes('@')) return showErr('login-err',"Gmail manzilini kiriting");
    setBusy('login-btn',true);
    const res = await post('login.php',{email});
    setBusy('login-btn',false);
    if (!res) return showErr('login-err',"Server bilan ulanishda xato");
    if (!res.success) return showErr('login-err',res.message);
    S.pendEmail=email; S.pendPurpose='login';
    document.getElementById('verify-email-show').textContent='📧 '+email;
    showStage('stage-verify'); clearOTP(); startResendTimer();
    notify('📧','Kirish kodi yuborildi!','Gmailingizni tekshiring');
}

async function doVerify() {
    const code=['o1','o2','o3','o4','o5','o6'].map(id=>document.getElementById(id).value).join('');
    clearErr('otp-err');
    if (code.length<6) return showErr('otp-err',"6 xonali kodni to'liq kiriting");
    setBusy('verify-btn',true);
    const res = await post('verify.php',{email:S.pendEmail,code,purpose:S.pendPurpose});
    setBusy('verify-btn',false);
    if (!res) return showErr('otp-err','Server bilan ulanishda xato');
    if (!res.success) {
        showErr('otp-err',res.message);
        document.querySelectorAll('.otp-box').forEach(el=>{el.style.borderColor='#ef4444';setTimeout(()=>el.style.borderColor='',1200);});
        return;
    }
    S.user=res.user; S.token=res.token;
    localStorage.setItem('ukon_session',JSON.stringify({user:res.user,token:res.token}));
    notify('✅','Tasdiqlandi!','Xush kelibsiz, '+res.user.full_name+'!');
    goToSubject();
}

async function doResend(e) {
    e.preventDefault();
    const ep=S.pendPurpose==='login'?'login.php':'register.php';
    const body=S.pendPurpose==='login'?{email:S.pendEmail}:{full_name:S.pendName,phone:S.pendPhone,email:S.pendEmail};
    const res=await post(ep,body);
    if (res?.success) { notify('📧','Qayta yuborildi!','Gmailingizni tekshiring'); clearOTP(); startResendTimer(); }
    else notify('❌','Xato',res?.message||'Qayta yuborishda xato');
}

function setupOTP() {
    const boxes=document.querySelectorAll('.otp-box');
    boxes.forEach((b,i)=>{
        b.addEventListener('input',()=>{
            b.value=b.value.replace(/\D/g,'');
            if(b.value&&i<boxes.length-1) boxes[i+1].focus();
            if([...boxes].every(x=>x.value)) doVerify();
        });
        b.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!b.value&&i>0) boxes[i-1].focus();});
        b.addEventListener('paste',e=>{
            e.preventDefault();
            const t=e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
            boxes.forEach((x,j)=>x.value=t[j]||'');
            if(t.length===6) doVerify();
        });
    });
}

function clearOTP() {
    document.querySelectorAll('.otp-box').forEach(b=>{b.value='';b.style.borderColor='';});
    clearErr('otp-err');
    document.getElementById('o1').focus();
}

let resendInt;
function startResendTimer() {
    let s=60;
    const link=document.getElementById('resend-link'), timer=document.getElementById('resend-timer');
    link.style.display='none'; timer.style.display='inline'; timer.textContent=` (${s}s)`;
    clearInterval(resendInt);
    resendInt=setInterval(()=>{
        s--; timer.textContent=` (${s}s)`;
        if(s<=0){clearInterval(resendInt);link.style.display='inline';timer.style.display='none';}
    },1000);
}

function goToSubject() {
    document.querySelectorAll('.stage,.quiz-stage,.landing').forEach(el=>el.classList.remove('active'));
    document.getElementById('stage-subject').classList.add('active');
    document.getElementById('greet-text').textContent=`Salom, ${S.user.full_name.split(' ')[0]}! Qaysi fanda bilimingizni sinab ko'rasiz?`;
    loadSubjects();
}

function pickSubject(id, name, icon, el) {
    document.querySelectorAll('.subject-card').forEach(c=>c.classList.remove('sel'));
    el.classList.add('sel');
    S.subject = name;
    S.subjectId = id;
    const btn=document.getElementById('start-btn');
    btn.style.opacity='1'; btn.style.pointerEvents='auto';
    const badge=document.getElementById('quiz-badge');
    badge.textContent = icon + ' ' + name;
    badge.className = 'subject-badge';
}

// ===================== QUIZ =====================
async function startQuiz() {
    if (!S.subjectId) return;

    // Bazadan savollarni yuklash
    const res = await fetch(`${API}/get_questions.php?subject=${S.subjectId}`).then(r=>r.json()).catch(()=>null);

    if (!res?.success || !res.questions?.length) {
        notify('❌','Xato',"Bu fanda savollar yo'q. Admin savol qo'shsin!");
        return;
    }

    S.questions = res.questions.map(q => ({
        q: q.question,
        opts: [q.opt_a, q.opt_b, q.opt_c, q.opt_d],
        ans: parseInt(q.correct_ans)
    }));

    S.curQ=0; S.score=0; S.chosen=new Array(S.questions.length).fill(null);
    S.totalTimeLeft=TOTAL_TIME; S.startTime=Date.now();

    document.getElementById('stage-subject').classList.remove('active');
    document.getElementById('stage-quiz').classList.add('active');

    const badge = document.getElementById('quiz-badge');
    badge.textContent = S.subject;

    startGlobalTimer();
    loadQ();
}

function loadQ() {
    const q=S.questions[S.curQ], total=S.questions.length;
    document.getElementById('q-label').textContent=`SAVOL ${S.curQ+1}`;
    document.getElementById('q-text').textContent=q.q;
    document.getElementById('q-count').textContent=`${S.curQ+1} / ${total}`;
    document.getElementById('prog-fill').style.width=`${(S.curQ/total)*100}%`;
    const letters=['A','B','C','D'];
    document.getElementById('options-grid').innerHTML=q.opts.map((opt,i)=>`
        <div class="option ${S.chosen[S.curQ]===i?'selected':''}" onclick="chooseOpt(${i},this)">
            <div class="opt-letter">${letters[i]}</div>
            <div class="opt-text">${opt}</div>
        </div>`).join('');
    document.getElementById('next-btn').textContent=S.curQ===total-1?"Natijani ko'rish ✓":"Keyingi savol →";
}

function chooseOpt(idx, el) {
    S.chosen[S.curQ]=idx;
    document.querySelectorAll('.option').forEach(o=>o.classList.remove('selected'));
    el.classList.add('selected');
}

function nextQ() {
    const q=S.questions[S.curQ];
    const opts=document.querySelectorAll('.option');
    opts[q.ans].classList.add('correct');
    if (S.chosen[S.curQ]!==null && S.chosen[S.curQ]!==q.ans) opts[S.chosen[S.curQ]].classList.add('wrong');
    setTimeout(()=>{
        S.curQ++;
        if(S.curQ<S.questions.length) loadQ();
        else showResults();
    },800);
}

function startGlobalTimer() {
    clearInterval(S.timerID);
    updateTimer(S.totalTimeLeft);
    S.timerID=setInterval(()=>{
        S.totalTimeLeft--;
        updateTimer(S.totalTimeLeft);
        if(S.totalTimeLeft<=0){clearInterval(S.timerID);showResults();}
    },1000);
}

function updateTimer(t) {
    const num=document.getElementById('timer-num');
    const ring=document.getElementById('timer-ring');
    const min=Math.floor(t/60), sec=t%60;
    num.textContent=min>0?`${min}:${sec.toString().padStart(2,'0')}`:t;
    ring.style.strokeDashoffset=138.2*(1-t/TOTAL_TIME);
    num.parentElement.classList.remove('timer-warn');
    if(t<=30){num.style.color='#ef4444';ring.style.stroke='#ef4444';num.parentElement.classList.add('timer-warn');}
    else if(t<=60){num.style.color='#ffd700';ring.style.stroke='#ffd700';}
    else{num.style.color='#f5f5f5';ring.style.stroke='var(--orange)';}
}

async function showResults() {
    clearInterval(S.timerID);
    S.score=0;
    S.questions.forEach((q,i)=>{ if(S.chosen[i]===q.ans) S.score++; });
    const total=S.questions.length;
    const pct=Math.round(S.score/total*100);
    const sec=Math.round((Date.now()-S.startTime)/1000);
    document.getElementById('stage-quiz').classList.remove('active');
    document.getElementById('stage-results').classList.add('active');
    document.getElementById('r-correct').textContent=S.score;
    document.getElementById('r-wrong').textContent=total-S.score;
    document.getElementById('r-time').textContent=sec+'s';
    let medal='🥉',title="Davom eting!",sub="Yana bir marta urinib ko'ring.";
    if(pct>=90){medal='🏆';title="Zo'r natija!";sub="Mukammal bilimga egasiz!";}
    else if(pct>=70){medal='🥇';title="Ajoyib!";sub="Juda yaxshi natija ko'rsatdingiz!";}
    else if(pct>=50){medal='🥈';title="Yaxshi!";sub="Mashq qilsangiz yanada yaxshilanasiz.";}
    document.getElementById('r-medal').textContent=medal;
    document.getElementById('r-title').textContent=title;
    document.getElementById('r-sub').textContent=sub;
    setTimeout(()=>{
        const f=document.getElementById('ring-fill');
        f.style.transition='stroke-dashoffset 1.5s ease';
        f.style.strokeDashoffset=351.86*(1-pct/100);
    },100);
    let cur=0;
    const iv=setInterval(()=>{cur=Math.min(cur+2,pct);document.getElementById('r-pct').textContent=cur+'%';if(cur>=pct)clearInterval(iv);},18);
    if(S.token){await post('save_results.php',{token:S.token,subject:S.subject,score:S.score,total,time_spent:sec});}
}

function goSubjects() {
    clearInterval(S.timerID);
    document.getElementById('stage-results').classList.remove('active');
    document.getElementById('stage-subject').classList.add('active');
    document.querySelectorAll('.subject-card').forEach(c=>c.classList.remove('sel'));
    S.subject=null; S.subjectId=null;
    document.getElementById('start-btn').style.opacity='0.45';
    document.getElementById('start-btn').style.pointerEvents='none';
    loadSubjects();
}

function retryQuiz() {
    clearInterval(S.timerID);
    document.getElementById('stage-results').classList.remove('active');
    startQuiz();
}

function v(id){return document.getElementById(id).value.trim();}
function showErr(id,msg){const el=document.getElementById(id);el.style.display='block';el.textContent='⚠️ '+msg;}
function clearErr(id){document.getElementById(id).style.display='none';}
function setBusy(id,busy){
    const btn=document.getElementById(id);btn.disabled=busy;
    if(busy){btn.dataset.orig=btn.innerHTML;btn.innerHTML='<span class="spinner"></span>Yuklanmoqda...';}
    else{btn.innerHTML=btn.dataset.orig||btn.innerHTML;}
}
function notify(icon,title,msg){
    document.getElementById('notif-icon').textContent=icon;
    document.getElementById('notif-title').textContent=title;
    document.getElementById('notif-msg').textContent=msg;
    const el=document.getElementById('notif');
    el.classList.add('show');setTimeout(()=>el.classList.remove('show'),5000);
}
async function post(endpoint,data){
    try{
        const r=await fetch(`${API}/${endpoint}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        return await r.json();
    }catch(e){console.error(e);return null;}
}