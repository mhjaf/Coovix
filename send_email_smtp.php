<?php
// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Load configuration
$config = require 'email_config.php';

// Start output buffering to prevent any unwanted output from breaking JSON response
ob_start();

header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Response function
function sendResponse($success, $message, $data = null)
{
    // Clear any buffered output
    if (ob_get_length()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Only POST requests are allowed');
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode fails, try regular POST data
if (!$input) {
    $input = $_POST;
}

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'message'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    sendResponse(false, 'Missing required fields: ' . implode(', ', $missing_fields));
}

// Sanitize input data
$name = trim(htmlspecialchars($input['name']));
$email = trim(htmlspecialchars($input['email']));
$phone = trim(htmlspecialchars($input['phone']));
$message = trim(htmlspecialchars($input['message']));

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Invalid email format');
}

// Validate phone number (basic validation)
if (!preg_match('/^[0-9\s\+\-\(\)]+$/', $phone)) {
    sendResponse(false, 'Invalid phone number format');
}

// Credentials must be provided by the VPS and must never be committed inside
// the public website directory.
if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
    error_log('Coovix contact form: Zoho SMTP credentials are not configured.');
    sendResponse(false, 'The email service is temporarily unavailable. Please contact us directly at info@coovix.com.');
}

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    if ($config['enable_debug']) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        // Capture debug output instead of echoing it
        $mail->Debugoutput = function ($str, $level) {
            error_log("SMTP Debug: $str");
        };
    }

    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['smtp_port'];
    $mail->CharSet = 'UTF-8';

    // Increase timeout for slower servers
    $mail->Timeout = 30;

    // Recipients
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email']);     // Add recipient
    $mail->addReplyTo($email, $name);           // Reply to the form submitter

    // Content
    $mail->isHTML(true);
    $mail->Subject = $config['subject_prefix'] . 'New Message from ' . $name;

    // Create HTML email body
    $html_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #495057; }
            .value { background: white; padding: 10px; border-left: 4px solid #007bff; }
            .footer { background: #e9ecef; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
                <p>Coovix Website</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div class='value'>{$name}</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'>{$email}</div>
                </div>
                <div class='field'>
                    <div class='label'>Phone:</div>
                    <div class='value'>{$phone}</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>" . nl2br($message) . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>Submitted: " . date('Y-m-d H:i:s') . "</p>
                <p>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</p>
            </div>
        </div>
    </body>
    </html>";

    // Create plain text version
    $text_body = "
New contact form submission from Coovix website:

Name: {$name}
Email: {$email}
Phone: {$phone}

Message:
{$message}

---
Submitted at: " . date('Y-m-d H:i:s') . "
IP Address: " . $_SERVER['REMOTE_ADDR'] . "
User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "
    ";

    $mail->Body = $html_body;
    $mail->AltBody = $text_body;

    // Send the email
    $mail->send();

    // Log successful submission
    $log_entry = date('Y-m-d H:i:s') . " - Email sent successfully via SMTP from: {$email} (Name: {$name})\n";
    file_put_contents('contact_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

    // Send auto-reply if enabled
    if ($config['send_copy_to_sender']) {
        $autoReply = new PHPMailer(true);
        $autoReply->isSMTP();
        $autoReply->Host = $config['smtp_host'];
        $autoReply->SMTPAuth = true;
        $autoReply->Username = $config['smtp_username'];
        $autoReply->Password = $config['smtp_password'];
        $autoReply->SMTPSecure = $config['smtp_secure'];
        $autoReply->Port = $config['smtp_port'];
        $autoReply->CharSet = 'UTF-8';

        $autoReply->setFrom($config['from_email'], $config['reply_to_name']);
        $autoReply->addAddress($email, $name);

        $autoReply->isHTML(true);
        $autoReply->Subject = 'Thank you for contacting Coovix';
        $autoReply->Body = "
        <h2>Thank you for your message!</h2>
        <p>Dear {$name},</p>
        <p>We have received your message and will get back to you within 24 hours.</p>
        <p>Best regards,<br>Coovix Team</p>
        ";

        $autoReply->send();
    }

    sendResponse(true, 'Thank you for your message! We will get back to you soon.', [
        'submitted_at' => date('Y-m-d H:i:s'),
        'message_id' => $mail->getLastMessageID()
    ]);

} catch (Exception $e) {
    // Log failed submission
    $log_entry = date('Y-m-d H:i:s') . " - Email failed to send via SMTP from: {$email} - Error: {$mail->ErrorInfo}\n";
    file_put_contents('contact_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

    // Provide detailed error info when debug is enabled
    if ($config['enable_debug']) {
        sendResponse(false, 'SMTP Error: ' . $mail->ErrorInfo . ' | Exception: ' . $e->getMessage());
    } else {
        sendResponse(false, 'Sorry, there was an error sending your message. Please try again or contact us directly.');
    }
}
?>
