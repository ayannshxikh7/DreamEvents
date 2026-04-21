# DreamEvents (Academic DBMS Project)

DreamEvents is a BookMyShow-inspired event discovery and booking platform with role-based admin/user workflows.

## Tech Stack
- Frontend: HTML, CSS, Bootstrap 5
- Backend: PHP (PDO)
- Database: MySQL
- Runtime: XAMPP (Apache + MySQL)

## Setup (XAMPP)
1. Copy `DreamEvents` into `htdocs`.
2. Start Apache + MySQL.
3. Import `database/dreamevents.sql` for fresh setup.
4. Existing installations should run `database/upgrade_v2.sql`.
5. Ensure `assets/images/` is writable.
6. Open `http://localhost/DreamEvents/auth/login.php`.

## Default Admin
- Username: `admin`
- Email: `admin@dreamevents.local`
- Password: `admin123`

## Core Features
- Secure role-based authentication and session guards.
- Email-based signup/login support with password hashing (`password_hash`, `password_verify`).
- Forgot password with OTP verification (6-digit, 10-minute expiry, one-time use).
- Login rate limiting (5 failed attempts lock for 15 minutes).
- CSRF protection across sensitive forms/actions.
- Event CRUD (Add, Edit, Delete) with secure image upload (JPG/PNG, max 2MB).
- Event request approval workflow with user email notifications.
- Booking flow with attendee details + payment simulation.
- Booking confirmation email after successful booking.
- Refund request workflow with 10% commission retention + email notifications.
- Downloadable PDF ticket/invoice from My Bookings.
- Enhanced admin analytics:
  - Most booked event
  - Highest revenue event
  - Top 5 events by registrations
  - Successful refunds
  - Refund loss vs retained commission
  - Revenue after refunds

## Email Notes
- The project uses PHP `mail()` through `includes/mailer.php` with branded HTML templates.
- For local delivery, configure your PHP/XAMPP mail transport (SMTP/sendmail).

## Flow
- User: Browse → Search/Filter → Booking Form → Pay (if needed) → My Bookings → Download Ticket / Request Refund.
- Admin: Dashboard (analytics) → Add/Edit/Manage Events → Event Requests → Refund Requests → Registrations.
