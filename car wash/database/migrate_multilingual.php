<?php
/**
 * Migration: Add multilingual support for services
 * Adds Kurdish (ku) and Arabic (ar) columns for service name and description
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carwash_db');
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, DB_SOCKET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<h2>Adding Multilingual Support to Services Table</h2>";

// Add Kurdish name column
$sql1 = "ALTER TABLE services ADD COLUMN IF NOT EXISTS name_ku VARCHAR(255) DEFAULT NULL AFTER name";
if ($conn->query($sql1)) {
    echo "<p style='color: green;'>Added name_ku column (or already exists)</p>";
} else {
    // Try alternative syntax for older MySQL versions
    $check = $conn->query("SHOW COLUMNS FROM services LIKE 'name_ku'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE services ADD COLUMN name_ku VARCHAR(255) DEFAULT NULL AFTER name");
        echo "<p style='color: green;'>Added name_ku column</p>";
    } else {
        echo "<p style='color: blue;'>name_ku column already exists</p>";
    }
}

// Add Arabic name column
$check = $conn->query("SHOW COLUMNS FROM services LIKE 'name_ar'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE services ADD COLUMN name_ar VARCHAR(255) DEFAULT NULL AFTER name_ku");
    echo "<p style='color: green;'>Added name_ar column</p>";
} else {
    echo "<p style='color: blue;'>name_ar column already exists</p>";
}

// Add Kurdish description column
$check = $conn->query("SHOW COLUMNS FROM services LIKE 'description_ku'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE services ADD COLUMN description_ku TEXT DEFAULT NULL AFTER description");
    echo "<p style='color: green;'>Added description_ku column</p>";
} else {
    echo "<p style='color: blue;'>description_ku column already exists</p>";
}

// Add Arabic description column
$check = $conn->query("SHOW COLUMNS FROM services LIKE 'description_ar'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE services ADD COLUMN description_ar TEXT DEFAULT NULL AFTER description_ku");
    echo "<p style='color: green;'>Added description_ar column</p>";
} else {
    echo "<p style='color: blue;'>description_ar column already exists</p>";
}

echo "<h3>Migration Complete!</h3>";
echo "<p>You can now add Kurdish and Arabic translations for your services in the admin panel.</p>";
echo "<p><a href='../admin/settings.php'>Go to Admin Settings</a></p>";

$conn->close();
?>
