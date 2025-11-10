<?php
include_once('config.php'); // Make sure this connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and fetch input
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $specialization = trim($_POST['specialization']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = trim($_POST['status']);

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($specialization) || empty($email) || empty($phone) || empty($status)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit;
    }

    // Insert into the database
    $stmt = $con->prepare("INSERT INTO tbl_dentists (first_name, last_name, specialization, email, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssss", $firstName, $lastName, $specialization, $email, $phone, $status);
        if ($stmt->execute()) {
            echo "<script>alert('Dentist added successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Failed to add dentist.'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Database error.'); window.history.back();</script>";
    }

    $con->close();
}
?>
