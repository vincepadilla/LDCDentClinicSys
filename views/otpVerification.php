<?php
session_start();
include_once("../database/config.php");

$error = '';
$success = '';
$showSuccessModal = false;

// Check if registration was successful (allow success modal even if session is cleared)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $showSuccessModal = true;
    $success = 'Registration successful!';
    // Don't check for session if showing success modal
} else {
    // Check if OTP session exists (only if not showing success modal)
    if (!isset($_SESSION['temp_user']) || !isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
        echo "<script>alert('No registration session found. Please register again.'); window.location.href='register.php';</script>";
        exit;
    }

    // Check if OTP expired on page load
    if (time() > $_SESSION['otp_expiry']) {
        unset($_SESSION['temp_user'], $_SESSION['otp'], $_SESSION['otp_expiry']);
        echo "<script>alert('OTP expired. Please register again.'); window.location.href='register.php';</script>";
        exit;
    }
}

if (isset($_POST['submit'])) {
    $entered_otp = trim($_POST['otp']);

    // Validate OTP format
    if (empty($entered_otp)) {
        $error = 'Please enter the OTP code.';
    } elseif (!preg_match('/^\d{6}$/', $entered_otp)) {
        $error = 'Please enter a valid 6-digit OTP code.';
    } else {
        // Check if OTP expired
        if (time() > $_SESSION['otp_expiry']) {
            unset($_SESSION['temp_user'], $_SESSION['otp'], $_SESSION['otp_expiry']);
            echo "<script>alert('OTP expired. Please register again.'); window.location.href='register.php';</script>";
            exit;
        }

        // Verify OTP
        if ($entered_otp == $_SESSION['otp']) {
            $user_id = $_SESSION['temp_user']['user_id'];
            $username = $_SESSION['temp_user']['username'];
            $fname = $_SESSION['temp_user']['fname'];
            $lname = $_SESSION['temp_user']['lname'];
            $email = $_SESSION['temp_user']['email'];
            $phone = $_SESSION['temp_user']['phone'];
            $birthdate = $_SESSION['temp_user']['birthdate'];
            $gender = $_SESSION['temp_user']['gender'];
            $address = $_SESSION['temp_user']['address'];
            $password_hash = $_SESSION['temp_user']['password'];

            // Check for duplicate username or email before attempting insert
            $check_duplicate = $con->prepare("SELECT user_id, username, email FROM user_account WHERE username = ? OR email = ? OR user_id = ?");
            $check_duplicate->bind_param("sss", $username, $email, $user_id);
            $check_duplicate->execute();
            $duplicate_result = $check_duplicate->get_result();
            
            if ($duplicate_result->num_rows > 0) {
                $duplicate_row = $duplicate_result->fetch_assoc();
                if ($duplicate_row['username'] === $username) {
                    $error = 'This username is already taken. Please choose a different username.';
                } elseif ($duplicate_row['email'] === $email) {
                    $error = 'This email is already registered. Please use a different email or try logging in.';
                } elseif ($duplicate_row['user_id'] === $user_id) {
                    $error = 'User ID conflict. Please try registering again.';
                } else {
                    $error = 'This account information is already registered.';
                }
                $check_duplicate->close();
            } else {
                $check_duplicate->close();
                
                // Use transaction to ensure both inserts succeed
                mysqli_begin_transaction($con);

                try {
                    // Insert into user_account
                    $query1 = "INSERT INTO user_account 
                                (user_id, username, first_name, last_name, birthdate, gender, address, email, phone, password_hash, role, contactNumber_verify) 
                               VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'patient', 'verified')";
                    $stmt1 = $con->prepare($query1);
                    $stmt1->bind_param("ssssssssss", $user_id, $username, $fname, $lname, $birthdate, $gender, $address, $email, $phone, $password_hash);
                    
                    if ($stmt1->execute()) {
                        // Commit the transaction
                        mysqli_commit($con);
                        $stmt1->close();
                        
                        // Clear session data
                        unset($_SESSION['temp_user'], $_SESSION['otp'], $_SESSION['otp_expiry']);
                        
                        // Set success flag for modal display
                        $_SESSION['registration_success'] = true;
                        
                        // Redirect to show success modal (reload page)
                        header("Location: otpVerification.php?success=1");
                        exit;
                    } else {
                        $error_code = $stmt1->errno;
                        $error_message = $stmt1->error;
                        $stmt1->close();
                        
                        // Handle specific MySQL error codes
                        if ($error_code == 1062) { // Duplicate entry
                            if (strpos($error_message, 'username') !== false) {
                                $error = 'This username is already taken. Please choose a different username.';
                            } elseif (strpos($error_message, 'email') !== false) {
                                $error = 'This email is already registered. Please use a different email or try logging in.';
                            } elseif (strpos($error_message, 'user_id') !== false) {
                                $error = 'User ID conflict. Please try registering again.';
                            } else {
                                $error = 'This information is already registered. Please check your details.';
                            }
                        } elseif ($error_code == 1048) { // NULL value in NOT NULL field
                            $error = 'Missing required information. Please ensure all fields are filled.';
                        } else {
                            $error = 'Database error: ' . htmlspecialchars($error_message) . ' (Error Code: ' . $error_code . ')';
                        }
                        
                        throw new Exception("Failed to insert user data: " . $error_message . " (Error Code: " . $error_code . ")");
                    }

                } catch (Exception $e) {
                    mysqli_rollback($con);
                    error_log('Database Transaction Error: ' . $e->getMessage());
                    // Error message already set above, or use generic one if not set
                    if (empty($error)) {
                        $error = 'Database error during registration. Please try again later.';
                    }
                }
            }

        } else {
            $error = 'Invalid OTP code. Please try again.';
        }
    }
}

// Calculate remaining time for the timer (only if session exists)
$remaining_time = 0;
if (!$showSuccessModal && isset($_SESSION['otp_expiry'])) {
    $remaining_time = $_SESSION['otp_expiry'] - time();
    if ($remaining_time < 0) {
        $remaining_time = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification | Landero Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/loginpagestyle.css?v=<?php echo time(); ?>">
    <style>
        /* Success Modal Styles */
        .success-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease-out;
            backdrop-filter: blur(4px);
        }

        .success-modal-overlay.show {
            opacity: 1;
        }

        .success-modal-content {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 380px;
            width: 90%;
            margin: auto;
            transform: scale(0.7) translateY(-50px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }

        .success-modal-overlay.show .success-modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .success-modal-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .success-icon-container {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
        }

        .success-icon-container::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            animation: ripple 1.5s infinite;
        }

        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.3);
                opacity: 0;
            }
        }

        .check-icon {
            width: 50px;
            height: 50px;
            position: relative;
            z-index: 1;
        }

        .check-icon svg {
            width: 100%;
            height: 100%;
        }

        .check-path {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.8s ease-out 0.3s forwards;
        }

        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }

        .success-modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .success-modal-body {
            padding: 25px;
            text-align: center;
        }

        .success-modal-body p {
            color: #6B7280;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }

        .success-modal-footer {
            padding: 18px 25px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            background: #f9fafb;
        }

        .btn-continue {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-continue:active {
            transform: translateY(0);
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .success-modal-content {
                width: 90%;
                max-width: 90%;
            }

            .success-modal-header {
                padding: 25px 20px;
            }

            .success-icon-container {
                width: 80px;
                height: 80px;
            }

            .check-icon {
                width: 50px;
                height: 50px;
            }

            .success-modal-header h2 {
                font-size: 24px;
            }

            .success-modal-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- LEFT SIDE -->
        <div class="login-left">
            <div class="overlay"></div>
            <div class="left-content">
                <h1>Secure Your Account</h1>
                <p class="subtitle">One-Time Password Verification</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Enhanced Security</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <span>Valid for 10 minutes</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Sent to your phone</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="login-right">
            <div class="right-content">
                <div class="logo-container">
                    <img src="../assets/images/landerologo.png" alt="Clinic Logo" class="clinic-logo">
                </div>

                <div class="welcome-section">
                    <h2>OTP Verification</h2>
                    <p>Enter the 6-digit code sent to your Email</p>
                    <p class="phone-number"><?php echo (isset($_SESSION['temp_user']['email']) && !$showSuccessModal) ? ' ' . htmlspecialchars($_SESSION['temp_user']['email']) : ''; ?></p>
                </div>

                <?php if (!empty($error)) { ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>

                <?php if ($showSuccessModal): ?>
                    <div id="successModal" class="success-modal-overlay show">
                        <div class="success-modal-content">
                            <div class="success-modal-header">
                                <div class="success-icon-container">
                                    <div class="check-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                            <path class="check-path" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h2>Registration Successful!</h2>
                            </div>
                            <div class="success-modal-body">
                                <p>Your account has been created successfully. You can now log in to access your account and book appointments.</p>
                            </div>
                            <div class="success-modal-footer">
                                <button class="btn-continue" onclick="redirectToLogin()">
                                    <span>Continue to Login</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$showSuccessModal): ?>
                <form action="" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="otp">Enter OTP Code</label>
                        <div class="otp-container">
                            <div class="otp-input-field">
                                <input type="text" name="otp" id="otp" maxlength="6" pattern="\d{6}" autocomplete="off" required 
                                       placeholder="Enter 6-digit code" class="otp-input" value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>">
                                <i class="fas fa-key otp-icon"></i>
                            </div>
                        </div>
                        <div class="otp-info">
                            <p><i class="fas fa-info-circle"></i> Check your phone for the OTP code</p>
                        </div>
                    </div>

                    <div class="otp-timer">
                        <i class="fas fa-clock"></i>
                        <span id="timer"><?php echo sprintf('%02d:%02d', floor($remaining_time / 60), $remaining_time % 60); ?></span> remaining
                    </div>

                    <button type="submit" name="submit" class="auth-btn">
                        <span>Verify OTP</span>
                        <i class="fas fa-check-circle"></i>
                    </button>

                    <div class="otp-actions">
                        <button type="button" class="resend-btn" id="resendOtp" <?php echo $remaining_time > 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-redo"></i>
                            <span>Resend OTP</span>
                        </button>
                    </div>

                    <div class="auth-link">
                        <p class="link-text">
                            <a href="register.php" class="link">← Back to Registration</a>
                        </p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // OTP Timer Countdown - using actual remaining time from PHP
        let timeLeft = <?php echo $remaining_time; ?>;
        const timerElement = document.getElementById('timer');
        const resendButton = document.getElementById('resendOtp');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                resendButton.disabled = false;
                resendButton.innerHTML = '<i class="fas fa-redo"></i><span>Resend OTP</span>';
                resendButton.classList.add('active');
                timerElement.textContent = '00:00';
                timerElement.style.color = '#f56565';
            }
        }

        // Start the timer if there's time left
        if (timeLeft > 0) {
            updateTimer();
        }

        // Resend OTP functionality
        resendButton.addEventListener('click', function() {
            if (!this.disabled) {
                // Show loading state
                this.disabled = true;
                this.classList.remove('active');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sending...</span>';
                
                // AJAX call to resend OTP
                fetch('resend_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'resend_otp=true'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '<i class="fas fa-check"></i><span>OTP Sent!</span>';
                        // Reset timer
                        timeLeft = 600;
                        updateTimer();
                        
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-redo"></i><span>Resend OTP</span>';
                            this.disabled = true;
                            this.classList.remove('active');
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Failed to send</span>';
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-redo"></i><span>Resend OTP</span>';
                            this.disabled = false;
                            this.classList.add('active');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Error occurred</span>';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-redo"></i><span>Resend OTP</span>';
                        this.disabled = false;
                        this.classList.add('active');
                    }, 2000);
                });
            }
        });

        // Auto-format OTP input
        const otpInput = document.getElementById('otp');
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                this.blur(); // Remove focus when complete
            }
        });

        // Auto-submit when 6 digits are entered
        otpInput.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Optional: auto-submit form
                // this.form.submit();
            }
        });

        // Add focus effects
        otpInput.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        otpInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });

        // Focus on OTP input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            otpInput.focus();
        });

        // Redirect to login function
        function redirectToLogin() {
            window.location.href = 'login.php';
        }

        // Auto-redirect after 5 seconds if modal is shown
        <?php if ($showSuccessModal): ?>
        setTimeout(function() {
            const modal = document.getElementById('successModal');
            if (modal && modal.classList.contains('show')) {
                redirectToLogin();
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>