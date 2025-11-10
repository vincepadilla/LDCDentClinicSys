<?php
include_once('config.php');

$date = $_GET['date'] ?? '';
$dentist_id = $_GET['dentist_id'] ?? '';

if (empty($date) || empty($dentist_id)) {
    echo json_encode([]);
    exit;
}

// Get booked and blocked slots from dentist_schedule
$query = "
    SELECT time_slot 
    FROM dentist_schedule 
    WHERE dentist_id = ? AND date = ? AND status IN ('booked', 'blocked')
";
$stmt = $con->prepare($query);
$stmt->bind_param("ss", $dentist_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$blockedSlots = [];
while ($row = $result->fetch_assoc()) {
    $blockedSlots[] = $row['time_slot'];
}

$stmt->close();
$con->close();

echo json_encode($blockedSlots);
?>
