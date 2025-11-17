<?php
include_once('./login/config.php');

$date = $_GET['date'] ?? '';

if (!$date) {
    echo json_encode([]);
    exit;
}

// Query appointments for that date, fetch booked time slots
$query = "SELECT time_slot FROM appointments WHERE appointment_date = ? AND status != 'Cancelled'";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookedSlots = [];
while ($row = $result->fetch_assoc()) {
    $bookedSlots[] = $row['time_slot'];
}
$stmt->close();

// Also get blocked time slots for all dentists (blocked slots apply to all dentists)
$blockedQuery = "SELECT DISTINCT time_slot FROM blocked_time_slots WHERE date = ?";
$blockedStmt = $con->prepare($blockedQuery);
$blockedStmt->bind_param("s", $date);
$blockedStmt->execute();
$blockedResult = $blockedStmt->get_result();

$blockedSlots = [];
while ($row = $blockedResult->fetch_assoc()) {
    $blockedSlots[] = $row['time_slot'];
}
$blockedStmt->close();

// Merge booked and blocked slots (remove duplicates)
$unavailableSlots = array_unique(array_merge($bookedSlots, $blockedSlots));

header('Content-Type: application/json');
echo json_encode(array_values($unavailableSlots));
