<?php
// Include database configuration
require_once __DIR__ . '/admin/db_config.php';

// Get language parameter (default: en)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$validLangs = ['en', 'ku', 'ar'];
if (!in_array($lang, $validLangs)) {
    $lang = 'en';
}

try {
    $conn = createBarDatabaseConnection();

    if (!$conn) {
        throw new Exception('Database connection unavailable');
    }

    // Get all active barbers with photos and multilingual names
    $sql = "SELECT id, name, name_ku, name_ar, photo FROM barbers WHERE status = 'active' ORDER BY name ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $barbers = [];
    while ($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }

    // Return JSON for AJAX or HTML for direct include
    if (isset($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode($barbers);
    } else {
        // Return HTML
        if (count($barbers) > 0) {
            foreach ($barbers as $barber) {
                // Get name based on language (fallback to English if not available)
                if ($lang === 'ku' && !empty($barber['name_ku'])) {
                    $name = htmlspecialchars($barber['name_ku']);
                } elseif ($lang === 'ar' && !empty($barber['name_ar'])) {
                    $name = htmlspecialchars($barber['name_ar']);
                } else {
                    $name = htmlspecialchars($barber['name']);
                }

                // Get initial based on language
                $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');

                echo '<div class="barber-card">';
                if (!empty($barber['photo'])) {
                    $photo = htmlspecialchars($barber['photo']);
                    // Convert absolute path to relative path
                    // Photo is stored as /uploads/barbers/filename - make it relative
                    $photo = ltrim($photo, '/');
                    // Add cache busting timestamp
                    $photo_with_cache = $photo . '?v=' . time();
                    echo '<div class="barber-image">';
                    echo '<img src="' . $photo_with_cache . '" alt="' . $name . '" onerror="this.parentElement.innerHTML=\'<div class=\\\'barber-placeholder\\\'><div class=\\\'barber-initial\\\'>' . $initial . '</div></div>\';">';
                    echo '</div>';
                } else {
                    echo '<div class="barber-image barber-placeholder">';
                    echo '<div class="barber-initial">' . $initial . '</div>';
                    echo '</div>';
                }
                echo '<div class="barber-info">';
                echo '<h3 class="barber-name">' . $name . '</h3>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            // No barbers message based on language
            $noBarberMsg = 'No barbers available at the moment.';
            if ($lang === 'ku') {
                $noBarberMsg = 'هیچ دەلاکێک لە ئێستادا بەردەست نییە.';
            } elseif ($lang === 'ar') {
                $noBarberMsg = 'لا يوجد حلاقون متاحون حالياً.';
            }
            echo '<div class="no-barbers">';
            echo '<p>' . $noBarberMsg . '</p>';
            echo '</div>';
        }
    }

    $conn->close();

} catch (Exception $e) {
    // Show error only in development, graceful message in production
    $errorMsg = 'Our barbers information is currently unavailable.';
    if ($lang === 'ku') {
        $errorMsg = 'زانیاری دەلاکەکانمان لە ئێستادا بەردەست نییە.';
    } elseif ($lang === 'ar') {
        $errorMsg = 'معلومات حلاقينا غير متوفرة حالياً.';
    }
    echo '<div class="barbers-error" style="text-align: center; padding: 40px; color: #cccccc;">';
    echo '<p>' . $errorMsg . '</p>';
    error_log('get-barbers error: ' . $e->getMessage());
    echo '</div>';
}
?>
