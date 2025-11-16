<?php
session_start();
include_once("config.php");

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($con, trim($_POST['username']));
    $password = mysqli_real_escape_string($con, trim($_POST['password']));

    if (!empty($name) && !empty($password)) {

        // ✅ Check if user exists in user_account
        $query = "SELECT * FROM user_account WHERE username = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // ✅ Verify password
            if (password_verify($password, $row['password_hash'])) {
                // ✅ Set session variables (after successful login)
                $_SESSION['userID'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['first_name'] = $row['first_name'] ?? '';
                $_SESSION['last_name'] = $row['last_name'] ?? '';
                $_SESSION['email'] = $row['email'] ?? '';
                $_SESSION['phone'] = $row['phone'] ?? '';
                $_SESSION['role'] = $row['role'] ?? 'user';
                $_SESSION['valid'] = true;

                if (strtolower($row['role']) === 'admin') {
                header("Location: admin_verify.php");
                exit();

                } else {
                    header("Location: ../index.php");
                    exit();
                }

            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that username.";
        }

    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Clinic Portal</title>
    <link rel="stylesheet" href="loginpagestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">

        <!-- Back to Home Button -->
        <div class="back-home">
                <a href="../index.php" class="back-home-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
        </div>
        <!-- LEFT SIDE -->
        <div class="login-left">
            <div class="overlay"></div>
            <div class="left-content">
                <h1>Smiles Made Simple<br>Start Yours Today</h1>
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

        <!-- RIGHT SIDE - NO CONTAINER BOX -->
        <div class="login-right">
            <div class="right-content">
                <div class="logo-container">
                    <img src="../landerologo.png" alt="Clinic Logo" class="clinic-logo">
                </div>

                <div class="welcome-section">
                    <h2>Sign In to Your Account</h2>
                  
                </div>

                <?php if (isset($error)) { ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php } ?>

                <form action="" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-field">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" id="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password"></button>
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" name="submit" class="auth-btn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="auth-link">
                        <p class="link-text">
                            Don't have an account? <a href="register.php" class="link">Sign up now</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
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

        // Add focus effects
        document.querySelectorAll('.input-field input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
