<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required_fields = ['staff_rating', 'service_rating', 'hygiene_rating', 'user_language'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize input data
$staff_rating = (int) $input['staff_rating'];
$service_rating = (int) $input['service_rating'];
$hygiene_rating = (int) $input['hygiene_rating'];
$customer_notes = isset($input['customer_notes']) ? trim(strip_tags($input['customer_notes'])) : '';
$user_language = htmlspecialchars($input['user_language']);
$submission_date = date('Y-m-d H:i:s');

// Validate ratings (should be between 1-5)
if ($staff_rating < 1 || $staff_rating > 5 || 
    $service_rating < 1 || $service_rating > 5 || 
    $hygiene_rating < 1 || $hygiene_rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rating values']);
    exit;
}

// Calculate totals
$total_stars = $staff_rating + $service_rating + $hygiene_rating;
$average_rating = round($total_stars / 3, 1);

// Create star representations
function createStars($rating) {
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

$staff_stars = createStars($staff_rating);
$service_stars = createStars($service_rating);
$hygiene_stars = createStars($hygiene_rating);

$to_email = 'championsrest956@gmail.com'; 
$from_email = 'noreply@codiva.co'; 
$subject = 'New Customer Feedback - Champions Restaurant';

$html_content = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: white; padding: 20px; text-align: center; }
        .rating-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .rating-item { display: flex; justify-content: space-between; margin: 10px 0; }
        .stars { font-size: 18px; color: #f59e0b; }
        .notes-section { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .summary { background: #e8f5e8; padding: 15px; text-align: center; border-radius: 8px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏆 Champions Restaurant</h1>
            <h2>New Customer Feedback</h2>
        </div>
        
        <div class='summary'>
            <h3>Overall Rating: {$average_rating}/5.0</h3>
            <p>Total Stars: {$total_stars}/15</p>
            <p>Submitted: {$submission_date}</p>
            <p>Language: {$user_language}</p>
        </div>
        
        <div class='rating-section'>
            <h3>📊 Detailed Ratings</h3>
            <div class='rating-item'>
                <strong>👥 Staff:</strong>
                <span class='stars'>{$staff_stars} ({$staff_rating}/5)</span>
            </div>
            <div class='rating-item'>
                <strong>🔔 Service:</strong>
                <span class='stars'>{$service_stars} ({$service_rating}/5)</span>
            </div>
            <div class='rating-item'>
                <strong>🛡️ Hygiene:</strong>
                <span class='stars'>{$hygiene_stars} ({$hygiene_rating}/5)</span>
            </div>
        </div>";

// Add notes section if notes are provided
if (!empty($customer_notes)) {
    $html_content .= "
        <div class='notes-section'>
            <h3>💬 Customer Notes</h3>
            <p><em>\"" . nl2br(htmlspecialchars($customer_notes)) . "\"</em></p>
        </div>";
} else {
    $html_content .= "
        <div class='notes-section'>
            <h3>💬 Customer Notes</h3>
            <p><em>No additional notes provided.</em></p>
        </div>";
}

$html_content .= "
        <div class='footer'>
            <p>This feedback was submitted through the Champions Restaurant website.</p>
            <p>Please respond to customer feedback promptly to maintain service quality.</p>
        </div>
    </div>
</body>
</html>";

// Create plain text version
$plain_content = "
CHAMPIONS RESTAURANT - NEW CUSTOMER FEEDBACK

Overall Rating: {$average_rating}/5.0
Total Stars: {$total_stars}/15
Submitted: {$submission_date}
Language: {$user_language}

DETAILED RATINGS:
- Staff: {$staff_rating}/5 {$staff_stars}
- Service: {$service_rating}/5 {$service_stars}
- Hygiene: {$hygiene_rating}/5 {$hygiene_stars}

CUSTOMER NOTES:
" . (!empty($customer_notes) ? $customer_notes : 'No additional notes provided.') . "

---
This feedback was submitted through the Champions Restaurant website.
";

// Email headers
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
$headers .= "From: Champions Restaurant <{$from_email}>" . "\r\n";
$headers .= "Reply-To: {$from_email}" . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mail_sent = mail($to_email, $subject, $html_content, $headers);

if ($mail_sent) {
    $log_entry = date('Y-m-d H:i:s') . " - Feedback submitted - Overall: {$average_rating}/5, Language: {$user_language}, Notes: " . 
                 (empty($customer_notes) ? 'None' : 'Yes') . "\n";
    file_put_contents('feedback_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback sent successfully',
        'data' => [
            'average_rating' => $average_rating,
            'total_stars' => $total_stars,
            'has_notes' => !empty($customer_notes)
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to send email',
        'message' => 'There was a problem sending your feedback. Please try again later.'
    ]);
}
?>