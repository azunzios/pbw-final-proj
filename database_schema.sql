-- Buat database
CREATE DATABASE IF NOT EXISTS petcare_pbw;
USE petcare_pbw;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(64) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
    );

-- Tabel user_settings
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    require_notes BOOLEAN DEFAULT true,
    enable_notifications BOOLEAN DEFAULT true,
    reminder_minutes INT DEFAULT 15,
    FOREIGN KEY (user_id) REFERENCES users(id)
    );

-- Tabel pets
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    birth_date DATE,
    weight DECIMAL(5, 2) DEFAULT 0.0,
    length DECIMAL(5, 2) DEFAULT 0.0,
    gender VARCHAR(20),
    notes TEXT,
    image_path TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
    );

-- Tabel schedules
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    pet_id INT NOT NULL,
    care_type VARCHAR(50) NOT NULL,
    schedule_time DATETIME NOT NULL,
    days VARCHAR(50) NOT NULL,
    recurrence VARCHAR(20) DEFAULT 'Once',
    category VARCHAR(50) DEFAULT 'General',
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
    );

-- Tabel care_logs
CREATE TABLE IF NOT EXISTS care_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    pet_id INT NOT NULL,
    schedule_id INT,
    care_type VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    done_by VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
    );

-- Tabel pet_measurements
CREATE TABLE IF NOT EXISTS pet_measurements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    weight DECIMAL(5, 2),
    length DECIMAL(5, 2),
    notes TEXT,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
    );

-- Tabel schedule_instances
CREATE TABLE IF NOT EXISTS schedule_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT,
    date DATE,
    is_done BOOLEAN,
    notes TEXT,
    done_at TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
    );

-- Tabel remember_tokens untuk fitur ingat saya
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
);

-- Tambah user admin default
INSERT IGNORE INTO users (username, password_hash, email, full_name)
VALUES ('admin', SHA2('admin123', 256), 'admin@example.com', 'Administrator');
