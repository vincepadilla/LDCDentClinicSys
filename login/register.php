<?php 
include_once("config.php");
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PhpMailer/src/Exception.php';
require '../PhpMailer/src/PHPMailer.php';
require '../PhpMailer/src/SMTP.php';

if (isset($_POST['submit'])) {
    // Sanitize input
    $username = mysqli_real_escape_string($con, trim($_POST['username']));
    $fname = mysqli_real_escape_string($con, trim($_POST['fname']));
    $lname = mysqli_real_escape_string($con, trim($_POST['lname']));
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $phone = mysqli_real_escape_string($con, trim($_POST['phone']));
    $birthdate = mysqli_real_escape_string($con, trim($_POST['birthdate']));
    $gender = mysqli_real_escape_string($con, trim($_POST['gender']));
    $address = mysqli_real_escape_string($con, trim($_POST['address']));
    $password = mysqli_real_escape_string($con, trim($_POST['password']));

    if (empty($username) || empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($password) || empty($birthdate) || empty($gender) || empty($address)) {
        echo "<script>alert('All fields are required.'); window.location.href='register.php';</script>";
        exit();
    }

    // Password length check
    if (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long.'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate phone format (exactly 11 digits)
    if (!preg_match('/^\d{11}$/', $phone)) {
        echo "<script>alert('Invalid phone number. It must be exactly 11 digits.'); window.location.href='register.php';</script>";
        exit();
    }

    // Check for existing email
    $check_email = mysqli_query($con, "SELECT email FROM user_account WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>alert('The email is already registered.'); window.location.href='register.php';</script>";
        exit();
    }

    // Generate unique alphanumeric user_id (e.g., U0001)
    $result = mysqli_query($con, "SELECT user_id FROM user_account ORDER BY user_id DESC LIMIT 1");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastID = intval(substr($row['user_id'], 1)) + 1;
        $user_id = "U" . str_pad($lastID, 4, "0", STR_PAD_LEFT);
    } else {
        $user_id = "U0001";
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['temp_user'] = [
        'user_id' => $user_id,
        'username' => $username,
        'fname' => $fname,
        'lname' => $lname,
        'birthdate' => $_POST['birthdate'] ?? null,
        'gender' => $_POST['gender'] ?? null,
        'address' => $_POST['address'] ?? null,
        'email' => $email,
        'phone' => $phone,
        'password' => $hashed_password
    ];
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 600; 

    // Send OTP via PHPMailer
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
        $mail->addAddress($email, $fname . ' ' . $lname);
        $mail->Subject = 'OTP Verification';
        $mail->Body = "Hello $fname,\n\nYour OTP for account verification is: $otp\n\nThis code will expire in 10 minutes.\n\nThank you!";

        $mail->send();

        header("Location: otpVerification.php");
        exit();
    } catch (Exception $e) {
        echo "<script>alert('Mailer Error: {$mail->ErrorInfo}'); window.location.href='register.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Landero Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="registerstyle.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <!-- LEFT SIDE -->
        <div class="login-left">
            <div class="overlay"></div>
            <div class="left-content">
                <h1>Your Journey to<br>Confidence Begins Here</h1>
                <p class="subtitle">Landero Dental Clinic</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-tooth"></i>
                        <span>Expert Dental Care</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Easy Online Booking</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-star"></i>
                        <span>5-Star Rated Service</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE - COMPACT DESIGN -->
        <div class="login-right">
            <div class="right-content compact-form">

                <div class="welcome-section">
                    <h2>Join Our Clinic!</h2>
                    <p>Create your account to get started</p>
                </div>

                <?php if (isset($error)) { ?>
                    <div class="error-message compact-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>
                
                <?php if (isset($success)) { ?>
                    <div class="success-message compact-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php } ?>

                <form action="register.php" method="post" class="auth-form compact-auth-form">
                    <div class="form-row compact-row">
                        <div class="form-group compact-group half-width">
                            <label for="fname">First Name</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fname" id="fname" placeholder="First name" required>
                            </div>
                        </div>
                        
                        <div class="form-group compact-group half-width">
                            <label for="lname">Last Name</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-user"></i>
                                <input type="text" name="lname" id="lname" placeholder="Last name" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group compact-group">
                        <label for="username">Username</label>
                        <div class="input-field compact-input">
                            <i class="fas fa-at"></i>
                            <input type="text" name="username" id="username" placeholder="Choose username" required>
                        </div>
                    </div>

                    <div class="form-group compact-group">
                        <label for="email">Email</label>
                        <div class="input-field compact-input">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="form-group compact-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-field compact-input">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="phone" id="phone" placeholder="11-digit phone number" maxlength="11" pattern="\d{11}" required>
                        </div>
                    </div>

                    <!-- NEW FIELDS: Birthdate, Gender, Address -->
                    <div class="form-row compact-row">
                        <div class="form-group compact-group half-width">
                            <label for="birthdate">Birthdate</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-calendar"></i>
                                <input type="date" name="birthdate" id="birthdate" required>
                            </div>
                        </div>
                        
                        <div class="form-group compact-group half-width">
                            <label for="gender">Gender</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-venus-mars"></i>
                                <select name="gender" id="gender" required>
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group compact-group">
                        <label for="address">Address</label>
                        <div class="input-field compact-input">
                            <i class="fas fa-home"></i>
                            <input type="text" name="address" id="address" placeholder="Enter your complete address" required>
                        </div>
                    </div>

                    <div class="form-row compact-row">
                        <div class="form-group compact-group half-width">
                            <label for="password">Password</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="password" placeholder="Create password" required>
                                <button type="button" class="toggle-password compact-toggle" id="togglePassword"></button>
                            </div>
                        </div>

                        <div class="form-group compact-group half-width">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-field compact-input">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                                <button type="button" class="toggle-password compact-toggle" id="toggleConfirmPassword"></button>
                            </div>
                        </div>
                    </div>

                    <div class="terms-agreement compact-terms">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#" class="terms-link">Terms</a> and <a href="#" class="terms-link">Privacy Policy</a></label>
                    </div>

                    <button type="submit" name="submit" class="auth-btn compact-btn">
                        <span>Create Account</span>
                        <i class="fas fa-user-plus"></i>
                    </button>

                    <div class="auth-link compact-link">
                        <p class="link-text">
                            Already have an account? <a href="login.php" class="link">Sign in</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Add focus effects
        document.querySelectorAll('.input-field input, .input-field select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Set max date for birthdate (minimum age 18 years)
        const birthdateInput = document.getElementById('birthdate');
        const today = new Date();
        const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
        birthdateInput.max = maxDate.toISOString().split('T')[0];
    </script>
</body>
</html>