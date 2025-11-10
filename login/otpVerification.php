<?php
session_start();
include_once("config.php");

// Check if OTP session exists
if (!isset($_SESSION['temp_user']) || !isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
    echo "<script>alert('No registration session found. Please register again.'); window.location.href='register.php';</script>";
    exit;
}

$error = '';
$success = '';

// Check if OTP expired on page load
if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['temp_user'], $_SESSION['otp'], $_SESSION['otp_expiry']);
    echo "<script>alert('OTP expired. Please register again.'); window.location.href='register.php';</script>";
    exit;
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
                    
                    // Clear session data
                    unset($_SESSION['temp_user'], $_SESSION['otp'], $_SESSION['otp_expiry']);
                    
                    echo "<script>
                        alert('Registration successful! You can now log in.');
                        window.location.href='login.php';
                    </script>";
                    exit;
                } else {
                    throw new Exception("Failed to insert user data: " . $stmt1->error);
                }

            } catch (Exception $e) {
                mysqli_rollback($con);
                error_log('Database Transaction Error: ' . $e->getMessage());
                $error = 'Database error during registration. Please try again later.';
            }

        } else {
            $error = 'Invalid OTP code. Please try again.';
        }
    }
}

// Calculate remaining time for the timer
$remaining_time = $_SESSION['otp_expiry'] - time();
if ($remaining_time < 0) {
    $remaining_time = 0;
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
    <link rel="stylesheet" href="loginpagestyle.css?v=<?php echo time(); ?>">
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
                    <img src="../landerologo.png" alt="Clinic Logo" class="clinic-logo">
                </div>

                <div class="welcome-section">
                    <h2>OTP Verification</h2>
                    <p>Enter the 6-digit code sent to your phone</p>
                    <p class="phone-number"><?php echo isset($_SESSION['temp_user']['phone']) ? '📱 ' . htmlspecialchars($_SESSION['temp_user']['phone']) : ''; ?></p>
                </div>

                <?php if (!empty($error)) { ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>

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
    </script>
</body>
</html>