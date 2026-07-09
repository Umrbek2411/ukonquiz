# UKON Quiz

**Test your knowledge** — an online quiz platform with timed tests in Math and Programming.

🔗 Demo: [ukonquiz.onrender.com](https://ukonquiz.onrender.com/)

---

## 📌 About the project

UKON Quiz is a web application that lets users take timed quizzes made of randomly selected questions from a chosen subject. The system uses passwordless authentication via email OTP (one-time code) verification, automatically calculates results, and sends the user a nicely formatted result email. The project also includes a fully functional admin panel for managing subjects, questions, and quiz settings.

## ✨ Key features

- **Passwordless registration/login** — sends a 6-digit OTP code via email (valid for 10 minutes)
- **Subject-based quizzes** — randomly selected questions per subject (4 options, one correct answer)
- **Configurable quiz settings** — admin can adjust the number of questions and quiz duration
- **Automatic result calculation** — percentage score, medal (🏆🥇🥈🥉), and performance rating
- **Email result delivery** — a styled HTML result email sent after each quiz
- **Admin panel** — manage subjects, questions, settings, and view statistics (username/password login)
- **Session-based authentication** — 30-day session via token

## 🛠️ Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 (vanilla, no framework) |
| Database | MySQL (via PDO) |
| Email | PHPMailer + Mailjet SMTP |
| Frontend | Vanilla HTML/CSS/JS |
| Containerization | Docker (php:8.2-apache) |
| Dependency management | Composer |

## 📂 Project structure

```
ukonquiz/
├── api/
│   ├── register.php          # Registration + OTP sending
│   ├── login.php              # Login + OTP sending
│   ├── verify.php             # OTP verification, session creation
│   ├── get_questions.php      # Fetch questions (by subject slug)
│   ├── get_questionns.php     # Fetch questions (settings-aware, by subject ID)
│   ├── save_results.php       # Save quiz results and send email
│   ├── admin_login.php        # Admin login
│   ├── admin_subjects.php     # Manage subjects (CRUD)
│   ├── admin_questions.php    # Manage questions (CRUD)
│   ├── admin_settings.php     # Configure quiz time/question count
│   └── admin_results.php      # Results list and statistics
├── config/
│   └── database.php           # PDO connection settings
├── includes/
│   └── mailer.php              # PHPMailer + Mailjet SMTP configuration
├── index.html                  # User interface (landing, login, quiz, results)
├── admin.html                   # Admin panel interface
├── script.js                    # Frontend logic (OTP flow, quiz flow, timer)
├── style.css                    # Glass-card style design
├── Dockerfile                   # PHP 8.2 + Apache container
├── composer.json                # PHPMailer dependency
├── robots.txt / sitemap.xml     # SEO configuration
└── Postman Collections/         # Ready-to-use API test requests
```

## 🗄️ Database structure (core tables)

Tables identified from the code:

- **users** — `id, full_name, phone, email, is_verified, last_login`
- **otp_codes** — `id, email, code, purpose, used, expires_at`
- **sessions** — `id, user_id, token, expires_at`
- **subjects** — `id, name, icon, description`
- **questions** — `id, subject_id, question, opt_a, opt_b, opt_c, opt_d, correct_ans`
- **quiz_results** — `id, user_id, subject, score, total, time_spent, taken_at`
- **settings** — `key_name, value` (e.g. `quiz_time`, `questions_count`)
- **admins** — `id, username, password` (bcrypt hash)

> ⚠️ No `.sql` schema file was found in the repository — a `CREATE TABLE` script should be prepared separately based on the structure above before deployment.

## 🚀 Setup and running

### Using Docker
```bash
docker build -t ukonquiz .
docker run -p 8080:80 ukonquiz
```

### Local setup (XAMPP/LAMP)
```bash
composer install
# Configure DB_HOST, DB_NAME, DB_USER, DB_PASS in config/database.php
# Create the ukonquiz_db database and the tables listed above in MySQL
```

### Important before deployment
1. Move the SMTP credentials in `includes/mailer.php` to a **`.env` file** and **rotate the Mailjet API key**
2. Update the database credentials in `config/database.php` for production
3. Add `.env` and `vendor/` (if installed via Composer) to `.gitignore`

## 📡 API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/register.php` | Registration, OTP sending |
| POST | `/api/login.php` | Login, OTP sending |
| POST | `/api/verify.php` | OTP verification, returns session token |
| GET | `/api/get_questionns.php?subject={id}` | Fetch questions by subject |
| POST | `/api/save_results.php` | Save quiz result |
| POST | `/api/admin_login.php` | Admin login |
| GET/POST | `/api/admin_subjects.php` | CRUD for subjects |
| GET/POST | `/api/admin_questions.php` | CRUD for questions |
| GET/POST | `/api/admin_settings.php` | Update quiz settings |
| GET | `/api/admin_results.php?action=list\|stats` | Results and statistics |

Full sample requests are available in `Postman Collections/ukonquiz-api.json.json`.

## 📫 Author

Umrbek Karimov (UKON) — karimovu960@gmail.com
