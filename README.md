# StockSense IMS

Modular Inventory Management System. PHP 8 (OOP, MVC) + MySQL + Bootstrap 5 + jQuery, REST APIs, PHPMailer, TCPDF.

**Built so far: M0 Foundation + M1 Authentication & Access.** Remaining modules are listed at the bottom.

## 1. Requirements
PHP 8.1+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd` (captcha), `curl` (TCPDF). MySQL/MariaDB. Apache with `mod_rewrite` (XAMPP/WAMP is fine).
In XAMPP these are in `php.ini`: make sure `extension=gd` and `extension=curl` are not commented out, then restart Apache.

## 2. Setup
1. Put the folder in `htdocs/` (e.g. `C:\xampp\htdocs\inventory-management-system`).
2. Import the database: phpMyAdmin -> Import -> `database/schema.sql` (it creates `stocksense_ims`).
3. Copy `.env.example` to `.env` and fill it:
   - `APP_URL` = where the site opens, e.g. `http://localhost/inventory-management-system/public` (used inside e-mail links)
   - `APP_KEY` = any long random string
   - `MANAGER_REGISTRATION_KEY` = secret needed to sign up as Inventory Manager
   - `DB_*` = your MySQL login
   - `MAIL_*` = SMTP. Gmail: turn on 2-step verification, create an **App Password**, put it in `MAIL_PASSWORD`.
   - To test without real mail set `MAIL_DRIVER=log`; OTPs and links are then written to `storage/logs/mail.log`.
4. Open `http://localhost/inventory-management-system/` (or `.../public/`).
5. `vendor/` is already installed. If you ever change dependencies run `composer install`.

## 3. Folder structure
```
public/            index.php (front controller), assets/{css,js}
src/Core/          Router, Request, Response, Database, Session, Validator, Csrf, RateLimiter, Logger, View
src/Controllers/   AuthController, ProfileController, PageController
src/Models/        User
src/Services/      OtpService, PasswordResetService, MailService (PHPMailer), CaptchaService, PdfService (TCPDF)
src/Middleware/    auth, guest, role, csrf
views/             layouts, partials, auth, dashboard, profile
config/            navigation.php (sidebar; items appear when their module is built)
database/          schema.sql
lib/tcpdf/         TCPDF (trimmed: no tools/examples/CJK fonts)
storage/logs/      app logs, mail.log
```

## 4. REST API (v1, JSON)
Envelope: `{ success, message, code, data, errors, meta }`. State-changing calls need header `X-CSRF-Token` (the pages add it).

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/v1/auth/captcha | captcha PNG (single use) |
| GET | /api/v1/auth/check-availability?field=email\|phone&value= | duplicate check (rate limited) |
| POST | /api/v1/auth/register | validate, captcha, duplicate check, then send OTP |
| GET | /api/v1/auth/otp/status | remaining seconds, can_resend |
| POST | /api/v1/auth/otp/verify | verify OTP |
| POST | /api/v1/auth/otp/resend | only after the previous OTP expired |
| POST | /api/v1/auth/login, /logout | session login/logout |
| POST | /api/v1/auth/forgot-password | e-mail reset link (registered e-mails only) |
| POST | /api/v1/auth/reset-password | set new password with token |
| GET/PUT | /api/v1/profile | my profile |
| POST | /api/v1/profile/password | change password |

## 5. Security behaviour (M1)
- OTP: 6 digits, stored as HMAC, valid **120 s**, 5 wrong tries, resend only after expiry, max 5 sends/hour/e-mail. If the e-mail fails the OTP is discarded so no timer starts.
- Reset link: 256-bit token stored as SHA-256, valid **8 h**, **one use**, a new link voids older ones.
- Passwords: `password_hash`. Login rate limit: 5 fails / 15 min per e-mail+IP. CSRF on every write. Session cookie HttpOnly + SameSite=Lax, idle timeout.
- Duplicates: checked **before** any OTP is sent (verified accounts only; an unverified sign-up can be resumed).
- Manager role needs `MANAGER_REGISTRATION_KEY`. Staff sign up freely.

## 6. Module status
M0 Foundation - done · M1 Auth & Access - done · M2 Warehouses + map · M3 Products · M4 Approvals + Receipts · M5 Deliveries + tracking · M6 Transfers · M7 Adjustments · M8 Move History · M9 Dashboards · M10 Alerts/Staff · M11 QA
