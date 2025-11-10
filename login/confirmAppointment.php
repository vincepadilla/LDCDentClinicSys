<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PhpMailer/src/Exception.php';
require '../PhpMailer/src/PHPMailer.php';
require '../PhpMailer/src/SMTP.php';

include_once("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);

    $stmt = $con->prepare("SELECT a.*, u.email 
                           FROM tbl_appointments a 
                           JOIN users u ON a.userID = u.user_id 
                           WHERE a.appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();

    if ($appointment) {
        // Update status to Confirmed
        $stmtUpdate = $con->prepare("UPDATE tbl_appointments SET status = 'Confirmed' WHERE appointment_id = ?");
        $stmtUpdate->bind_param("i", $appointment_id);

        if ($stmtUpdate->execute()) {
            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'vincehenrick.padilla0712@gmail.com';
                $mail->Password = 'xazs imyr lepb yjuq'; 
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('your-email@gmail.com', 'Dental Buddies Clinic');
                $mail->addAddress($appointment['email'], $appointment['patient_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Appointment Confirmed';

                $mail->Body = "
                    <h3>Hi {$appointment['patient_name']},</h3>
                    <p>Your appointment has been <strong>confirmed</strong>.</p>
                    <p><strong>Service:</strong> {$appointment['service']}<br>
                    <strong>Dentist:</strong> {$appointment['dentist']}<br>
                    <strong>Date:</strong> {$appointment['appointment_date']}<br>
                    <strong>Time:</strong> {$appointment['appointment_time']}</p>
                    <p>Thank you for choosing our clinic!</p>
                ";

                $mail->send();

                echo '<script>
                    alert("Appointment confirmed and email sent.");
                    window.location.href = "admin.php?message=Appointment+confirmed";
                </script>';
                exit();
            } catch (Exception $e) {
                echo '<script>
                    alert("Appointment confirmed, but email failed to send. Error: ' . $mail->ErrorInfo . '");
                    window.location.href = "admin.php";
                </script>';
                exit();
            }
        } else {
            echo "Error updating appointment: " . $stmtUpdate->error;
        }

        $stmtUpdate->close();
    } else {
        echo "Appointment not found.";
    }

    $con->close();
} else {
    header("Location: admin.php");
    exit();
}
