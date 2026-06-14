# Cabinet Medical - Dr. Dghar Mohamed

A web application for managing medical appointments, built with PHP and MySQL.

---

## Features

- **Patient Side**
  - Book an appointment online (name, phone, service, date, time slot)
  - Upload a medical document (PDF / image)
  - Track appointment status using reference number + CNI
  - View appointment confirmation ticket with QR code

- **Admin Side**
  - Secure login with password hashing (bcrypt)
  - Dashboard with appointment statistics
  - Manage appointments (confirm / cancel / delete)
  - Create appointments manually
  - Manage time slots and schedule exceptions (holidays)
  - Change admin password

- **Multilingual** — supports French and Arabic (RTL)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ |
| Database | MySQL (PDO) |
| Frontend | HTML, CSS, JavaScript |
| Server | Apache (XAMPP) |

---

## Installation

### Requirements
- XAMPP (Apache + MySQL + PHP 8+)

### Steps

1. **Clone or copy** the project into `C:\xampp\htdocs\Cabinet Medicale PHP\`

2. **Import the database**
   - Open `http://localhost/phpmyadmin`
   - Create a new database named `cabinet_medical`
   - Import the file `database/database.sql`

3. **Configure the database connection**
   - Open `config/Database.php`
   - Update credentials if needed (default: `root` with no password)

4. **Start the application**
   - Open your browser and go to: `http://localhost/Cabinet%20Medicale%20PHP/`

---

## Admin Access

| Field | Value |
|-------|-------|
| URL | `http://localhost/Cabinet%20Medicale%20PHP/admin/login.php` |
| Password | `admin123` |

> Change the password after first login via the admin panel.

---

## Project Structure

```
Cabinet Medicale PHP/
├── admin/
│   ├── change-password.php
│   ├── create-appointment.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── schedule.php
│   ├── update-max-patients.php
│   └── update-status.php
├── assets/
│   ├── css/
│   │   ├── admin-login.css
│   │   ├── admin.css
│   │   └── main.css
│   ├── images/
│   └── js/
│       ├── admin.js
│       └── main.js
├── config/
│   └── Database.php
├── database/
│   └── database.sql
├── includes/
│   ├── footer.php
│   └── header.php
├── lang/
│   └── translations.php
├── models/
│   └── Appointment.php
├── traitement/
│   ├── download-document.php
│   ├── get-slots.php
│   └── submit-appointment.php
├── uploads/
├── .gitignore
├── confirmation.php
├── index.php
├── pending-confirmation.php
├── track-appointment.php
└── README.md
```

---

## Security

- SQL Injection prevention via **PDO Prepared Statements**
- Password hashing with **bcrypt** (`password_hash`)
- XSS prevention via **`htmlspecialchars()`** on all outputs
- File upload validation (type + size)
- Secure unique reference generation using **CSPRNG** (`random_bytes()`)

---

## Author

**Mohamed Dghar** — Final Year Project (Solicode)
