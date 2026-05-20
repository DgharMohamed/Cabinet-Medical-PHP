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
-- 1. Services Table
-- Stores the different medical services offered by the doctor.
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'The name of the service (e.g., General Consultation)',
    price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'The price of the service'
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
    
    -- Indexes for faster lookups and to prevent duplicate time slots on the same day
    INDEX idx_day (day_of_week),
    UNIQUE KEY uq_day_time (day_of_week, start_time, end_time)
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
    
    -- Foreign Key referencing time_slots
    CONSTRAINT fk_exc_time_slot FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE
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
    
    -- Security and Attachments
    medical_document VARCHAR(255) DEFAULT NULL COMMENT 'File path to the uploaded medical document (if any)',
    reference_number VARCHAR(50) DEFAULT NULL COMMENT 'Unique reference code (e.g., APT-20260520-1479)',
    public_token VARCHAR(64) DEFAULT NULL COMMENT 'Secure token to prevent IDOR vulnerabilities (public access)',

    -- Foreign Keys
    CONSTRAINT fk_appointment_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointment_timeslot FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE SET NULL,
    
    -- Indexes for performance and security constraints
    UNIQUE KEY uq_reference_number (reference_number),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_public_token (public_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
