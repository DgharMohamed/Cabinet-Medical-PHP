-- ============================================================
-- Medical Cabinet Database Schema
-- Designed for a clinical appointment management system
-- ============================================================

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS cabinet_medical
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cabinet_medical;


-- ============================================================
-- 1. Admins Table
-- Stores the administrators of the system.
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Administrator username',
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Administrator email',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default administrator
INSERT INTO admins (username, password, email) 
VALUES ('admin', '$2y$10$gYV8RB638rtZOmD8s.wmPO/EvM5r3lOqP4OEz0d4UCD7n5ZghdxTa', 'admin@cabinet.com') 
ON DUPLICATE KEY UPDATE username=username;


-- ============================================================
-- 1. Services Table
-- Stores the different medical services offered by the doctor.
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'The name of the service (e.g., General Consultation)',
    price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'The price of the service',
    admin_id INT DEFAULT NULL COMMENT 'Admin who created/manages this service',
    CONSTRAINT fk_service_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. Time Slots Table
-- Defines the weekly working schedule of the clinic.
-- ============================================================
CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week VARCHAR(20) NOT NULL COMMENT 'English day name (e.g., Monday, Tuesday)',
    start_time TIME NOT NULL COMMENT 'Start time of the slot',
    end_time TIME NOT NULL COMMENT 'End time of the slot',
    max_patients INT DEFAULT 1 COMMENT 'Maximum number of patients allowed per slot',
    
    admin_id INT DEFAULT NULL COMMENT 'Admin who created/manages this time slot',
    
    -- Indexes for faster lookups and to prevent duplicate time slots on the same day
    INDEX idx_day (day_of_week),
    UNIQUE KEY uq_day_time (day_of_week, start_time, end_time),
    CONSTRAINT fk_timeslot_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. Schedule Exceptions Table
-- Allows the doctor to block specific dates (holidays, vacations).
-- ============================================================
CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exception_date DATE NOT NULL COMMENT 'The date the clinic is closed',
    reason VARCHAR(255) DEFAULT NULL COMMENT 'Reason for closure (e.g., National Holiday)',
    time_slot_id INT DEFAULT NULL COMMENT 'If NULL, the whole day is blocked. If set, only this slot is blocked',
    
    -- Ensure we don't block the same slot on the same day twice
    UNIQUE KEY uq_date_slot (exception_date, time_slot_id),
    
    admin_id INT DEFAULT NULL COMMENT 'Admin who added this exception',
    
    -- Foreign Key referencing time_slots
    CONSTRAINT fk_exc_time_slot FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    CONSTRAINT fk_exc_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. Appointments Table
-- Stores all patient appointment requests and their statuses.
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Full name of the patient',
    email VARCHAR(100) NOT NULL COMMENT 'Email address for communication',
    phone VARCHAR(50) NOT NULL COMMENT 'Contact phone number',
    cni VARCHAR(20) NOT NULL COMMENT 'National Identity Card number',
    service_type VARCHAR(100) NOT NULL COMMENT 'String fallback for the service requested',
    
    appointment_date DATE NOT NULL COMMENT 'The requested date of the appointment',
    message TEXT DEFAULT NULL COMMENT 'Optional message or symptoms described by patient',
    status ENUM('pending', 'confirmed', 'canceled') DEFAULT 'pending' COMMENT 'Current state of the appointment',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of request creation',
    
    -- Relational fields
    service_id INT DEFAULT NULL,
    time_slot_id INT DEFAULT NULL,
    
    admin_id INT DEFAULT NULL COMMENT 'Admin who managed this appointment',
    
    -- Security and Attachments
    medical_document VARCHAR(255) DEFAULT NULL COMMENT 'File path to the uploaded medical document (if any)',
    reference_number VARCHAR(50) DEFAULT NULL COMMENT 'Unique reference code (e.g., APT-20260520-1479)',
    public_token VARCHAR(64) DEFAULT NULL COMMENT 'Secure token to prevent IDOR vulnerabilities (public access)',

    -- Foreign Keys
    CONSTRAINT fk_appointment_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointment_timeslot FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointment_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    
    -- Indexes for performance and security constraints
    UNIQUE KEY uq_reference_number (reference_number),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_public_token (public_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Seed Data (Default Values)
-- ============================================================

-- Services proposés par le cabinet
INSERT IGNORE INTO services (name, price) VALUES 
('Consultation Générale', 200.00),
('Suivi de Maladie Chronique', 300.00),
('Bilan de Santé', 500.00),
('Visite à Domicile', 400.00),
('Consultation Pédiatrique', 250.00),
('Conseil en Nutrition', 300.00);

-- Créneaux horaires par défaut (planning hebdomadaire)

-- Lundi (Monday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Monday', '09:00:00', '11:00:00', 4),
('Monday', '11:00:00', '13:00:00', 4),
('Monday', '14:00:00', '16:00:00', 4),
('Monday', '16:00:00', '18:00:00', 4);

-- Mardi (Tuesday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Tuesday', '09:00:00', '11:00:00', 4),
('Tuesday', '11:00:00', '13:00:00', 4),
('Tuesday', '14:00:00', '16:00:00', 4),
('Tuesday', '16:00:00', '18:00:00', 4);

-- Mercredi (Wednesday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Wednesday', '09:00:00', '11:00:00', 4),
('Wednesday', '11:00:00', '13:00:00', 4),
('Wednesday', '14:00:00', '16:00:00', 4),
('Wednesday', '16:00:00', '18:00:00', 4);

-- Jeudi (Thursday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Thursday', '09:00:00', '11:00:00', 4),
('Thursday', '11:00:00', '13:00:00', 4),
('Thursday', '14:00:00', '16:00:00', 4),
('Thursday', '16:00:00', '18:00:00', 4);

-- Vendredi (Friday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Friday', '09:00:00', '11:00:00', 4),
('Friday', '11:00:00', '13:00:00', 4),
('Friday', '14:00:00', '16:00:00', 4),
('Friday', '16:00:00', '18:00:00', 4);

-- Samedi (Saturday)
INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES 
('Saturday', '09:00:00', '11:00:00', 4),
('Saturday', '11:00:00', '13:00:00', 4);
