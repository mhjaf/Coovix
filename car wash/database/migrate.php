<?php
// Run this file once to migrate the database
// Access it via: http://localhost:8000/database/migrate.php

require_once '../admin/config/database.php';

$conn = getConnection();

echo "<h2>Database Migration</h2>";
echo "<p>Adding category columns...</p>";

$queries = [
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        icon VARCHAR(10) DEFAULT '🚗',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS car_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        icon VARCHAR(10) DEFAULT '🚗',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "INSERT IGNORE INTO categories (name, icon) VALUES ('Car Wash', '🚗'), ('Polish', '✨')",
    "INSERT IGNORE INTO car_types (name, icon) VALUES 
        ('All Types', '🚗'), ('Sedan', '🚙'), ('SUV', '🚐'), ('Hatchback', '🚗'), 
        ('Coupe', '🏎️'), ('Convertible', '🚘'), ('Truck', '🚚'), 
        ('Van', '🚐'), ('Minivan', '🚐'), ('Wagon', '🚙')",
    "ALTER TABLE services ADD COLUMN category VARCHAR(50) DEFAULT 'Car Wash' AFTER description",
    "UPDATE services SET category = 'Car Wash' WHERE category IS NULL OR category = ''",
    "UPDATE services SET category = 'Polish' WHERE name LIKE '%ceramic%' OR name LIKE '%polish%' OR name LIKE '%coating%'",
    "ALTER TABLE services ADD COLUMN car_type VARCHAR(50) DEFAULT 'All Types' AFTER category",
    "ALTER TABLE bookings DROP COLUMN email",
    "ALTER TABLE bookings ADD COLUMN category VARCHAR(50) AFTER phone",
    "ALTER TABLE bookings ADD COLUMN car_type VARCHAR(50) AFTER category",
    "ALTER TABLE bookings MODIFY COLUMN booking_time TIME AFTER booking_date",
    "ALTER TABLE bookings MODIFY COLUMN notes TEXT AFTER booking_time",
    "ALTER TABLE admins DROP COLUMN role",
    "ALTER TABLE admins ADD COLUMN permissions JSON AFTER password",
    "UPDATE admins SET permissions = '[\"dashboard\",\"bookings\",\"reports\",\"settings\",\"users\"]' WHERE permissions IS NULL"
];

foreach ($queries as $query) {
    echo "<p>Running: " . htmlspecialchars($query) . "</p>";
    
    try {
        if ($conn->query($query)) {
            echo "<p style='color: green;'>✓ Success</p>";
        } else {
            $error = $conn->error;
            // Ignore "duplicate column" and "can't drop" errors
            if (strpos($error, 'Duplicate column') !== false || strpos($error, "Can't DROP") !== false) {
                echo "<p style='color: orange;'>⚠ Already exists (skipped)</p>";
            } else {
                echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($error) . "</p>";
            }
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
        if (strpos($error, 'Duplicate column') !== false || strpos($error, "Can't DROP") !== false) {
            echo "<p style='color: orange;'>⚠ Already exists (skipped)</p>";
        } else {
            echo "<p style='color: red;'>✗ Exception: " . htmlspecialchars($error) . "</p>";
        }
    }
    echo "<hr>";
}

echo "<h3>Migration Complete!</h3>";
echo "<p><a href='../admin/settings.php'>Go to Admin Settings</a></p>";

$conn->close();
?>
