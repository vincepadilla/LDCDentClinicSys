<?php
session_start();
include_once('../database/config.php');

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
function showSuccessNotificationPage($title, $message, $appointmentId = '', $redirectUrl = '../views/account.php', $delay = 3000) {
    $appointmentIdHtml = $appointmentId ? "<span class='appointment-id'>$appointmentId</span>" : '';
    $redirectJson = json_encode($redirectUrl);
    $titleJson = json_encode($title);
    $messageJson = json_encode($message);
    $appointmentIdJson = json_encode($appointmentId);
    $delayInt = intval($delay);

    echo <<<HTML
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Appointment Booked</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap' rel='stylesheet'>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; }
        .modal-overlay { position: fixed; inset: 0; display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.45); z-index:10000; opacity:0; transition:opacity 180ms ease-in; }
        .modal-overlay.visible { opacity: 1; }
        .notification { background:#fff; border-radius:12px; padding:24px; width:420px; max-width:92%; text-align:center; box-shadow:0 8px 24px rgba(0,0,0,0.12); }
        .notification-icon { margin-bottom:12px; }
        @keyframes check-draw { from{ stroke-dashoffset:100 } to{ stroke-dashoffset:0 } }
        .check-animation { stroke-dasharray:100; stroke-dashoffset:100; animation:check-draw 360ms ease-out forwards; }
        .notification-title { font-weight:700; font-size:20px; margin-bottom:6px; }
        .notification-message { color:#6B7280; font-size:14px; margin-bottom:8px; }
        .appointment-id { display:inline-block; margin-top:8px; padding:6px 10px; border-radius:6px; background:rgba(16,185,129,0.08); color:#059669; font-weight:600; }
        .loading-overlay{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background: rgba(0,0,0,0.5); z-index:10002 }
        .loading-text{ background:#fff; padding:16px 22px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08) }
    </style>
</head>
<body>
    <div class='loading-overlay' id='loadingOverlay'><div class='loading-text'>Redirecting...</div></div>
    <script>
        const redirectUrl = $redirectJson;
        const notifyDelay = $delayInt;

        function showSuccessNotification(title, message, appointmentId) {
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'modal-overlay';

            const container = document.createElement('div');
            container.className = 'notification-container';

            const notification = document.createElement('div');
            notification.className = 'notification';

            const appointmentIdHtml = appointmentId ? '<div style="margin-top:12px;">Your Appointment ID: <span class="appointment-id">' + appointmentId + '</span></div>' : '';

            notification.innerHTML = '<div class="notification-icon">'
                + '<svg width="55" height="55" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3">'
                + '<path d="M5 13l4 4L19 7" class="check-animation" stroke-linecap="round" stroke-linejoin="round"/>'
                + '</svg></div>'
                + '<div class="notification-content">'
                + '<div class="notification-title">' + title + '</div>'
                + '<div class="notification-message">' + message + '</div>'
                + appointmentIdHtml
                + '</div>';

            container.appendChild(notification);
            modalOverlay.appendChild(container);
            document.body.appendChild(modalOverlay);

            // show overlay
            setTimeout(() => modalOverlay.classList.add('visible'), 10);

            // redirect after delay
            setTimeout(() => {
                document.getElementById('loadingOverlay').style.display = 'flex';
                setTimeout(() => { window.location.href = redirectUrl; }, 450);
            }, notifyDelay);
        }

        window.addEventListener('DOMContentLoaded', function() {
            showSuccessNotification($titleJson, $messageJson, $appointmentIdJson);
        });
    </script>
</body>
</html>
HTML;
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['userID'])) {
        echo "<script>alert('Please login to book an appointment');
        window.location.href='../views/login.php';</script>";
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
                $uploadDir = "../uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $proofImagePath = $uploadDir . $safeName;
                move_uploaded_file($img['tmp_name'], $proofImagePath);
            } else {
                echo "<script>alert('Invalid file type for proof image.');
                window.location.href='../views/index.php#appointment';</script>";
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
            window.location.href='../views/index.php';
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
        // === DOUBLE BOOKING CHECK ===
        // Check if there's already an appointment with the same date, time_slot, and dentist
        // Only allow booking if the existing appointment is Cancelled or No-Show
        // All other statuses (Pending, Confirmed, Reschedule, Completed) will block the slot
        $checkDoubleBooking = "SELECT appointment_id FROM appointments 
                               WHERE appointment_date = ? 
                               AND time_slot = ? 
                               AND team_id = ? 
                               AND status NOT IN ('Cancelled', 'No-Show') 
                               LIMIT 1";
        $checkStmt = $con->prepare($checkDoubleBooking);
        if ($checkStmt) {
            $checkStmt->bind_param("sss", $date, $time_slot, $team_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $existingAppointment = $checkResult->fetch_assoc();
                $checkStmt->close();
                error_log("Double booking prevented: Appointment ID " . $existingAppointment['appointment_id'] . " already exists for date=$date, time_slot=$time_slot, team_id=$team_id");
                echo "<script>
                    alert('This time slot is already booked by another patient. Please select another appointment time.');
                    window.location.href='../views/index.php#appointment';
                </script>";
                exit();
            }
            $checkStmt->close();
        } else {
            // If prepare failed, log error but don't block (fallback to database constraint if exists)
            error_log("Double booking check prepare failed: " . $con->error);
        }
        
        // === APPOINTMENT INSERT ===
        // For Cash payments, create appointment with "Pending" status but marked as cash reservation
        // The appointment will remain "Pending" until cash payment is confirmed at branch
        // For GCash/PayMaya, create appointment with "Pending" status (normal flow)
        $appointment_id = generateID('A', 'appointments', 'appointment_id', $con);
        $appointmentStatus = 'Pending'; // Use Pending for both, cash payment will be verified on arrival

        // If Cash payment: generate alphanumeric ticket code and compute expiry (appointment end + grace)
        $ticketCode = null;
        $ticketExpiresAt = null;
        if ($isCashPayment) {
            $ticketCode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            // Parse end time from appointment time like '9:00AM-10:00AM'
            $endTime = '11:59PM';
            if (strpos($time, '-') !== false) {
                $parts = explode('-', $time);
                $endTime = trim($parts[1]);
            }
            $endDateTime = DateTime::createFromFormat('Y-m-d g:iA', "$date $endTime");
            if (!$endDateTime) {
                $endDateTime = new DateTime($date . ' 23:59:00');
            }
            // Grace period after end time (e.g., 30 minutes)
            $endDateTime->modify('+30 minutes');
            $ticketExpiresAt = $endDateTime->format('Y-m-d H:i:s');
        }

        // Check if the DB has ticket columns (migration may not have been run)
        $colCheck = mysqli_query($con, "SHOW COLUMNS FROM appointments LIKE 'ticket_code'");
        $hasTicketCols = ($colCheck && mysqli_num_rows($colCheck) > 0);

        if ($hasTicketCols) {
            $insertAppointment = "INSERT INTO appointments 
                (appointment_id, patient_id, team_id, service_id, branch, appointment_date, appointment_time, time_slot, status, ticket_code, ticket_expires_at)
                VALUES 
                ('$appointment_id', '$patient_id', '$team_id', '$service_id', '$branch', '$date', '$time', '$time_slot', '$appointmentStatus', " . ($ticketCode ? "'" . $ticketCode . "'" : "NULL") . ", " . ($ticketExpiresAt ? "'" . $ticketExpiresAt . "'" : "NULL") . ")";
        } else {
            // Fallback for older DB without ticket columns
            $insertAppointment = "INSERT INTO appointments 
                (appointment_id, patient_id, team_id, service_id, branch, appointment_date, appointment_time, time_slot, status)
                VALUES 
                ('$appointment_id', '$patient_id', '$team_id', '$service_id', '$branch', '$date', '$time', '$time_slot', '$appointmentStatus')";
        }

        $appointmentInserted = mysqli_query($con, $insertAppointment);
        
        if (!$appointmentInserted) {
            error_log('Appointment error: ' . mysqli_error($con));
            echo "<script>alert('Error booking appointment. Please try again.');
            window.location.href='../views/index.php#appointment';</script>";
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
                // Send ticket email to patient with confirm/cancel links
                try {
                    require_once '../libraries/PhpMailer/src/Exception.php';
                    require_once '../libraries/PhpMailer/src/PHPMailer.php';
                    require_once '../libraries/PhpMailer/src/SMTP.php';
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? '';
                    $baseUrl = $host ? $protocol . '://' . $host : '';
                    $confirmLink = $baseUrl . '/controllers/ticket_action.php?action=confirm&appointment_id=' . urlencode($appointment_id) . '&ticket=' . urlencode($ticketCode);
                    $cancelLink = $baseUrl . '/controllers/ticket_action.php?action=cancel&appointment_id=' . urlencode($appointment_id) . '&ticket=' . urlencode($ticketCode);

                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'padillavincehenrick@gmail.com';
                    $mail->Password = 'glxd csoa ispj bvjg';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('padillavincehenrick@gmail.com', 'Dental Clinic');
                    $mail->addAddress($email, trim($fname . ' ' . $lname));
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Appointment Ticket Code';
                    // Generate QR image for email (QR contains the appointment_id for direct scanner lookup)
                    // Using higher resolution (300x300) for better scanner readability
                    $qrImgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($appointment_id);

                    $mail->Body =
                        "<div style='font-family: Arial, sans-serif; line-height: 1.6; color: #111827;'>" .
                            "<h2 style='margin: 0 0 10px 0;'>Hello " . htmlspecialchars($fname) . "</h2>" .
                            "<p style='margin: 0 0 10px 0;'>Your appointment has been reserved. Your ticket code is: <strong>" . htmlspecialchars($ticketCode) . "</strong></p>" .
                            "<p style='margin: 0 0 14px 0;'><strong>Appointment:</strong> " . htmlspecialchars($date) . " at " . htmlspecialchars($time) . "</p>" .

                            "<div style='margin: 16px 0; padding: 14px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f9fafb; text-align: center;'>" .
                                "<div style='font-weight: 700; margin-bottom: 8px;'>Your QR Code</div>" .
                                "<img src='" . htmlspecialchars($qrImgUrl) . "' alt='Appointment QR Code' style='width: 300px; height: 300px; border: 8px solid #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: block; margin: 0 auto;' />" .
                                "<div style='font-size: 12px; color: #6b7280; margin-top: 10px;'>Show this QR code on your appointment day for scanning and payment.</div>" .
                            "</div>" .

                            "<p style='margin: 0 0 10px 0;'>On your appointment day, the cashier/dentist will scan this QR code in the clinic to verify your ticket and then process payment.</p>" .
                            "<p style='margin: 0 0 10px 0;'>If you will attend, you can confirm now: <a href='" . $confirmLink . "'>Confirm Appointment</a></p>" .
                            "<p style='margin: 0 0 10px 0;'>If you wish to cancel, click: <a href='" . $cancelLink . "'>Cancel Appointment</a></p>" .
                        "</div>";

                    $mail->send();
                } catch (Exception $ex) {
                    error_log('Ticket email send failed: ' . ($mail->ErrorInfo ?? $ex->getMessage()));
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
                        "IMPORTANT: You must pay TODAY ($todayFormatted) at the branch, otherwise your reservation will be cancelled.<br><br>Your Ticket Code: $ticketCode<br>Present this code at reception on your appointment day.",
                        $appointment_id,
                        '../views/account.php',
                        4000
                    );
                } else {
                    // Calculate deadline date (2 days before appointment)
                    $deadlineDate = clone $appointmentDate;
                    $deadlineDate->modify('-2 days');
                    $deadlineFormatted = $deadlineDate->format('F j, Y');
                    
                    showSuccessNotificationPage(
                        'Appointment Slot Reserved!',
                        "Please pay at least 2 days before your appointment date ($date) at the branch. Deadline: $deadlineFormatted<br><br>Your Ticket Code: $ticketCode<br>Present this code at reception on your appointment day.",
                        $appointment_id,
                        '../views/account.php',
                        4000
                    );
                }
            } else {
                error_log('Payment error: ' . mysqli_error($con));
                echo "<script>alert('Error saving reservation. Try again.');
                window.location.href='../views/index.php#appointment';</script>";
            }
        } else {
            // For GCash and PayMaya: Normal flow with appointment
            $payment_id = generateID('PY', 'payment', 'payment_id', $con);
            $insertPayment = "INSERT INTO payment 
                (payment_id, appointment_id, method, account_name, account_number, amount, reference_no, proof_image, status)
                VALUES 
                ('$payment_id', '$appointment_id', '$paymentMethod', '$paymentAccName', '$paymentNumber', '$paymentAmount', '$paymentRefNum', '$proofImagePath', 'pending')";

            if (mysqli_query($con, $insertPayment)) {
                // Show success notification with check animation
                showSuccessNotificationPage(
                    'Appointment Successfully Booked!',
                    'Your appointment has been confirmed and is pending payment verification.',
                    $appointment_id,
                    '../views/account.php',
                    3000
                );
            } else {
                error_log('Payment error: ' . mysqli_error($con));
                echo "<script>alert('Error saving payment. Try again.');
                window.location.href='../views/index.php#appointment';</script>";
            }
        }
    } else {
        error_log('Patient error: ' . mysqli_error($con));
        $errorMsg = $isExistingPatient ? 'Error updating patient info. Try again.' : 'Error saving patient info. Try again.';
        echo "<script>alert('$errorMsg');
        window.location.href='../views/index.php#appointment';</script>";
    }
} else {
    header("Location: ../views/index.php");
    exit();
}

mysqli_close($con);
?>
