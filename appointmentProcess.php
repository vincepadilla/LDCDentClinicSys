<?php
session_start();
include_once('./login/config.php');

// Function to generate new prefixed ID
function generateID($prefix, $table, $column, $con) {
    $query = "SELECT $column FROM $table ORDER BY $column DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $lastNum = intval(substr($row[$column], strlen($prefix))) + 1;
    } else {
        $lastNum = 1;
    }
    return $prefix . str_pad($lastNum, 3, '0', STR_PAD_LEFT);
}

// Function to show success notification with check animation
function showSuccessNotificationPage($title, $message, $appointmentId = '', $redirectUrl = '../login/account.php', $delay = 3000) {
    $appointmentIdHtml = $appointmentId ? "<span class='appointment-id'>$appointmentId</span>" : '';
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Appointment Booked</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
                overflow: hidden;
            }
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
                backdrop-filter: blur(4px);
            }
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }
            .notification-container {
                position: relative;
                z-index: 10001;
            }
            .notification {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 25px;
                min-width: 450px;
                max-width: 550px;
                text-align: center;
                animation: modalPopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
                border: none;
                position: relative;
            }
            @keyframes modalPopIn {
                0% {
                    transform: scale(0.7) translateY(-50px);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.05) translateY(0);
                }
                100% {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }
            .notification-icon {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                animation: successScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
                box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
                margin-bottom: 10px;
            }
            @keyframes successScale {
                0% {
                    transform: scale(0) rotate(-180deg);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.15) rotate(10deg);
                }
                100% {
                    transform: scale(1) rotate(0deg);
                    opacity: 1;
                }
            }
            @keyframes checkmark {
                0% {
                    stroke-dashoffset: 100;
                    opacity: 0;
                }
                50% {
                    opacity: 1;
                }
                100% {
                    stroke-dashoffset: 0;
                }
            }
            .check-animation {
                stroke-dasharray: 100;
                stroke-dashoffset: 100;
                animation: checkmark 0.8s ease-out forwards;
                animation-delay: 0.2s;
            }
            .notification-content {
                flex: 1;
                width: 100%;
            }
            .notification-title {
                font-weight: 700;
                font-size: 26px;
                margin: 0 0 15px 0;
                color: #111827;
                line-height: 1.3;
            }
            .notification-message {
                font-size: 16px;
                color: #6B7280;
                margin: 0 0 15px 0;
                line-height: 1.7;
            }
            .appointment-id {
                font-weight: 700;
                color: #10B981;
                font-size: 22px;
                display: inline-block;
                margin-top: 10px;
                padding: 8px 16px;
                background: rgba(16, 185, 129, 0.1);
                border-radius: 8px;
            }
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                backdrop-filter: blur(4px);
            }
            .loading-content {
                background: white;
                padding: 30px 40px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #10B981;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .loading-text {
                color: #6B7280;
                font-size: 14px;
                font-weight: 500;
            }
            @media (max-width: 600px) {
                .notification {
                    min-width: 90%;
                    max-width: 90%;
                    padding: 30px 20px;
                }
                .notification-icon {
                    width: 80px;
                    height: 80px;
                }
                .notification-title {
                    font-size: 22px;
                }
                .notification-message {
                    font-size: 14px;
                }
                .appointment-id {
                    font-size: 18px;
                }
            }
        </style>
    </head>
    <body>
        <div class='loading-overlay' id='loadingOverlay'>
            <div class='loading-content'>
                <div class='spinner'></div>
                <div class='loading-text'>Redirecting...</div>
            </div>
        </div>
        <script>
            function showSuccessNotification(title, message, appointmentId) {
                // Create modal overlay
                const modalOverlay = document.createElement('div');
                modalOverlay.className = 'modal-overlay';
                
                // Create notification container
                const container = document.createElement('div');
                container.className = 'notification-container';
                
                const notification = document.createElement('div');
                notification.className = 'notification';
                
                const appointmentIdHtml = appointmentId ? 
                    '<div style=\"margin-top: 15px;\">Your Appointment ID: <span class=\"appointment-id\">' + appointmentId + '</span></div>' : '';
                
                notification.innerHTML = `
                    <div class='notification-icon'>
                        <svg width='55' height='55' viewBox='0 0 24 24' fill='none' stroke='#10B981' stroke-width='3'>
                            <path d='M5 13l4 4L19 7' class='check-animation' stroke-linecap='round' stroke-linejoin='round'/>
                        </svg>
                    </div>
                    <div class='notification-content'>
                        <div class='notification-title'>" . addslashes(htmlspecialchars($title)) . "</div>
                        <div class='notification-message'>" . addslashes(htmlspecialchars($message)) . "</div>
                        " . $appointmentIdHtml . "
                    </div>
                `;
                
                container.appendChild(notification);
                modalOverlay.appendChild(container);
                document.body.appendChild(modalOverlay);
                
                // Show loading overlay and redirect after delay
                setTimeout(() => {
                    modalOverlay.style.animation = 'fadeIn 0.3s ease-out reverse';
                    setTimeout(() => {
                        document.getElementById('loadingOverlay').style.display = 'flex';
                        setTimeout(() => {
                            window.location.href = '$redirectUrl';
                        }, 500);
                    }, 300);
                }, $delay);
            }
            
            // Show notification when page loads
            window.addEventListener('DOMContentLoaded', function() {
                showSuccessNotification(" . json_encode($title) . ", " . json_encode($message) . ", " . json_encode($appointmentId) . ");
            });
        </script>
    </body>
    </html>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['userID'])) {
        echo "<script>alert('Please login to book an appointment');
        window.location.href='login.php';</script>";
        exit();
    }

    $userID = $_SESSION['userID']; // e.g., U001

    // Personal Info
    $fname = mysqli_real_escape_string($con, trim($_POST['fname']));
    $lname = mysqli_real_escape_string($con, trim($_POST['lname']));
    $age = (int)$_POST['age'];
    $birthdate = mysqli_real_escape_string($con, trim($_POST['birthdate']));
    $gender = mysqli_real_escape_string($con, trim($_POST['gender']));
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $phone = mysqli_real_escape_string($con, trim($_POST['phone']));

    // Address
    $address = mysqli_real_escape_string($con, trim($_POST['address']));
    // Appointment Details
    $service_id = mysqli_real_escape_string($con, trim($_POST['service_id']));
    $subService = mysqli_real_escape_string($con, trim($_POST['subService']));
    $subService_id = mysqli_real_escape_string($con, trim($_POST['subservice_id']));

    $team_id = mysqli_real_escape_string($con, trim($_POST['team_id'] ?? 'T001')); 
    $date = mysqli_real_escape_string($con, trim($_POST['date']));
    $time_slot = mysqli_real_escape_string($con, trim($_POST['time']));
    $branch = mysqli_real_escape_string($con, trim($_POST['branch']));

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
    $time = $timeMap[$time_slot] ?? '';

    // Payment Details
    $paymentMethod = mysqli_real_escape_string($con, trim($_POST['paymentMethod']));
    $paymentNumber = '';
    $paymentAmount = 0;
    $paymentRefNum = '';
    $paymentAccName = '';

    if ($paymentMethod == 'GCash') {
        $paymentAccName = mysqli_real_escape_string($con, trim($_POST['gcashaccName']));
        $paymentNumber = mysqli_real_escape_string($con, trim($_POST['gcashNum']));
        $paymentAmount = (float)$_POST['gcashAmount'];
        $paymentRefNum = mysqli_real_escape_string($con, trim($_POST['gcashrefNum']));
    } elseif ($paymentMethod == 'PayMaya') {
        $paymentAccName = mysqli_real_escape_string($con, trim($_POST['mayaaccName']));
        $paymentNumber = mysqli_real_escape_string($con, trim($_POST['mayaNum']));
        $paymentAmount = (float)$_POST['mayaAmount'];
        $paymentRefNum = mysqli_real_escape_string($con, trim($_POST['mayarefNum']));
    } elseif ($paymentMethod == 'Cash') {
        // For cash payments, amount is the consultation fee
        $paymentAmount = 500;
    }

    // Handle Proof Image (not required for Cash payments)
    $proofImagePath = '';
    $isCashPayment = ($paymentMethod == 'Cash');
    
    if (!$isCashPayment) {
        $proofField = $paymentMethod == 'GCash' ? 'proofImage' : 'proofImageMaya';

        if (isset($_FILES[$proofField]) && $_FILES[$proofField]['error'] == UPLOAD_ERR_OK) {
            $img = $_FILES[$proofField];
            $imgName = basename($img['name']);
            $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($imgExt, $allowed)) {
                $safeName = uniqid() . "_" . preg_replace("/[^A-Za-z0-9_\-\.]/", '_', $imgName);
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $proofImagePath = $uploadDir . $safeName;
                move_uploaded_file($img['tmp_name'], $proofImagePath);
            } else {
                echo "<script>alert('Invalid file type for proof image.');
                window.location.href='index.php#appointment';</script>";
                exit();
            }
        }
    }

    // Validation (proof image not required for Cash payments)
    if (empty($fname) || empty($lname) || empty($gender) || empty($email) || empty($phone) ||
        empty($address) || empty($date) || empty($time) || empty($service_id) || empty($subService) 
        || empty($paymentMethod)) {
        echo "<script>alert('All required fields must be filled');</script>";
        exit();
    }
    
    // For non-cash payments, proof image is required
    if (!$isCashPayment && empty($proofImagePath)) {
        echo "<script>alert('Please upload payment proof image.');</script>";
        exit();
    }
    
    // Final safety check: Verify clinic closure status (validation should have been done in payment.php, but this is a security measure)
    $clinicClosed = false;
    $checkTable = "SHOW TABLES LIKE 'clinic_closures'";
    $tableExists = mysqli_query($con, $checkTable);
    
    if ($tableExists && mysqli_num_rows($tableExists) > 0) {
        $closureQuery = "SELECT closure_type, reason FROM clinic_closures WHERE closure_date = ? AND status = 'active' LIMIT 1";
        $closureStmt = $con->prepare($closureQuery);
        if ($closureStmt) {
            $closureStmt->bind_param("s", $date);
            $closureStmt->execute();
            $closureResult = $closureStmt->get_result();
            
            if ($closureRow = $closureResult->fetch_assoc()) {
                if ($closureRow['closure_type'] === 'full_day') {
                    $clinicClosed = true;
                }
            }
            $closureStmt->close();
        }
    }
    
    // Final safety check: Verify time slot is not blocked
    $slotBlocked = false;
    $blockedSlotQuery = "SELECT block_id FROM blocked_time_slots WHERE date = ? AND time_slot = ? LIMIT 1";
    $blockedStmt = $con->prepare($blockedSlotQuery);
    if ($blockedStmt) {
        $blockedStmt->bind_param("ss", $date, $time_slot);
        $blockedStmt->execute();
        $blockedResult = $blockedStmt->get_result();
        $slotBlocked = ($blockedResult->num_rows > 0);
        $blockedStmt->close();
    }
    
    // If validation fails (should not happen if payment.php validation worked, but safety check)
    if ($clinicClosed || $slotBlocked) {
        echo "<script>
            alert('Appointment booking failed: The selected date or time slot is no longer available. Please select another appointment.');
            window.location.href='index.php';
        </script>";
        exit();
    }

    // === CHECK IF PATIENT EXISTS ===
    $userID_escaped_check = mysqli_real_escape_string($con, $userID);
    $checkPatientQuery = "SELECT patient_id FROM patient_information WHERE user_id = '$userID_escaped_check' LIMIT 1";
    $checkPatientResult = mysqli_query($con, $checkPatientQuery);
    $existingPatient = mysqli_fetch_assoc($checkPatientResult);
    $isExistingPatient = !empty($existingPatient);

    if ($isExistingPatient) {
        // Patient already exists, use existing patient_id
        $patient_id = $existingPatient['patient_id'];
        
        // Update patient information in case details changed
        $updatePatient = "UPDATE patient_information 
            SET first_name = '$fname', 
                last_name = '$lname', 
                birthdate = '$birthdate', 
                gender = '$gender', 
                phone = '$phone', 
                email = '$email', 
                address = '$address' 
            WHERE patient_id = '$patient_id'";
        
        $patientInsertSuccess = mysqli_query($con, $updatePatient);
    } else {
        // Patient doesn't exist, create new patient record
        $patient_id = generateID('P', 'patient_information', 'patient_id', $con);
        $insertPatient = "INSERT INTO patient_information 
            (patient_id, user_id, first_name, last_name, birthdate, gender, phone, email, address) 
            VALUES 
            ('$patient_id', '$userID', '$fname', '$lname', '$birthdate', '$gender', '$phone', '$email', '$address')";
        
        $patientInsertSuccess = mysqli_query($con, $insertPatient);
    }

    if ($patientInsertSuccess) {
        // === APPOINTMENT INSERT ===
        // For Cash payments, create appointment with "Pending" status but marked as cash reservation
        // The appointment will remain "Pending" until cash payment is confirmed at branch
        // For GCash/PayMaya, create appointment with "Pending" status (normal flow)
        $appointment_id = generateID('A', 'appointments', 'appointment_id', $con);
        $appointmentStatus = 'Pending'; // Use Pending for both, but cash requires branch payment confirmation
        
        $insertAppointment = "INSERT INTO appointments 
            (appointment_id, patient_id, team_id, service_id, branch, appointment_date, appointment_time, time_slot, status)
            VALUES 
            ('$appointment_id', '$patient_id', '$team_id', '$service_id', '$branch', '$date', '$time', '$time_slot', '$appointmentStatus')";

        $appointmentInserted = mysqli_query($con, $insertAppointment);
        
        if (!$appointmentInserted) {
            error_log('Appointment error: ' . mysqli_error($con));
            echo "<script>alert('Error booking appointment. Please try again.');
            window.location.href='index.php#appointment';</script>";
            exit();
        }

        // === PAYMENT INSERT ===
        if ($isCashPayment) {
            // For Cash: Create payment record linked to reserved appointment
            // Appointment status is "Reserved" - will be changed to "Pending" when payment is confirmed
            $payment_id = generateID('PY', 'payment', 'payment_id', $con);
            $insertPayment = "INSERT INTO payment 
                (payment_id, appointment_id, method, account_name, account_number, amount, reference_no, proof_image, status)
                VALUES 
                ('$payment_id', '$appointment_id', '$paymentMethod', '', '', '$paymentAmount', '', '', 'pending')";

            if (mysqli_query($con, $insertPayment)) {
                // Notification for cash reservation
                if (!empty($userID)) {
                    $getDentistQuery = "SELECT first_name, last_name FROM multidisciplinary_dental_team WHERE team_id = '$team_id'";
                    $dentistResult = mysqli_query($con, $getDentistQuery);
                    $dentistRow = mysqli_fetch_assoc($dentistResult);
                    $dentistName = 'Dr. ' . ($dentistRow['first_name'] ?? '') . ' ' . ($dentistRow['last_name'] ?? '');
                    $dentistName = mysqli_real_escape_string($con, trim($dentistName));
                    
                    $notification_id = generateID('N', 'notifications', 'notification_id', $con);
                    $userID_escaped = mysqli_real_escape_string($con, $userID);
                    $date_escaped = mysqli_real_escape_string($con, $date);
                    $time_escaped = mysqli_real_escape_string($con, $time);
                    
                    $insertNotification = "INSERT INTO notifications 
                        (notification_id, user_id, type, appointment_date, appointment_time, dentist_name, is_read, created_at)
                        VALUES 
                        ('$notification_id', '$userID_escaped', 'reserve', '$date_escaped', '$time_escaped', '$dentistName', 0, NOW())";
                    
                    mysqli_query($con, $insertNotification);
                }
                
                // Check if appointment is tomorrow
                $appointmentDate = new DateTime($date);
                $today = new DateTime('today');
                $tomorrow = clone $today;
                $tomorrow->modify('+1 day');
                $isTomorrow = ($appointmentDate->format('Y-m-d') === $tomorrow->format('Y-m-d'));
                
                // For tomorrow appointments with cash: require immediate payment
                // For other appointments with cash: maintain 2-day deadline
                if ($isTomorrow) {
                    $todayFormatted = $today->format('F j, Y');
                    showSuccessNotificationPage(
                        'Appointment Slot Reserved for Tomorrow!',
                        "IMPORTANT: You must pay TODAY ($todayFormatted) at the branch, otherwise your reservation will be cancelled.",
                        $appointment_id,
                        '../login/account.php',
                        4000
                    );
                } else {
                    // Calculate deadline date (2 days before appointment)
                    $deadlineDate = clone $appointmentDate;
                    $deadlineDate->modify('-2 days');
                    $deadlineFormatted = $deadlineDate->format('F j, Y');
                    
                    showSuccessNotificationPage(
                        'Appointment Slot Reserved!',
                        "Please pay at least 2 days before your appointment date ($date) at the branch. Deadline: $deadlineFormatted",
                        $appointment_id,
                        '../login/account.php',
                        4000
                    );
                }
            } else {
                error_log('Payment error: ' . mysqli_error($con));
                echo "<script>alert('Error saving reservation. Try again.');
                window.location.href='index.php#appointment';</script>";
            }
        } else {
            // For GCash and PayMaya: Normal flow with appointment
            $payment_id = generateID('PY', 'payment', 'payment_id', $con);
            $insertPayment = "INSERT INTO payment 
                (payment_id, appointment_id, method, account_name, account_number, amount, reference_no, proof_image, status)
                VALUES 
                ('$payment_id', '$appointment_id', '$paymentMethod', '$paymentAccName', '$paymentNumber', '$paymentAmount', '$paymentRefNum', '$proofImagePath', 'pending')";

            if (mysqli_query($con, $insertPayment)) {
                // === NOTIFICATION INSERT ===
                if (!empty($userID)) {
                    $getDentistQuery = "SELECT first_name, last_name FROM multidisciplinary_dental_team WHERE team_id = '$team_id'";
                    $dentistResult = mysqli_query($con, $getDentistQuery);
                    $dentistRow = mysqli_fetch_assoc($dentistResult);
                    $dentistName = 'Dr. ' . ($dentistRow['first_name'] ?? '') . ' ' . ($dentistRow['last_name'] ?? '');
                    $dentistName = mysqli_real_escape_string($con, trim($dentistName));
                    
                    $notification_id = generateID('N', 'notifications', 'notification_id', $con);
                    $userID_escaped = mysqli_real_escape_string($con, $userID);
                    $date_escaped = mysqli_real_escape_string($con, $date);
                    $time_escaped = mysqli_real_escape_string($con, $time);
                    
                    $insertNotification = "INSERT INTO notifications 
                        (notification_id, user_id, type, appointment_date, appointment_time, dentist_name, is_read, created_at)
                        VALUES 
                        ('$notification_id', '$userID_escaped', 'booked', '$date_escaped', '$time_escaped', '$dentistName', 0, NOW())";
                    
                    mysqli_query($con, $insertNotification);
                }
                
                // Show success notification with check animation
                showSuccessNotificationPage(
                    'Appointment Successfully Booked!',
                    'Your appointment has been confirmed and is pending payment verification.',
                    $appointment_id,
                    '../login/account.php',
                    3000
                );
            } else {
                error_log('Payment error: ' . mysqli_error($con));
                echo "<script>alert('Error saving payment. Try again.');
                window.location.href='index.php#appointment';</script>";
            }
        }
    } else {
        error_log('Patient error: ' . mysqli_error($con));
        $errorMsg = $isExistingPatient ? 'Error updating patient info. Try again.' : 'Error saving patient info. Try again.';
        echo "<script>alert('$errorMsg');
        window.location.href='index.php#appointment';</script>";
    }
} else {
    header("Location: index.php");
    exit();
}

mysqli_close($con);
?>
