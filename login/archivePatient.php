<?php
session_start();
include_once('config.php');

// Check if user is logged in (admin)
if (!isset($_SESSION['valid']) || $_SESSION['valid'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;

    // Validate patient ID
    if ($patient_id <= 0) {
        echo "<script>
                alert('Invalid patient ID.');
                window.location.href = 'admin.php';
              </script>";
        exit();
    }

    // Prepare and execute delete query for patient_information
    $stmt = $con->prepare("DELETE FROM patient_information WHERE patient_id = ?");
    
    if (!$stmt) {
        echo "<script>
                alert('Database error: Failed to prepare statement. " . addslashes($con->error) . "');
                window.location.href = 'admin.php';
              </script>";
        exit();
    }

    $stmt->bind_param("i", $patient_id);

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>
                alert('Patient archived successfully!');
                window.location.href = 'admin.php';
              </script>";
        exit();
    } else {
        $error = $stmt->error;
        $stmt->close();
        echo "<script>
                alert('Error archiving patient: " . addslashes($error) . "');
                window.location.href = 'admin.php';
              </script>";
        exit();
    }
} else {
    echo "<script>
            alert('Invalid request.');
            window.location.href = 'admin.php';
          </script>";
    exit();
}
?>

