<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PhpMailer/src/Exception.php';
require '../PhpMailer/src/PHPMailer.php';
require '../PhpMailer/src/SMTP.php';

include_once("config.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_date = $_POST['new_date_resched'] ?? null;
    $new_time_slot = $_POST['new_time_resched'] ?? null;

    $timeMap = [
        'firstBatch'   => '8:00AM-9:00AM',
        'secondBatch'  => '9:00AM-10:00AM',
        'thirdBatch'   => '10:00AM-11:00AM',
        'fourthBatch'  => '11:00AM-12:00PM',
        'fifthBatch'   => '1:00PM-2:00PM',
        'sixthBatch'   => '2:00PM-3:00PM',
        'sevenBatch'   => '3:00PM-4:00PM',
        'eightBatch'   => '4:00PM-5:00PM',
        'nineBatch'    => '5:00PM-6:00PM',
        'tenBatch'     => '6:00PM-7:00PM',
        'lastBatch'    => '7:00PM-8:00PM'
    ];

    if ($appointment_id && $new_date && $new_time_slot && isset($timeMap[$new_time_slot])) {
        $today = date("Y-m-d");
        if ($new_date < $today) {
            echo '<script>
                alert("New date cannot be in the past.");
                window.location.href = "admin.php?reschedule=invalid_date";
            </script>';
            exit();
        }

        $new_time = $timeMap[$new_time_slot];

        // Update appointment in database
        $stmt = $con->prepare("
            UPDATE tbl_appointments 
            SET appointment_date = ?, 
                appointment_time = ?, 
                time_slot = ?, 
                status = 'Reschedule'
            WHERE appointment_id = ?
        ");
        $stmt->bind_param("sssi", $new_date, $new_time, $new_time_slot, $appointment_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Get user email and name
            $infoQuery = $con->prepare("
                SELECT u.email, a.patient_name, a.service, a.dentist 
                FROM tbl_appointments a
                JOIN users u ON a.userID = u.user_id
                WHERE a.appointment_id = ?
            ");
            $infoQuery->bind_param("i", $appointment_id);
            $infoQuery->execute();
            $infoQuery->bind_result($email, $patientName, $service, $dentist);
            $infoQuery->fetch();
            $infoQuery->close();

            // Send reschedule email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'vincehenrick.padilla0712@gmail.com';
                $mail->Password = 'xazs imyr lepb yjuq';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('your_email@gmail.com', 'Dental Clinic');
                $mail->addAddress($email, $patientName);

                $mail->isHTML(true);
                $mail->Subject = 'Appointment Rescheduled';
                $mail->Body = "
                    <h3>Hello, $patientName!</h3>
                    <p>Your appointment has been <strong>rescheduled</strong> with the following details:</p>
                    <ul>
                        <li><strong>Service:</strong> $service</li>
                        <li><strong>New Date:</strong> $new_date</li>
                        <li><strong>New Time:</strong> $new_time</li>
                        <li><strong>Dentist:</strong> $dentist</li>
                    </ul>
                    <p>Please check your account for more details. Thank you!</p>
                ";

                $mail->send();
                echo '<script>
                    alert("Appointment successfully rescheduled and email sent.");
                    window.location.href = "admin.php?reschedule=success";
                </script>';
            } catch (Exception $e) {
                echo "<script>
                    alert('Rescheduled, but email could not be sent. Mailer Error: {$mail->ErrorInfo}');
                    window.location.href = 'admin.php?reschedule=email_failed';
                </script>";
            }

        } else {
            echo '<script>
                alert("No changes were made. The data may be identical or appointment not found.");
                window.location.href = "admin.php?reschedule=failed";
            </script>';
        }

        $stmt->close();
        $con->close();
    } else {
        echo '<script>
            alert("Please complete all required fields.");
            window.location.href = "admin.php?reschedule=missing_fields";
        </script>';
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
