<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dentist_id = $_POST['dentist_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    $status = $_POST['status'] ?? ''; // expected values: available | booked | blocked

    if (empty($dentist_id) || empty($date) || empty($time_slot) || empty($status)) {
        echo json_encode(["success" => false, "message" => "Missing required fields."]);
        exit;
    }

    // Validate ENUM values
    $validSlots = [
        'firstBatch','secondBatch','thirdBatch','fourthBatch',
        'fifthBatch','sixthBatch','sevenBatch','eightBatch','nineBatch','tenBatch','lastBatch'
    ];
    $validStatus = ['available', 'booked', 'blocked'];

    if (!in_array($time_slot, $validSlots) || !in_array($status, $validStatus)) {
        echo json_encode(["success" => false, "message" => "Invalid slot or status."]);
        exit;
    }

    // Generate schedule_id (e.g. SCH001)
    $prefix = "SCH";
    $getLast = $con->query("SELECT schedule_id FROM dentist_schedule ORDER BY schedule_id DESC LIMIT 1");
    if ($getLast && $getLast->num_rows > 0) {
        $lastId = $getLast->fetch_assoc()['schedule_id'];
        $num = (int)substr($lastId, 3) + 1;
        $schedule_id = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    } else {
        $schedule_id = "SCH001";
    }

    // Insert or update schedule
    $query = "
        INSERT INTO dentist_schedule (schedule_id, dentist_id, date, time_slot, status)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("sssss", $schedule_id, $dentist_id, $date, $time_slot, $status);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Schedule updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating schedule: " . $stmt->error]);
    }

    $stmt->close();
    $con->close();
}
?>
