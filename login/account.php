<?php
session_start();
include_once("config.php");
define("TITLE", "My Account");
include_once('../header.php');

// ✅ Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['userID'];

// ✅ Fetch user and patient information
$user_query = $con->prepare("
    SELECT ua.user_id, ua.username, ua.first_name, ua.last_name, ua.email, ua.phone,
           p.patient_id, p.birthdate, p.gender, p.address
    FROM user_account ua
    LEFT JOIN patient_information p ON ua.user_id = p.user_id
    WHERE ua.user_id = ?
");
$user_query->bind_param("s", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

// ✅ Fetch recent appointments (if patient exists)
// Prioritize non-cancelled appointments over cancelled ones
$recent_appointments = [];
if (!empty($user['patient_id'])) {
    $appt_query = $con->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
               s.service_category, a.status, a.created_at
        FROM appointments a
        INNER JOIN services s ON a.service_id = s.service_id
        WHERE a.patient_id = ?
        ORDER BY 
            CASE 
                WHEN a.status IN ('Cancelled', 'Complete', 'Completed', 'No-show') THEN 1
                ELSE 0
            END ASC,
            a.created_at DESC,
            a.appointment_date DESC, 
            a.appointment_time DESC
        LIMIT 5
    ");
    $appt_query->bind_param("s", $user['patient_id']);
    $appt_query->execute();
    $appt_result = $appt_query->get_result();
    while ($row = $appt_result->fetch_assoc()) {
        $recent_appointments[] = $row;
    }
}

// ✅ Debug output to browser console
echo "<script>
console.log('DEBUG: User ID => " . addslashes($user_id) . "');
console.log('DEBUG: Patient ID => " . addslashes($user['patient_id'] ?? 'NULL') . "');
console.log('DEBUG: User data exists => " . (!empty($user) ? 'YES' : 'NO') . "');
console.log('DEBUG: Found appointments => " . count($recent_appointments) . "');
</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="accountstyle.css">
</head>
<body>

<div class="account-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-header">
            <div>
                <h1>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'User'); ?></h1>
                <p>Manage your appointments and account settings</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="btn btn-secondary logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="account-layout">
        <!-- Left Column - Account Info & Quick Actions -->
        <div class="left-column">
            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Account Information</h2>
                    <button class="btn-edit" onclick="openEditModal()">Edit</button>
                </div>
                <p class="card-subtitle">Your personal details</p>
                
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?= htmlspecialchars($user['username'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">User ID</span>
                        <span class="info-value"><?= htmlspecialchars($user['user_id'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?= htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 class="card-title">Quick Actions</h2>
                
                <div class="action-list">
                    <a href="#" class="action-item">
                        <span class="action-icon">📋</span>
                        <span class="action-text">View All Appointments</span>
                    </a>
                    <a href="#edit-credentials" class="action-item" onclick="openCredentialsModal()">
                        <span class="action-icon">🔐</span>
                        <span class="action-text">Change Password</span>
                    </a>
                    <a href="../location.php" class="action-item">
                        <span class="action-icon">📍</span>
                        <span class="action-text">Find Locations</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column - Recent Appointments -->
        <div class="right-column">
            <div class="card">
                <h2 class="card-title">Your Recent Appointments</h2>
                <p class="card-subtitle">View and manage your upcoming visits</p>

                <?php if (!empty($recent_appointments)): ?>
                    <?php foreach ($recent_appointments as $recent_appointment): ?>
                        <div class="appointment-card" style="margin-bottom: 20px;">
                            <div class="appointment-header">
                                <h3>Appointment #<?= htmlspecialchars($recent_appointment['appointment_id']); ?></h3>
                                <?php
                                $status = $recent_appointment['status'];
                                $statusClass = match($status) {
                                    'Pending' => 'status-pending',
                                    'Confirmed' => 'status-confirmed',
                                    'Cancelled' => 'status-cancelled',
                                    'Complete' => 'status-completed',
                                    'Completed' => 'status-completed',
                                    'Reschedule' => 'status-pending',
                                    default => 'status-default'
                                };
                                ?>
                                <span class="status-badge <?= $statusClass; ?>"><?= htmlspecialchars($status); ?></span>
                            </div>
                            
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?= htmlspecialchars($recent_appointment['appointment_date']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value"><?= htmlspecialchars($recent_appointment['appointment_time']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Service</span>
                                    <span class="detail-value"><?= htmlspecialchars($recent_appointment['service_category']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Dentist</span>
                                    <span class="detail-value">Dr. Michelle Landero</span>
                                </div>
                            </div>
                            
                            <div class="appointment-message">
                                <?php
                                if ($status == "Pending") {
                                    echo "<p>Your appointment has been scheduled. Please wait for confirmation.</p>";
                                } elseif ($status == "Confirmed") {
                                    echo "<p>Your appointment has been confirmed.</p>";
                                } elseif ($status == "Complete" || $status == "Completed") {
                                    echo "<p>Your appointment has been completed.</p>";
                                } elseif ($status == "Cancelled") {
                                    echo "<p>Your appointment has been cancelled.</p>";
                                } elseif ($status == "Reschedule") {
                                    echo "<p>Your appointment has been rescheduled. Please wait for confirmation.</p>";
                                }
                                ?>
                            </div>
                            
                            <div class="appointment-actions">
                                <a href="cancelAppointment.php?id=<?= $recent_appointment['appointment_id']; ?>" 
                                   class="btn btn-danger <?= ($status == 'Cancelled' || $status == 'Complete' || $status == 'Completed') ? 'disabled' : ''; ?>"
                                   <?= ($status == 'Cancelled' || $status == 'Complete' || $status == 'Completed') ? 'onclick="return false;"' : "onclick=\"return confirm('Are you sure you want to cancel?');\""; ?>>
                                    Cancel Appointment
                                </a>

                                <a href="reschedule.php?id=<?= $recent_appointment['appointment_id']; ?>" 
                                   class="btn btn-primary <?= ($status == 'Cancelled' || $status == 'Complete' || $status == 'Completed') ? 'disabled' : ''; ?>">
                                    Reschedule Appointment
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-appointment">
                        <div class="no-appointment-icon">📅</div>
                        <h3>No Recent Appointments</h3>
                        <p>You don't have any appointments scheduled yet. Book your next dental visit to maintain your oral health.</p>
                        <a href="../index.php" class="btn btn-primary">Book an Appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div id="editModal" class="edit-modal">
    <div class="edit-modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>EDIT ACCOUNT INFORMATION</h3>
        <form action="updateAccount.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Birthdate:</label>
                    <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Gender:</label>
                    <select name="gender" required>
                        <option value="Male" <?= (($user['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?= (($user['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <!-- Empty space for alignment -->
                </div>
            </div>
            
            <div class="form-group full-width">
                <label>Address:</label>
                <textarea name="address" required><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-submit">UPDATE ACCOUNT</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Credentials Modal -->
<div id="credentialsModal" class="edit-modal">
    <div class="edit-modal-content">
        <span class="close" onclick="closeCredentialsModal()">&times;</span>
        <h3>CHANGE PASSWORD</h3>
        <form action="updateCredentials.php" method="POST" id="credentialsForm">
            <div class="form-group full-width">
                <label>Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
                <span class="password-toggle" onclick="togglePassword('current_password', this)">👁️</span>
            </div>
            
            <div class="form-group full-width">
                <label>New Password:</label>
                <input type="password" name="new_password" id="new_password" required minlength="8">
                <span class="password-toggle" onclick="togglePassword('new_password', this)">👁️</span>
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <span class="strength-text">Password strength</span>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span class="password-toggle" onclick="togglePassword('confirm_password', this)">👁️</span>
                <span id="password-match" class="validation-message"></span>
            </div>
            
            <div class="password-requirements">
                <p><strong>Password Requirements:</strong></p>
                <ul>
                    <li id="req-length">At least 8 characters</li>
                    <li id="req-uppercase">One uppercase letter</li>
                    <li id="req-lowercase">One lowercase letter</li>
                    <li id="req-number">One number</li>
                    <li id="req-special">One special character</li>
                </ul>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeCredentialsModal()">Cancel</button>
                <button type="submit" class="btn-submit" id="updatePasswordBtn">UPDATE PASSWORD</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openEditModal() {
    const modal = document.getElementById("editModal");
    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("show"), 10);
}

function closeEditModal() {
    const modal = document.getElementById("editModal");
    modal.classList.remove("show");
    setTimeout(() => modal.style.display = "none", 300);
}

function openCredentialsModal() {
    const modal = document.getElementById("credentialsModal");
    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("show"), 10);
    resetCredentialsForm();
}

function closeCredentialsModal() {
    const modal = document.getElementById("credentialsModal");
    modal.classList.remove("show");
    setTimeout(() => modal.style.display = "none", 300);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById("editModal");
    const credentialsModal = document.getElementById("credentialsModal");
    
    if (event.target === editModal) closeEditModal();
    if (event.target === credentialsModal) closeCredentialsModal();
};

// Password visibility toggle
function togglePassword(inputId, toggleElement) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        toggleElement.textContent = '🙈';
    } else {
        input.type = 'password';
        toggleElement.textContent = '👁️';
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };

    // Update requirement indicators
    Object.keys(requirements).forEach(req => {
        const element = document.getElementById(`req-${req}`);
        if (element) {
            element.className = requirements[req] ? 'requirement-met' : '';
        }
    });

    // Calculate strength
    strength += requirements.length ? 1 : 0;
    strength += requirements.uppercase ? 1 : 0;
    strength += requirements.lowercase ? 1 : 0;
    strength += requirements.number ? 1 : 0;
    strength += requirements.special ? 1 : 0;

    // Update strength bar
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    if (strengthBar && strengthText) {
        const width = (strength / 5) * 100;
        strengthBar.style.width = `${width}%`;
        
        if (strength <= 2) {
            strengthBar.className = 'strength-bar weak';
            strengthText.textContent = 'Weak password';
        } else if (strength <= 4) {
            strengthBar.className = 'strength-bar medium';
            strengthText.textContent = 'Medium strength';
        } else {
            strengthBar.className = 'strength-bar strong';
            strengthText.textContent = 'Strong password';
        }
    }

    return strength;
}

// Password confirmation check
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchElement = document.getElementById('password-match');
    
    if (confirmPassword === '') {
        matchElement.textContent = '';
        matchElement.className = 'validation-message';
    } else if (newPassword === confirmPassword) {
        matchElement.textContent = '✓ Passwords match';
        matchElement.className = 'validation-message valid';
    } else {
        matchElement.textContent = '✗ Passwords do not match';
        matchElement.className = 'validation-message invalid';
    }
}

// Reset credentials form
function resetCredentialsForm() {
    document.getElementById('credentialsForm').reset();
    document.querySelector('.strength-bar').style.width = '0%';
    document.querySelector('.strength-text').textContent = 'Password strength';
    document.getElementById('password-match').textContent = '';
    
    // Reset requirement indicators
    const requirements = ['length', 'uppercase', 'lowercase', 'number', 'special'];
    requirements.forEach(req => {
        const element = document.getElementById(`req-${req}`);
        if (element) element.className = '';
    });
}

// Event listeners for password fields
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    // Form validation for credentials
    const credentialsForm = document.getElementById('credentialsForm');
    if (credentialsForm) {
        credentialsForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (checkPasswordStrength(newPassword) < 3) {
                e.preventDefault();
                alert('Please choose a stronger password!');
                return false;
            }
            
            return true;
        });
    }
});

</script>

<?php include_once('../footer.php'); ?>
</body>
</html>