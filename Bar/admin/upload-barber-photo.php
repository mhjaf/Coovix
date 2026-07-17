<?php
/**
 * AJAX endpoint for instant barber photo upload
 * Uploads photo immediately and updates database
 */
require_once 'config.php';
requireLogin();

// Check permission
if (!hasPermission('can_barbers')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

header('Content-Type: application/json');

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Validate barber ID
if (!isset($_POST['barber_id']) || !is_numeric($_POST['barber_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid barber ID']);
    exit();
}

$barberId = intval($_POST['barber_id']);

// Get current barber data
$stmt = $conn->prepare("SELECT id, photo FROM barbers WHERE id = ?");
$stmt->bind_param("i", $barberId);
$stmt->execute();
$result = $stmt->get_result();
$barber = $result->fetch_assoc();
$stmt->close();

if (!$barber) {
    echo json_encode(['success' => false, 'message' => 'Barber not found']);
    exit();
}

// Handle photo removal
if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
    // Delete the photo file
    if ($barber['photo'] && file_exists('..' . $barber['photo'])) {
        @unlink('..' . $barber['photo']);
    }

    // Update database to remove photo
    $stmt = $conn->prepare("UPDATE barbers SET photo = NULL WHERE id = ?");
    $stmt->bind_param("i", $barberId);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Photo removed successfully',
            'photo' => null
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove photo from database']);
    }
    $stmt->close();
    exit();
}

// Handle photo upload
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No photo file uploaded']);
    exit();
}

if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
    ];
    $errorCode = $_FILES['photo']['error'];
    $errorMsg = isset($uploadErrors[$errorCode]) ? $uploadErrors[$errorCode] : 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $errorMsg]);
    exit();
}

// Validate file
$fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPG, JPEG, PNG, or GIF.']);
    exit();
}

// Verify MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file content. File does not appear to be a valid image.']);
    exit();
}

// Verify it's actually an image
$imageInfo = @getimagesize($_FILES['photo']['tmp_name']);
if ($imageInfo === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid image file.']);
    exit();
}

if ($_FILES['photo']['size'] > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
    exit();
}

// Create upload directory if it doesn't exist (with secure permissions)
$uploadDir = '../uploads/barbers/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
}

// Generate unique filename
$fileName = 'barber_' . $barberId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
$uploadPath = $uploadDir . $fileName;

// Upload file
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Error uploading photo. Please check folder permissions.']);
    exit();
}

// Delete old photo if exists
if ($barber['photo'] && file_exists('..' . $barber['photo'])) {
    @unlink('..' . $barber['photo']);
}

// Update database with new photo path
$photoPath = '/uploads/barbers/' . $fileName;
$stmt = $conn->prepare("UPDATE barbers SET photo = ? WHERE id = ?");
$stmt->bind_param("si", $photoPath, $barberId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Photo uploaded successfully!',
        'photo' => $photoPath . '?v=' . time()
    ]);
} else {
    // If database update fails, remove the uploaded file
    @unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'Failed to update database']);
}
$stmt->close();
?>
