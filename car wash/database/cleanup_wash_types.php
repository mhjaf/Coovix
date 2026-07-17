<?php
// Cleanup script to remove VIP and Premium wash types
// Run this file directly: php database/cleanup_wash_types.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carwash_db');
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, DB_SOCKET);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Cleaning up wash types (removing VIP)...\n\n";

// Delete VIP wash type
$sql = "DELETE FROM wash_types WHERE name = 'VIP'";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "✓ Removed $affected wash type(s) (VIP)\n";
} else {
    echo "✗ Error removing wash type: " . $conn->error . "\n";
}

// Set all existing bookings with VIP to Normal
$sql = "UPDATE bookings SET wash_type = 'Normal' WHERE wash_type = 'VIP'";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        echo "✓ Updated $affected booking(s) from VIP to Normal\n";
    } else {
        echo "- No bookings needed updating\n";
    }
} else {
    echo "✗ Error updating bookings: " . $conn->error . "\n";
}

// Remove pricing entries for VIP
$sql = "DELETE FROM service_pricing WHERE wash_type_id IN (SELECT id FROM wash_types WHERE name = 'VIP')";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        echo "✓ Removed $affected pricing entr(y/ies) for VIP\n";
    } else {
        echo "- No pricing entries to remove\n";
    }
} else {
    // Table might not exist yet, that's okay
    if (strpos($conn->error, "doesn't exist") === false) {
        echo "✗ Error removing pricing entries: " . $conn->error . "\n";
    }
}

echo "\n✓ Cleanup completed successfully!\n";

// Display remaining wash types
echo "\nRemaining Wash Types:\n";
$result = $conn->query("SELECT id, name, icon, status FROM wash_types");
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  - ID: {$row['id']}, Name: {$row['name']}, Icon: {$row['icon']}, Status: {$row['status']}\n";
        }
    } else {
        echo "  No wash types found (this is normal if migration hasn't been run yet)\n";
    }
}

$conn->close();
?>
