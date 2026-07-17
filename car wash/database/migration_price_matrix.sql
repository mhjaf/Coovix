-- Migration: Price Matrix System
-- This migration adds support for different prices per car type
-- Run this in phpMyAdmin or MySQL CLI

USE carwash_db;

-- Create service_pricing junction table for price matrix
CREATE TABLE IF NOT EXISTS service_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    car_type_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_service_cartype (service_id, car_type_id),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (car_type_id) REFERENCES car_types(id) ON DELETE CASCADE
);

-- Add price_paid column to bookings table to store actual price at time of booking
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS price_paid DECIMAL(10,2) DEFAULT NULL AFTER service_id;

-- Migrate existing data: Copy base prices from services to all car types
-- This creates initial pricing entries using the service's base price
INSERT INTO service_pricing (service_id, car_type_id, price)
SELECT s.id, ct.id, s.price
FROM services s
CROSS JOIN car_types ct
WHERE ct.name != 'All Types'
  AND ct.status = 'active'
  AND s.status = 'active'
ON DUPLICATE KEY UPDATE price = VALUES(price);

-- Verify migration
SELECT
    s.name AS service_name,
    ct.name AS car_type,
    sp.price
FROM service_pricing sp
JOIN services s ON sp.service_id = s.id
JOIN car_types ct ON sp.car_type_id = ct.id
ORDER BY s.name, ct.name;
