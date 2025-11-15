<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PhpMailer/src/Exception.php';
require '../PhpMailer/src/PHPMailer.php';
require '../PhpMailer/src/SMTP.php';

include_once("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    // Get appointment details with joins to patient_information, services, and multidisciplinary_dental_team
    $stmt = $con->prepare("SELECT a.*, 
                                   p.first_name, p.last_name, p.email,
                                   s.service_category, s.sub_service,
                                   d.first_name as dentist_first, d.last_name as dentist_last
                           FROM appointments a 
                           LEFT JOIN patient_information p ON a.patient_id = p.patient_id 
                           LEFT JOIN services s ON a.service_id = s.service_id
                           LEFT JOIN multidisciplinary_dental_team d ON a.team_id = d.team_id
                           WHERE a.appointment_id = ?");
    $stmt->bind_param("s", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();

    if ($appointment) {
        // Update status to Confirmed
        $stmtUpdate = $con->prepare("UPDATE appointments SET status = 'Confirmed' WHERE appointment_id = ?");
        $stmtUpdate->bind_param("s", $appointment_id);

        if ($stmtUpdate->execute()) {
            // Prepare patient name and service details
            $patient_name = trim($appointment['first_name'] . ' ' . $appointment['last_name']);
            $service = !empty($appointment['sub_service']) ? $appointment['sub_service'] : $appointment['service_category'];
            $dentist = trim($appointment['dentist_first'] . ' ' . $appointment['dentist_last']);
            $email = $appointment['email'];
            
            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'padillavincehenrick@gmail.com';
                $mail->Password = 'glxd csoa ispj bvjg'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('padillavincehenrick@gmail.com', 'Landero Dental Clinic');
                $mail->addAddress($email, $patient_name);
                $mail->isHTML(true);
                $mail->Subject = 'Appointment Confirmed';

                $mail->Body = "
                    <h3>Hi {$patient_name},</h3>
                    <p>Your appointment has been <strong>confirmed</strong>.</p>
                    <p><strong>Service:</strong> {$service}<br>
                    <strong>Dentist:</strong> {$dentist}<br>
                    <strong>Date:</strong> " . date('F j, Y', strtotime($appointment['appointment_date'])) . "<br>
                    <strong>Time:</strong> {$appointment['appointment_time']}<br>
                    <strong>Branch:</strong> {$appointment['branch']}</p>
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
