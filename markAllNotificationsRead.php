<?php
session_start();
include_once('./login/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['valid']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id); // Changed from "i" to "s" for VARCHAR

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error']);
}
?>