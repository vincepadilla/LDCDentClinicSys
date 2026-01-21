<?php
session_start();
include_once("config.php");

// Check if user is admin
if (!isset($_SESSION['userID']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../views/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin.php");
    exit();
}

// Get form data
$appointment_id = mysqli_real_escape_string($con, trim($_POST['original_appointment_id'] ?? ''));
$appointment_date = mysqli_real_escape_string($con, trim($_POST['appointment_date'] ?? ''));
$time_slot = mysqli_real_escape_string($con, trim($_POST['time_slot'] ?? ''));

// Validate required fields
if (empty($appointment_id) || empty($appointment_date) || empty($time_slot)) {
    $_SESSION['error_message'] = "All fields are required.";
    header("Location: admin.php#appointment");
    exit();
}

// Map time slot to actual time
$timeMap = [
    'firstBatch' => '8:00AM-9:00AM',
    'secondBatch' => '9:00AM-10:00AM',
    'thirdBatch' => '10:00AM-11:00AM',
    'fourthBatch' => '11:00AM-12:00PM',
    'fifthBatch' => '1:00PM-2:00PM',
    'sixthBatch' => '2:00PM-3:00PM',
    'sevenBatch' => '3:00PM-4:00PM',
    'eightBatch' => '4:00PM-5:00PM',
    'nineBatch' => '5:00PM-6:00PM',
    'tenBatch' => '6:00PM-7:00PM',
    'lastBatch' => '7:00PM-8:00PM'
];

$appointment_time = $timeMap[$time_slot] ?? '';

if (empty($appointment_time)) {
    $_SESSION['error_message'] = "Invalid time slot selected.";
    header("Location: admin.php#appointment");
    exit();
}

// Update the existing appointment status to 'Follow-Up' and update date/time
$updateQuery = "UPDATE appointments 
    SET appointment_date = '$appointment_date', 
        appointment_time = '$appointment_time', 
        time_slot = '$time_slot', 
        status = 'Follow-Up' 
    WHERE appointment_id = '$appointment_id'";

if (mysqli_query($con, $updateQuery)) {
    $_SESSION['success_message'] = "Appointment updated to Follow-Up successfully!";
} else {
    $_SESSION['error_message'] = "Error updating appointment: " . mysqli_error($con);
}

header("Location: admin.php#appointment");
exit();

