<?php
// ============================================================
// VV EVENTS — Contact Inquiry API Endpoint
// ============================================================

require_once __DIR__ . '/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed. Only POST requests are permitted.', [], 405);
}

// Decode JSON input or fallback to $_POST
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input || !is_array($input)) {
    $input = $_POST;
}

// Extract & Sanitize Data
$fullName = trim($input['cname'] ?? $input['full_name'] ?? '');
$phone    = trim($input['cphone'] ?? $input['phone'] ?? '');
$email    = trim($input['cemail'] ?? $input['email'] ?? '');
$message  = trim($input['cmsg'] ?? $input['message'] ?? '');

// Validation
if (empty($fullName) || empty($phone) || empty($email) || empty($message)) {
    sendJsonResponse(false, 'Please fill in all fields (Name, Phone, Email, Message).', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Invalid email address format.', [], 400);
}

// Check DB Connection
$db = getDBConnection();
if (!$db) {
    sendJsonResponse(false, 'Database connection error. Please try again later.', [], 500);
}

try {
    $stmt = $db->prepare("
        INSERT INTO contact_messages (full_name, phone, email, message, status)
        VALUES (:full_name, :phone, :email, :message, 'unread')
    ");

    $stmt->execute([
        ':full_name' => $fullName,
        ':phone'     => $phone,
        ':email'     => $email,
        ':message'   => $message
    ]);

    sendJsonResponse(true, "Thank you! We've received your message and will reach out shortly.", [
        'id' => $db->lastInsertId()
    ], 201);

} catch (PDOException $e) {
    error_log("Contact Insert Error: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to send your message. Please try again.', [], 500);
}
