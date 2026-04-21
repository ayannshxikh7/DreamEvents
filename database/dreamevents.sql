CREATE DATABASE IF NOT EXISTS dreamevents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dreamevents;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(120) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT NULL,
    capacity INT NOT NULL DEFAULT 100,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    booking_reference VARCHAR(32) DEFAULT NULL,
    payment_status ENUM('free', 'paid') NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    refund_status ENUM('none','requested','approved','rejected') NOT NULL DEFAULT 'none',
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    commission_deducted DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_registrations_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registrations_event FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_user_event UNIQUE (user_id, event_id)
);

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

INSERT INTO users (username, email, password, role)
VALUES ('admin', 'admin@dreamevents.local', '$2y$12$wxACj1jVMslxqw3G4IHZHOv4LYrPutpsdllmbwefT5MejXnCnTfc6', 'admin')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO events (event_name, event_date, event_time, venue, description, price, image)
VALUES
('Indie Music Fest', '2026-04-20', '18:30:00', 'Downtown Arena', 'A vibrant evening featuring top indie bands and food pop-ups.', 799.00, 'default.jpg'),
('AI & Future Tech Summit', '2026-05-03', '10:00:00', 'City Convention Center', 'Talks and demos on AI, robotics, and startup innovation.', 0.00, 'default.jpg'),
('Open-Air Cinema Night', '2026-05-12', '20:00:00', 'Riverside Park', 'Classic films under the stars with comfy seating and snacks.', 299.00, 'default.jpg');
