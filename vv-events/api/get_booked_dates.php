<?php
// ============================================================
// VV EVENTS — Get Booked Dates API Endpoint
// ============================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$year  = isset($_GET['year']) ? intval($_GET['year']) : null;

$db = getDBConnection();
if (!$db) {
    // Return empty fallback array on DB fail so calendar doesn't break
    echo json_encode(['success' => true, 'booked_days' => [], 'booked_dates' => []]);
    exit;
}

try {
    if ($month && $year) {
        $stmt = $db->prepare("
            SELECT DISTINCT event_date, DAY(event_date) AS day_num 
            FROM bookings 
            WHERE status IN ('confirmed', 'pending') 
            AND MONTH(event_date) = :month 
            AND YEAR(event_date) = :year
        ");
        $stmt->execute([':month' => $month, ':year' => $year]);
    } else {
        $stmt = $db->query("
            SELECT DISTINCT event_date, DAY(event_date) AS day_num 
            FROM bookings 
            WHERE status IN ('confirmed', 'pending')
        ");
    }

    $rows = $stmt->fetchAll();
    $bookedDays = [];
    $bookedDates = [];

    foreach ($rows as $row) {
        $bookedDays[] = (int)$row['day_num'];
        $bookedDates[] = $row['event_date'];
    }

    echo json_encode([
        'success'      => true,
        'booked_days'  => $bookedDays,
        'booked_dates' => $bookedDates
    ]);

} catch (PDOException $e) {
    error_log("Get Booked Dates Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'booked_days' => [], 'booked_dates' => []]);
}
