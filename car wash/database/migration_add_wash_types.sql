-- Migration: Add Wash Types Support
-- This migration adds wash_types table and updates service_pricing to support wash types
-- Run this in phpMyAdmin or MySQL CLI

USE carwash_db;

-- Create wash_types table
CREATE TABLE IF NOT EXISTS wash_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '🧼',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default wash types
INSERT INTO wash_types (name, icon) VALUES
('Normal', '🧼'),
('Premium', '💎')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Add wash_type column to bookings table
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS wash_type VARCHAR(50) DEFAULT NULL AFTER car_type;

-- Drop existing unique key if it exists (to add wash_type_id)
ALTER TABLE service_pricing
    DROP KEY IF EXISTS unique_service_cartype;

-- Add wash_type_id column to service_pricing table
ALTER TABLE service_pricing
    ADD COLUMN IF NOT EXISTS wash_type_id INT DEFAULT NULL AFTER car_type_id,
    ADD CONSTRAINT fk_wash_type FOREIGN KEY (wash_type_id) REFERENCES wash_types(id) ON DELETE CASCADE;

-- Create new unique key with wash_type_id
ALTER TABLE service_pricing
    ADD UNIQUE KEY unique_service_cartype_washtype (service_id, car_type_id, wash_type_id);

-- Verify migration
SELECT 'Wash Types:' as info;
SELECT * FROM wash_types;

SELECT 'Service Pricing Structure:' as info;
DESCRIBE service_pricing;

SELECT 'Bookings Structure:' as info;
DESCRIBE bookings;
