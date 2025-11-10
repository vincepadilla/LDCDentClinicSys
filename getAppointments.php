<?php
include_once('./login/config.php');

$date = $_GET['date'] ?? '';

if (!$date) {
    echo json_encode([]);
    exit;
}

// Query appointments for that date, fetch booked time slots
$query = "SELECT time_slot FROM appointments WHERE appointment_date = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookedSlots = [];
while ($row = $result->fetch_assoc()) {
    $bookedSlots[] = $row['time_slot'];
}

echo json_encode($bookedSlots);
