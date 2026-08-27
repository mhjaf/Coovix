<?php
// Shared Database Configuration
// Update these values from your Hostinger hPanel > Databases > MySQL Databases
define('DB_HOST', 'localhost');
define('DB_USER', 'u533650363_db_bar');
define('DB_PASS', 'Cocihan2024@');
define('DB_NAME', 'u533650363_bar');
define('DB_SOCKET', '');

function createBarDatabaseConnection() {
    mysqli_report(MYSQLI_REPORT_OFF);

    $socket = getenv('BAR_DB_SOCKET') ?: DB_SOCKET;
    if (!$socket && file_exists('/Applications/MAMP/tmp/mysql/mysql.sock')) {
        $socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
    }

    try {
        $host = getenv('BAR_DB_HOST') ?: DB_HOST;
        $user = getenv('BAR_DB_USER') ?: DB_USER;
        $pass = getenv('BAR_DB_PASS') ?: DB_PASS;
        $name = getenv('BAR_DB_NAME') ?: DB_NAME;
        $port = (int) (getenv('BAR_DB_PORT') ?: 3306);
        $conn = @new mysqli($host, $user, $pass, $name, $port, $socket ?: null);
    } catch (Throwable $error) {
        error_log('Bar database connection failed: ' . $error->getMessage());
        return null;
    }

    if ($conn->connect_errno) {
        error_log('Bar database connection failed: ' . $conn->connect_error);
        return null;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
