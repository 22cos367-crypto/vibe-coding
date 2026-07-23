<?php
// ============================================================
// VV EVENTS — Event Booking API Endpoint
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
$fullName        = trim($input['bname'] ?? $input['full_name'] ?? '');
$phone           = trim($input['bphone'] ?? $input['phone'] ?? '');
$whatsapp        = trim($input['bwhatsapp'] ?? $input['whatsapp'] ?? '');
$email           = trim($input['bemail'] ?? $input['email'] ?? '');
$address         = trim($input['baddress'] ?? $input['address'] ?? '');
$eventLocation   = trim($input['blocation'] ?? $input['event_location'] ?? '');
$preferredTime   = trim($input['btime'] ?? $input['preferred_time'] ?? '');
$guestCount      = intval($input['guestCount'] ?? $input['guest_count'] ?? 100);
$budget          = trim($input['bbudget'] ?? $input['budget'] ?? '');
$specialRequests = trim($input['bmsg'] ?? $input['special_requests'] ?? '');

$selectedDateStr = trim($input['selectedDate'] ?? $input['event_date'] ?? '');
$eventType       = trim($input['eventType'] ?? $input['event_type'] ?? 'wedding');
$decoration      = trim($input['decoration'] ?? 'basic');
$entry           = trim($input['entry'] ?? 'none');

// Handle Addons (Can be array or JSON string)
$addonsRaw = $input['addons'] ?? [];
if (is_string($addonsRaw)) {
    $decodedAddons = json_decode($addonsRaw, true);
    $addonsArray = is_array($decodedAddons) ? $decodedAddons : array_filter(array_map('trim', explode(',', $addonsRaw)));
} elseif (is_array($addonsRaw)) {
    $addonsArray = $addonsRaw;
} else {
    $addonsArray = [];
}

// Basic Validation
if (empty($fullName) || empty($phone) || empty($email) || empty($address) || empty($eventLocation)) {
    sendJsonResponse(false, 'Please fill in all required customer details (Name, Phone, Email, Address, Venue Location).', [], 400);
}

// Convert date if string format like "15 July 2026" or "2026-07-15"
$eventDate = null;
if (!empty($selectedDateStr)) {
    $timestamp = strtotime($selectedDateStr);
    if ($timestamp) {
        $eventDate = date('Y-m-d', $timestamp);
    }
}
if (!$eventDate) {
    // Default to 7 days from now if not specified or invalid
    $eventDate = date('Y-m-d', strtotime('+7 days'));
}

// Check DB Connection
$db = getDBConnection();
if (!$db) {
    sendJsonResponse(false, 'Database connection error. Please try again later.', [], 500);
}

// Server-side Estimate Calculation
$priceMap = [
    'eventType' => ['corporate' => 25000, 'wedding' => 60000, 'birthday' => 12000, 'family' => 15000],
    'decoration' => ['basic' => 5000, 'premium' => 12000, 'luxury' => 22000],
    'entry' => ['none' => 0, 'balloon' => 4000, 'pyro' => 15000, 'dance' => 10000, 'dj' => 12000],
    'addons' => [
        'photography' => 8000,
        'videography' => 10000,
        'cake'        => 3000,
        'magicShow'   => 6000,
        'host'        => 5000
    ]
];

$estimatedPrice = 0;
$estimatedPrice += $priceMap['eventType'][$eventType] ?? 0;
$estimatedPrice += $priceMap['decoration'][$decoration] ?? 0;
$estimatedPrice += $priceMap['entry'][$entry] ?? 0;

foreach ($addonsArray as $addonKey) {
    if (isset($priceMap['addons'][$addonKey])) {
        $estimatedPrice += $priceMap['addons'][$addonKey];
    }
}

if ($guestCount > 100) {
    $estimatedPrice += floor(($guestCount - 100) / 50) * 3000;
}

// Generate unique Booking Reference
$bookingRef = 'VV-' . date('Y') . '-' . rand(1000, 9999);

try {
    $stmt = $db->prepare("
        INSERT INTO bookings 
        (booking_ref, full_name, phone, whatsapp, email, address, event_location, preferred_time, guest_count, budget, special_requests, event_date, event_type, decoration, entry, addons, estimated_price, status)
        VALUES 
        (:booking_ref, :full_name, :phone, :whatsapp, :email, :address, :event_location, :preferred_time, :guest_count, :budget, :special_requests, :event_date, :event_type, :decoration, :entry, :addons, :estimated_price, 'pending')
    ");

    $formattedTime = !empty($preferredTime) ? date('H:i:s', strtotime($preferredTime)) : null;
    $addonsJson = json_encode(array_values($addonsArray));

    $stmt->execute([
        ':booking_ref'     => $bookingRef,
        ':full_name'       => $fullName,
        ':phone'           => $phone,
        ':whatsapp'        => !empty($whatsapp) ? $whatsapp : $phone,
        ':email'           => $email,
        ':address'         => $address,
        ':event_location'   => $eventLocation,
        ':preferred_time'   => $formattedTime,
        ':guest_count'      => $guestCount,
        ':budget'          => $budget,
        ':special_requests' => $specialRequests,
        ':event_date'      => $eventDate,
        ':event_type'      => $eventType,
        ':decoration'      => $decoration,
        ':entry'           => $entry,
        ':addons'          => $addonsJson,
        ':estimated_price' => $estimatedPrice
    ]);

    sendJsonResponse(true, 'Booking request received successfully!', [
        'booking_ref'     => $bookingRef,
        'estimated_price' => $estimatedPrice,
        'event_date'      => $eventDate
    ], 201);

} catch (PDOException $e) {
    error_log("Booking Insert Error: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to save booking request. Please try again.', [], 500);
}
