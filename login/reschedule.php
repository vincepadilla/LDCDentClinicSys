<?php
include_once("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_date = $_POST['new_date'] ?? null;
    $new_time = $_POST['new_time'] ?? null;

    if ($appointment_id && $new_date && $new_time) {
        if ($new_time === '8:00AM-9:00AM') {
            $batch = 'firstBatch';
        } elseif ($new_time === '9:00AM-10:00AM') {
            $batch = 'secondBatch';
        } elseif ($new_time === '10:00AM-11:00AM') {
            $batch = 'thirdBatch';
        } elseif ($new_time === '11:00AM-12:00PM') {
            $batch = 'fourthBatch';
        } elseif ($new_time === '1:00PM-2:00PM') {
            $batch = 'fifthBatch';
        } elseif ($new_time === '2:00PM-3:00PM') {
            $batch = 'sixthBatch';
        } elseif ($new_time === '3:00PM-4:00PM') {
            $batch = 'sevenBatch';
        } elseif ($new_time === '4:00PM-5:00PM') {
            $batch = 'eightBatch';
        } elseif ($new_time === '5:00PM-6:00PM') {
            $batch = 'nineBatch';
        } elseif ($new_time === '6:00PM-7:00PM') {
            $batch = 'tenBatch';
        } elseif ($new_time === '7:00PM-8:00PM') {
            $batch = 'lastBatch';
        } else {
            $batch = null; // Invalid time
        }

        if ($batch !== null) {
            $stmt = $con->prepare("
                UPDATE tbl_appointments 
                SET appointment_date = ?, appointment_time = ?, time_slot = ?, status = 'Pending' 
                WHERE appointment_id = ?
            ");
            $stmt->bind_param("sssi", $new_date, $new_time, $batch, $appointment_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo '<script>
                    alert("Appointment rescheduled successfully!");
                    window.location.href = "account.php?reschedule=success";
                </script>';
            } else {
                echo '<script>
                    alert("Failed to reschedule the appointment. Please try again.");
                    window.location.href = "account.php?reschedule=failed";
                </script>';
            }

            $stmt->close();
        } else {
            echo '<script>
                alert("Invalid time slot selected.");
                window.location.href = "account.php?reschedule=invalid_time";
            </script>';
        }
    } else {
        echo '<script>
            alert("Invalid input. Please fill out all required fields.");
            window.location.href = "account.php?reschedule=invalid";
        </script>';
    }
} else {
    header("Location: account.php");
    exit();
}
?>
