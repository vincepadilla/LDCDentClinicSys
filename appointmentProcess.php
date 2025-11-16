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
    }

    // Handle Proof Image
    $proofImagePath = '';
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

    // Validation
    if (empty($fname) || empty($lname) || empty($gender) || empty($email) || empty($phone) ||
        empty($address) || empty($date) || empty($time) || empty($service_id) || empty($subService) 
        || empty($paymentMethod) || empty($proofImagePath)) {
        echo "<script>alert('All required fields must be filled');</script>";
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
        $appointment_id = generateID('A', 'appointments', 'appointment_id', $con);
        $insertAppointment = "INSERT INTO appointments 
            (appointment_id, patient_id, team_id, service_id, branch, appointment_date, appointment_time, time_slot, status)
            VALUES 
            ('$appointment_id', '$patient_id', '$team_id', '$service_id', '$branch', '$date', '$time', '$time_slot', 'Pending')";

        if (mysqli_query($con, $insertAppointment)) {
            // === PAYMENT INSERT ===
            $payment_id = generateID('PY', 'payment', 'payment_id', $con);
            $insertPayment = "INSERT INTO payment 
                (payment_id, appointment_id, method, account_name, account_number, amount, reference_no, proof_image, status)
                VALUES 
                ('$payment_id', '$appointment_id', '$paymentMethod', '$paymentAccName', '$paymentNumber', '$paymentAmount', '$paymentRefNum', '$proofImagePath', 'Pending')";

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
                
                echo "<script>alert('Appointment Successfully Booked! Your Appointment ID: $appointment_id');
                window.location.href='../login/account.php';</script>";
            } else {
                error_log('Payment error: ' . mysqli_error($con));
                echo "<script>alert('Error saving payment. Try again.');
                window.location.href='index.php#appointment';</script>";
            }
        } else {
            error_log('Appointment error: ' . mysqli_error($con));
            echo "<script>alert('Error booking appointment. Please try again.');
            window.location.href='index.php#appointment';</script>";
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
