# DreamEvents (Academic DBMS Project)

DreamEvents is a BookMyShow-inspired event discovery and booking platform with role-based workflows.

## Tech Stack
- Frontend: HTML, CSS, Bootstrap 5
- Backend: PHP (PDO)
- Database: MySQL
- Runtime: XAMPP (Apache + MySQL)

## Setup (XAMPP)
1. Copy `DreamEvents` into `htdocs`.
2. Start Apache + MySQL.
3. Import `database/dreamevents.sql`.
4. For existing setup run `database/upgrade_v2.sql`.
5. Ensure `assets/images/` is writable.
6. Open `http://localhost/DreamEvents/auth/login.php`.

## Default Admin
- Username: `admin`
- Password: `admin123`

## Core Features
- Secure role-based login and session guards.
- Event discovery with premium UI, search, filters, capacity control.
- Multi-step booking + payment simulation.
- User event request workflow with admin approval/rejection.
- Privacy-compliant admin panel (view-only registrations, no CSV export).
- Refund workflow:
  - User requests refund for eligible future paid booking.
  - 10% commission retained by platform.
  - Admin approves/rejects via dedicated refund queue.
  - Revenue reflects retained commissions after approved refunds.

## Flow
- User: Browse → Search/Filter → Book → Pay (if needed) → My Bookings → Request Refund.
- Admin: Dashboard → Manage Events → Event Requests → Refund Requests → Registrations.
