<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    header("Location: login.php");
    exit("You must be logged in to view this page.");
}

define("TITLE", "Payment");
include_once('header.php');

$fname = $lname = $birthdate = $age = $email = $gender = $phone = '';
$address = $service_id = $subService = $branch = $date = $time = '';
$price = 500;

$dentist = 'Dr. Michelle Landero';

$timeRanges = [
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

// Calculate age
if (!empty($birthdate) && $birthdate !== 'N/A') {
    $birthDateObj = new DateTime($birthdate);
    $todayObj = new DateTime();
    $age = $todayObj->diff($birthDateObj)->y;
} else {
    $age = 'N/A';
}

// Override with POST values if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $fname = htmlspecialchars($_POST['fname'] ?? $fname);
    $lname = htmlspecialchars($_POST['lname'] ?? $lname);
    $birthdate = htmlspecialchars($_POST['birthdate'] ?? $birthdate);
    $age = htmlspecialchars($_POST['age'] ?? $age);
    $email = htmlspecialchars($_POST['email'] ?? $email);
    $gender = htmlspecialchars($_POST['gender'] ?? $gender);
    $phone = htmlspecialchars($_POST['phone'] ?? $phone);
    $address = htmlspecialchars($_POST['address'] ?? $address);

    $subService = htmlspecialchars($_POST['sub_service'] ?? 'N/A');

    // Map subService to service_id
    switch ($subService) {
        case 'Cleaning':   $service_id = 'S001'; break;
        case 'Checkups':   $service_id = 'S001'; break;
        case 'Flouride':   $service_id = 'S001'; break;
        case 'Pit & Fissure Sealants':   $service_id = 'S001'; break;
        case 'Tooth Restoration':   $service_id = 'S001'; break;
        case 'Braces':     $service_id = 'S002'; break;
        case 'Retainers':     $service_id = 'S002'; break;
        case 'Tooth Extraction': $service_id = 'S003'; break;
        case 'Root Canal Treatment': $service_id = 'S004'; break;
        case 'Crowns': $service_id = 'S005'; break;
        case 'Dentures': $service_id = 'S005'; break;
        default:           $service_id = 'N/A'; break;
    }

    // Map service_id to service_name
    switch ($service_id) {
        case 'S001': $service_name = 'General Dentistry'; break;
        case 'S002': $service_name = 'Orthodontics'; break;
        case 'S003': $service_name = 'Oral Surgery'; break;
        case 'S004': $service_name = 'Endodontics'; break;
        case 'S005': $service_name = 'Prosthodontics (Pustiso)'; break;
        default:     $service_name = 'Unknown Service'; break;
    }

    $branch = htmlspecialchars($_POST['branch'] ?? 'N/A');
    $date = htmlspecialchars($_POST['date'] ?? 'N/A');
    $time = isset($_POST['time']) && isset($timeRanges[$_POST['time']]) ? $timeRanges[$_POST['time']] : 'N/A';

    // Format branch names
    if (strtolower($branch) === 'comembo') {
        $branch = 'Comembo Branch';
    } elseif (strtolower($branch) === 'taytay') {
        $branch = 'Taytay Rizal Branch';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm & Pay - SmileCare Dental</title>
    <link rel="stylesheet" href="paymentstyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="payment-container">
    <div class="header-section">
        <h1>Complete Your Payment</h1>
        <p>Review your appointment details and proceed with payment.</p>
    </div>

    <form id="paymentForm" action="appointmentProcess.php" method="POST" enctype="multipart/form-data">
        <div class="content-grid">
            <!-- Appointment Summary -->
            <div class="summary-section">
                <div class="section-header">
                    <h2>Appointment Summary</h2>
                    <p>Please verify your appointment details.</p>
                </div>

                <div class="info-section">
                    <h3 class="section-title">A. Patient Information</h3>
                    <div class="patient-details">
                        <div class="patient-row">
                            <div class="patient-label">Full Name:</div>
                            <div class="patient-value"><?= strtoupper("$fname $lname") ?></div>
                        </div>
                        <div class="patient-row">
                            <div class="patient-label">Age:</div>
                            <div class="patient-value"><?= $age ?></div>
                        </div>
                        <div class="patient-row">
                            <div class="patient-label">Gender:</div>
                            <div class="patient-value"><?= strtoupper($gender) ?></div>
                            <input type="hidden" name="address" value="<?= $address ?>">
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title">C. Appointment Details</h3>
                    <div class="appointment-details">
                        <div class="detail-row">
                            <div class="detail-label">Service</div>
                            <div class="detail-value"><?= ucwords($service_name) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Sub-Service</div>
                            <div class="detail-value"><?= ucwords($subService) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Dentist</div>
                            <div class="detail-value"><?= strtoupper($dentist) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Branch</div>
                            <div class="detail-value"><?= strtoupper($branch) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Date</div>
                            <div class="detail-value"><?= date('F j, Y', strtotime($date)) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Time Slot</div>
                            <div class="detail-value"><?= $time ?></div>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="fname" value="<?= $fname ?>">
                <input type="hidden" name="lname" value="<?= $lname ?>">
                <input type="hidden" name="age" value="<?= $age ?>">
                <input type="hidden" name="birthdate" value="<?= $birthdate ?>">
                <input type="hidden" name="gender" value="<?= $gender ?>">
                <input type="hidden" name="email" value="<?= $email ?>">
                <input type="hidden" name="phone" value="<?= $phone ?>">
                <input type="hidden" name="street" value="<?= $address ?>">
                <input type="hidden" name="service_id" value="<?= $service_id ?>">
                <input type="hidden" name="subService" value="<?= $subService ?>">
                <input type="hidden" name="subservice_id" value="<?= $subservice_id ?>">
                <input type="hidden" name="dentist" value="<?= $dentist ?>">
                <input type="hidden" name="branch" value="<?= $branch ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <input type="hidden" name="time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
            </div>

            <!-- Payment Information -->
            <div class="payment-section">
                <div class="section-header">
                    <h2>Payment Information</h2>
                    <p>Complete payment to confirm booking.</p>
                </div>

                <div class="payment-method-section">
                    <h3 class="section-title">Payment Method</h3>
                    
                    <div class="payment-method-selector">
                        <select name="paymentMethod" id="paymentMethod" required>
                            <option value="">Select payment method</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                        </select>
                    </div>

                    <!-- GCash Section -->
                    <div id="gcashDetails" class="payment-details" style="display: none;">
                        <div class="payment-option">
                            <div class="payment-header">
                                <div class="payment-logo">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>GCash</span>
                                </div>
                                <div class="payment-qr">
                                    <p>Scan to Pay via GCash</p>
                                    <div class="qr-code">
                                        <img src="gcash.jpg" alt="GCash QR Code">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="account-info">
                                <p>Or use Account Number:</p>
                                <div class="account-number">09123456789</div>
                            </div>
                            
                            <div class="payment-form">
                                <div class="form-group">
                                    <label for="gcashaccName">Account Name</label>
                                    <input type="text" name="gcashaccName" id="gcashaccName" placeholder="Your Account Name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gcashNum">GCash Number</label>
                                    <input type="text" name="gcashNum" id="gcashNum" placeholder="Your GCash Account Number" maxlength="11" pattern="\d{11}">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gcashAmount">Payment Amount You've Sent</label>
                                    <input type="number" name="gcashAmount" id="gcashAmount" placeholder="Amount Sent" min="500" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gcashrefNum">Reference Number</label>
                                    <input type="text" name="gcashrefNum" id="gcashrefNum" placeholder="Reference No.">
                                </div>
                                
                                <div class="form-group">
                                    <label for="proofImage">Upload Receipt</label>
                                    <div class="file-upload">
                                        <input type="file" name="proofImage" id="proofImage">
                                        <span class="file-text">Choose File No file chosen</span>
                                    </div>
                                </div>
                                
                                <div class="confirmation-checkbox">
                                    <input type="checkbox" id="gcashConfirm" onchange="togglePayButton('gcash')">
                                    <label for="gcashConfirm">I confirm that the above details are correct and I agree to proceed with the payment.</label>
                                </div>
                                
                                <button type="submit" class="pay-button" id="gcashPayBtn" disabled>Pay Now</button>
                            </div>
                        </div>
                    </div>

                    <!-- PayMaya Section -->
                    <div id="mayaDetails" class="payment-details" style="display: none;">
                        <div class="payment-option">
                            <div class="payment-header">
                                <div class="payment-logo">
                                    <i class="fas fa-wallet"></i>
                                    <span>PayMaya</span>
                                </div>
                                <div class="payment-qr">
                                    <p>Scan to Pay via PayMaya</p>
                                    <div class="qr-code">
                                        <img src="maya.png" alt="Maya QR Code">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="account-info">
                                <p>Or use Account Number:</p>
                                <div class="account-number">0915 067 2948</div>
                            </div>
                            
                            <div class="payment-form">
                                <div class="form-group">
                                    <label for="mayaaccName">Account Name</label>
                                    <input type="text" name="mayaaccName" id="mayaaccName" placeholder="Your Account Name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="mayaNum">PayMaya Number</label>
                                    <input type="text" name="mayaNum" id="mayaNum" placeholder="Your PayMaya Account Number">
                                </div>
                                
                                <div class="form-group">
                                    <label for="mayaAmount">Payment Amount You've Sent</label>
                                    <input type="number" name="mayaAmount" id="mayaAmount" placeholder="Amount Sent" min="500" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label for="mayarefNum">Reference Number</label>
                                    <input type="text" name="mayarefNum" id="mayarefNum" placeholder="Reference No.">
                                </div>
                                
                                <div class="form-group">
                                    <label for="proofImageMaya">Upload Receipt</label>
                                    <div class="file-upload">
                                        <input type="file" name="proofImageMaya" id="proofImageMaya">
                                        <span class="file-text">Choose File No file chosen</span>
                                    </div>
                                </div>
                                
                                <div class="confirmation-checkbox">
                                    <input type="checkbox" id="mayaConfirm" onchange="togglePayButton('maya')">
                                    <label for="mayaConfirm">I confirm that the above details are correct and I agree to proceed with the payment.</label>
                                </div>
                                
                                <button type="submit" class="pay-button" id="mayaPayBtn" disabled>Pay Now</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="fee-notice">
                    <p><strong>Consultation Fee:</strong> ₱500.00</p>
                    <p>This appointment fee will be deducted from the total payment.</p>
                </div>
            </div>
        </div>

        <!-- Hidden IDs -->
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>">
        <input type="hidden" name="appointment_id" value="<?= $appointment_id ?? '' ?>">
    </form>
</div>

<?php include_once('footer.php'); ?>

<script>
const paymentMethodSelect = document.getElementById('paymentMethod');

const gcashFields = ['gcashaccName', 'gcashNum', 'gcashAmount', 'gcashrefNum', 'proofImage'];
const mayaFields = ['mayaaccName', 'mayaNum', 'mayaAmount', 'mayarefNum', 'proofImageMaya'];

function toggleFields(fields, show) {
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.required = show;
            el.disabled = !show;
        }
    });
}

paymentMethodSelect.addEventListener('change', function () {
    const method = this.value;
    document.getElementById('gcashDetails').style.display = 'none';
    document.getElementById('mayaDetails').style.display = 'none';

    toggleFields(gcashFields, false);
    toggleFields(mayaFields, false);

    document.getElementById('gcashPayBtn').disabled = true;
    document.getElementById('mayaPayBtn').disabled = true;
    document.getElementById('gcashConfirm').checked = false;
    document.getElementById('mayaConfirm').checked = false;

    if (method === 'GCash') {
        document.getElementById('gcashDetails').style.display = 'block';
        toggleFields(gcashFields, true);
    } else if (method === 'PayMaya') {
        document.getElementById('mayaDetails').style.display = 'block';
        toggleFields(mayaFields, true);
    }
});

function togglePayButton(type) {
    const btn = document.getElementById(type + 'PayBtn');
    const confirm = document.getElementById(type + 'Confirm');
    if (btn && confirm) {
        btn.disabled = !confirm.checked;
    }
}

// File upload text update
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            const fileText = this.parentElement.querySelector('.file-text');
            if (fileText) {
                fileText.textContent = fileName;
            }
        });
    });
});
</script>

</body>
</html>