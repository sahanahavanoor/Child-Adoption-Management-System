-- ============================================================
-- Child Adoption System - Database Setup
-- Run this file in phpMyAdmin or MySQL CLI
-- ============================================================

-- Step 1: Create the database
CREATE DATABASE IF NOT EXISTS child_adoption_db;
USE child_adoption_db;

-- ============================================================
-- Table 1: users
-- Stores both admin and adopter accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,        -- Stores hashed password
    role ENUM('admin', 'adopter') NOT NULL DEFAULT 'adopter',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table 2: children
-- Stores information about children available for adoption
-- ============================================================
CREATE TABLE IF NOT EXISTS children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT 'default.png',  -- Stores image filename
    status ENUM('available', 'adopted') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table 3: adoption_requests
-- Stores adoption requests made by adopters
-- ============================================================
CREATE TABLE IF NOT EXISTS adoption_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                  -- References users table
    child_id INT NOT NULL,                 -- References children table
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    id_proof VARCHAR(255),                 -- Stores uploaded ID proof filename
    message TEXT,                          -- Adopter's message/reason
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key constraints to maintain data integrity
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
);

-- ============================================================
-- Insert default admin account
-- Password: admin123 (hashed using PHP password_hash)
-- You can change this password after first login
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES (
    'Admin',
    'admin@adoption.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'admin'
);
-- NOTE: After importing, log in as admin and change the password
-- The above hash = "password" | You should run: password_hash('admin123', PASSWORD_DEFAULT) in PHP to get new hash
