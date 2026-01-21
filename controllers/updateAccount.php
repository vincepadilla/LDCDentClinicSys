<?php
session_start();
include("config.php");

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST values, sanitize
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $address = trim($_POST['address'] ?? '');

    // Update users table
    $stmtUser = $con->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
    $stmtUser->bind_param("ssssi", $first_name, $last_name, $email, $phone, $userID);
    $updateUser = $stmtUser->execute();
    $stmtUser->close();

    // Update tbl_patients table
    $stmtPatient = $con->prepare("UPDATE tbl_patients SET birthdate = ?, gender = ?, address = ?, email = ? WHERE user_id = ?");
    $stmtPatient->bind_param("ssssi", $birthdate, $gender, $address, $email, $userID);
    $updatePatient = $stmtPatient->execute();
    $stmtPatient->close();

    if ($updateUser && $updatePatient) {
        echo "<script>
            alert('Account updated successfully!');
            window.location.href = 'account.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Failed to update account. Please try again.');
            window.location.href = 'account.php';
        </script>";
        exit();
    }
} else {
    header("Location: account.php");
    exit();
}
