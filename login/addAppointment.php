<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PhpMailer/src/Exception.php';
require '../PhpMailer/src/PHPMailer.php';
require '../PhpMailer/src/SMTP.php';

include_once('config.php');


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_POST['userID'];
    $patientName = $_POST['patient_name'];
    $service = $_POST['needServices'];
    $appointmentDate = $_POST['appointment_date'];
    $timeSlot = $_POST['appointment_time'];
    $dentist = $_POST['dentistSelect'];

    // Map time slot batch to actual time range
    $timeMap = [
        "firstBatch" => "8:00AM-9:00AM",
        "secondBatch" => "9:00AM-10:00AM",
        "thirdBatch" => "10:00AM-11:00AM",
        "fourthBatch" => "11:00AM-12:00PM",
        "fifthBatch" => "1:00PM-2:00PM",
        "sixthBatch" => "2:00PM-3:00PM",
        "sevenBatch" => "3:00PM-4:00PM",
        "eightBatch" => "4:00PM-5:00PM",
        "nineBatch" => "5:00PM-6:00PM",
        "tenBatch" => "6:00PM-7:00PM",
        "lastBatch" => "7:00PM-8:00PM"
    ];

    $appointmentTime = isset($timeMap[$timeSlot]) ? $timeMap[$timeSlot] : "Unknown";

    // Insert into database
    $insert = $con->prepare("INSERT INTO tbl_appointments (userID, patient_name, service, dentist, appointment_date, appointment_time, time_slot, status, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $insert->bind_param("issssss", $userID, $patientName, $service, $dentist, $appointmentDate, $appointmentTime, $timeSlot);

    if ($insert->execute()) {

        // Get user's email
        $stmt = $con->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();

        // Send email
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'vincehenrick.padilla0712@gmail.com'; 
            $mail->Password = 'xazs imyr lepb yjuq'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your_email@gmail.com', 'Dental Clinic');
            $mail->addAddress($email, $patientName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Confirmation';
            $mail->Body    = "
                <h3>Hello, $patientName!</h3>
                <p>Your appointment has been scheduled with the following details:</p>
                <ul>
                    <li><strong>Service:</strong> $service</li>
                    <li><strong>Date:</strong> $appointmentDate</li>
                    <li><strong>Time:</strong> $appointmentTime</li>
                    <li><strong>Dentist:</strong> $dentist</li>
                </ul>
                <p>Please confirm to your account and arrive 15 minutes early. Thank you!</p>
            ";

            $mail->send();

            echo "<script>alert('Appointment added and email sent successfully.'); window.location.href='admin.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Appointment added but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='admin.php';</script>";
        }

    } else {
        echo "<script>alert('Error adding appointment.'); window.history.back();</script>";
    }

    $insert->close();
}
?>
