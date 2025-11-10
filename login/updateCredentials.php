<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['userID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Enhanced debugging
    error_log("=== PASSWORD UPDATE ATTEMPT ===");
    error_log("User ID: " . $user_id);
    error_log("Current password provided: " . (empty($current_password) ? 'EMPTY' : 'PROVIDED'));
    error_log("New password length: " . strlen($new_password));

    // Basic validation
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match!";
        header("Location: account.php");
        exit();
    }

    if (strlen($new_password) < 8) {
        $_SESSION['error_message'] = "Password must be at least 8 characters long!";
        header("Location: account.php");
        exit();
    }

    try {
        // Check user and password field - USING CORRECT COLUMN NAME password_hash
        $check_query = $con->prepare("SELECT username, password_hash FROM user_account WHERE user_id = ?");
        $check_query->bind_param("s", $user_id);
        $check_query->execute();
        $result = $check_query->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error_message'] = "User not found!";
            header("Location: account.php");
            exit();
        }

        $user = $result->fetch_assoc();
        $current_password_hash = $user['password_hash'];
        $username = $user['username'];

        // Enhanced debugging
        error_log("Username: " . $username);
        error_log("Current password hash in DB: " . ($current_password_hash ?? 'NULL'));
        error_log("Hash length: " . strlen($current_password_hash));

        // Verify current password
        if (password_verify($current_password, $current_password_hash)) {
            error_log("Current password verification: SUCCESS");
            
            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            error_log("New password hash created: " . substr($new_password_hash, 0, 20) . "...");
            
            // Update the password - USING CORRECT COLUMN NAME password_hash
            $update_query = $con->prepare("UPDATE user_account SET password_hash = ? WHERE user_id = ?");
            $update_query->bind_param("ss", $new_password_hash, $user_id);
            
            if ($update_query->execute()) {
                error_log("Password update: SUCCESS");
                
                // SUCCESS MESSAGE FOR DEBUGGING
                $_SESSION['success_message'] = "Password updated successfully! 
                    DEBUG INFO: 
                    - User: $username
                    - Hash updated in password_hash column
                    - Rows affected: " . $update_query->affected_rows;
                
                // Verify the update worked
                $verify_query = $con->prepare("SELECT password_hash FROM user_account WHERE user_id = ?");
                $verify_query->bind_param("s", $user_id);
                $verify_query->execute();
                $verify_result = $verify_query->get_result();
                $updated_user = $verify_result->fetch_assoc();
                
                error_log("Password after update: " . substr($updated_user['password_hash'], 0, 20) . "...");
                
                // Test if new password verifies
                $password_verifies = password_verify($new_password, $updated_user['password_hash']);
                error_log("New password verifies: " . ($password_verifies ? 'YES' : 'NO'));
                
                // Add verification result to success message
                if ($password_verifies) {
                    $_SESSION['success_message'] .= " - VERIFICATION: SUCCESS - New password works!";
                } else {
                    $_SESSION['success_message'] .= " - VERIFICATION: FAILED - New password doesn't work!";
                }
                
            } else {
                error_log("Password update: FAILED - " . $update_query->error);
                $_SESSION['error_message'] = "Error updating password: " . $update_query->error;
            }
        } else {
            error_log("Current password verification: FAILED");
            error_log("Provided: " . $current_password);
            error_log("Expected hash: " . $current_password_hash);
            $_SESSION['error_message'] = "Current password is incorrect! 
                DEBUG: Make sure you're entering the exact password you used during registration.";
        }

    } catch (Exception $e) {
        error_log("Password update exception: " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: account.php");
    exit();
}
?>