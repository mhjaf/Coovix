<?php
// Migration script to add wash types support
// Run this file directly: php database/migrate_wash_types.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carwash_db');
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, DB_SOCKET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Running migration to add wash types support...\n\n";

// Create wash_types table
$sql = "CREATE TABLE IF NOT EXISTS wash_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '🧼',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✓ Created wash_types table\n";
} else {
    echo "✗ Error creating wash_types table: " . $conn->error . "\n";
}

// Insert default wash types
$sql = "INSERT INTO wash_types (name, icon) VALUES
('Normal', '🧼'),
('Premium', '💎')
ON DUPLICATE KEY UPDATE name = VALUES(name)";

if ($conn->query($sql)) {
    echo "✓ Inserted default wash types\n";
} else {
    echo "✗ Error inserting wash types: " . $conn->error . "\n";
}

// Add wash_type column to bookings table
$sql = "ALTER TABLE bookings ADD COLUMN wash_type VARCHAR(50) DEFAULT NULL AFTER car_type";
if ($conn->query($sql)) {
    echo "✓ Added wash_type column to bookings table\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- wash_type column already exists in bookings table\n";
    } else {
        echo "✗ Error adding wash_type column: " . $conn->error . "\n";
    }
}

// Check if unique key exists
$result = $conn->query("SHOW KEYS FROM service_pricing WHERE Key_name = 'unique_service_cartype'");
if ($result && $result->num_rows > 0) {
    // Drop existing unique key
    $sql = "ALTER TABLE service_pricing DROP KEY unique_service_cartype";
    if ($conn->query($sql)) {
        echo "✓ Dropped old unique key from service_pricing\n";
    } else {
        echo "✗ Error dropping unique key: " . $conn->error . "\n";
    }
}

// Add wash_type_id column to service_pricing table
$sql = "ALTER TABLE service_pricing ADD COLUMN wash_type_id INT DEFAULT NULL AFTER car_type_id";
if ($conn->query($sql)) {
    echo "✓ Added wash_type_id column to service_pricing table\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- wash_type_id column already exists in service_pricing table\n";
    } else {
        echo "✗ Error adding wash_type_id column: " . $conn->error . "\n";
    }
}

// Add foreign key for wash_type_id
$result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                        WHERE TABLE_NAME = 'service_pricing' AND CONSTRAINT_NAME = 'fk_wash_type'");
if (!$result || $result->num_rows == 0) {
    $sql = "ALTER TABLE service_pricing 
            ADD CONSTRAINT fk_wash_type FOREIGN KEY (wash_type_id) REFERENCES wash_types(id) ON DELETE CASCADE";
    if ($conn->query($sql)) {
        echo "✓ Added foreign key constraint for wash_type_id\n";
    } else {
        echo "✗ Error adding foreign key: " . $conn->error . "\n";
    }
} else {
    echo "- Foreign key constraint already exists\n";
}

// Create new unique key with wash_type_id
$result = $conn->query("SHOW KEYS FROM service_pricing WHERE Key_name = 'unique_service_cartype_washtype'");
if (!$result || $result->num_rows == 0) {
    $sql = "ALTER TABLE service_pricing 
            ADD UNIQUE KEY unique_service_cartype_washtype (service_id, car_type_id, wash_type_id)";
    if ($conn->query($sql)) {
        echo "✓ Added new unique key with wash_type_id\n";
    } else {
        echo "✗ Error adding unique key: " . $conn->error . "\n";
    }
} else {
    echo "- Unique key already exists\n";
}

echo "\n✓ Migration completed successfully!\n";

// Display wash types
echo "\nWash Types in database:\n";
$result = $conn->query("SELECT id, name, icon, status FROM wash_types");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Name: {$row['name']}, Icon: {$row['icon']}, Status: {$row['status']}\n";
    }
}

$conn->close();
?>
