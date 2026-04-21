USE dreamevents;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email VARCHAR(120) NULL AFTER username;

ALTER TABLE events
    ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER description,
    ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL AFTER price,
    ADD COLUMN IF NOT EXISTS capacity INT NOT NULL DEFAULT 100 AFTER image;

ALTER TABLE registrations
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NOT NULL DEFAULT 'Guest' AFTER event_id,
    ADD COLUMN IF NOT EXISTS gender ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Other' AFTER full_name,
    ADD COLUMN IF NOT EXISTS age TINYINT UNSIGNED NOT NULL DEFAULT 18 AFTER gender,
    ADD COLUMN IF NOT EXISTS booking_reference VARCHAR(32) DEFAULT NULL AFTER age,
    ADD COLUMN IF NOT EXISTS payment_status ENUM('free', 'paid') NOT NULL DEFAULT 'free' AFTER age,
    ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER payment_status,
    ADD COLUMN IF NOT EXISTS refund_status ENUM('none','requested','approved','rejected') NOT NULL DEFAULT 'none' AFTER amount_paid,
    ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER refund_status,
    ADD COLUMN IF NOT EXISTS commission_deducted DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER refund_amount;

CREATE TABLE IF NOT EXISTS event_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_name VARCHAR(120) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_requests_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS refund_requests (
    refund_id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    original_amount DECIMAL(10,2) NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    commission_deducted DECIMAL(10,2) NOT NULL,
    status ENUM('requested','approved','rejected') NOT NULL DEFAULT 'requested',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_refund_registration FOREIGN KEY (registration_id) REFERENCES registrations(registration_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_refund_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_refund_event FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    identity_key VARCHAR(150) NOT NULL UNIQUE,
    fail_count INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_attempt DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_reset_otps (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);


UPDATE users SET email = CONCAT(username, '@dreamevents.local') WHERE email IS NULL OR email = '';
ALTER TABLE users MODIFY COLUMN email VARCHAR(120) NOT NULL;
CREATE UNIQUE INDEX uq_users_email ON users(email);
