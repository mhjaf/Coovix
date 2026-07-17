-- Migration to add category columns
-- Run this SQL to update your existing database

USE carwash_db;

-- Add category column to services table
ALTER TABLE services ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'Car Wash' AFTER description;

-- Update existing services with default categories
UPDATE services SET category = 'Car Wash' WHERE category IS NULL OR category = '';
UPDATE services SET category = 'Polish' WHERE name LIKE '%ceramic%' OR name LIKE '%polish%' OR name LIKE '%coating%';

-- Remove email column from bookings and add category, time, notes
ALTER TABLE bookings DROP COLUMN IF EXISTS email;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS category VARCHAR(50) AFTER phone;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS booking_time TIME AFTER booking_date;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS notes TEXT AFTER booking_time;
