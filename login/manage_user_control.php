<?php
session_start();
include_once('config.php');

// Check if admin is logged in
if (!isset($_SESSION['userID']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'block_user':
        blockUser($con, $input);
        break;
    
    case 'unblock_user':
        unblockUser($con, $input);
        break;
    
    case 'send_promotional_email':
        sendPromotionalEmail($con, $input);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function blockUser($con, $data) {
    $userId = $data['user_id'] ?? '';
    
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    // Check if user_account table has status column, if not create it
    checkUserAccountStatusColumn($con);
    
    // Block user by setting status to 'blocked'
    $updateQuery = "UPDATE user_account SET status = 'blocked' WHERE user_id = ? AND role != 'admin'";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param("s", $userId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or cannot be blocked']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to block user: ' . mysqli_error($con)]);
    }
    
    $updateStmt->close();
}

function unblockUser($con, $data) {
    $userId = $data['user_id'] ?? '';
    
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    checkUserAccountStatusColumn($con);
    
    // Unblock user by setting status to 'active'
    $updateQuery = "UPDATE user_account SET status = 'active' WHERE user_id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param("s", $userId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'User unblocked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unblock user: ' . mysqli_error($con)]);
    }
    
    $updateStmt->close();
}

function sendPromotionalEmail($con, $data) {
    $recipients = $data['recipients'] ?? 'all_users';
    $subject = $data['subject'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        return;
    }
    
    // Get recipient emails based on selection
    $whereClause = "";
    if ($recipients === 'with_appointments') {
        $whereClause = "AND EXISTS (SELECT 1 FROM patient_information p 
                        INNER JOIN appointments a ON p.patient_id = a.patient_id 
                        WHERE p.user_id = ua.user_id)";
    } else if ($recipients === 'no_appointments') {
        $whereClause = "AND NOT EXISTS (SELECT 1 FROM patient_information p 
                        INNER JOIN appointments a ON p.patient_id = a.patient_id 
                        WHERE p.user_id = ua.user_id)";
    }
    
    $query = "SELECT ua.user_id, ua.email, ua.first_name, ua.last_name 
              FROM user_account ua 
              WHERE ua.role = 'patient' 
              AND (ua.status IS NULL OR ua.status != 'blocked')
              AND ua.email IS NOT NULL AND ua.email != '' 
              {$whereClause}";
    $result = mysqli_query($con, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch recipients: ' . mysqli_error($con)]);
        return;
    }
    
    $sentCount = 0;
    $failedCount = 0;
    
    // Initialize promotional_emails table if needed
    checkPromotionalEmailsTable($con);
    
    // For now, just save to database or log
    // In production, you would integrate with an email service (e.g., PHPMailer, SendGrid)
    while ($user = mysqli_fetch_assoc($result)) {
        // Log the promotional email
        $insertQuery = "INSERT INTO promotional_emails (user_id, email, subject, message, sent_at) 
                       VALUES (?, ?, ?, ?, NOW())";
        $stmt = $con->prepare($insertQuery);
        if ($stmt) {
            $stmt->bind_param("ssss", $user['user_id'], $user['email'], $subject, $message);
            if ($stmt->execute()) {
                $sentCount++;
            } else {
                $failedCount++;
            }
            $stmt->close();
        } else {
            $failedCount++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Promotional email queued for {$sentCount} recipients.",
        'sent_count' => $sentCount,
        'failed_count' => $failedCount
    ]);
}

function checkUserAccountStatusColumn($con) {
    // Check if status column exists in user_account table
    $checkColumn = "SHOW COLUMNS FROM user_account LIKE 'status'";
    $result = mysqli_query($con, $checkColumn);
    
    if (mysqli_num_rows($result) == 0) {
        // Add status column if it doesn't exist
        $addColumn = "ALTER TABLE user_account ADD COLUMN status ENUM('active', 'blocked') NOT NULL DEFAULT 'active' AFTER role";
        mysqli_query($con, $addColumn);
    }
}

// Create promotional_emails table if it doesn't exist
function checkPromotionalEmailsTable($con) {
    $checkTable = "SHOW TABLES LIKE 'promotional_emails'";
    $result = mysqli_query($con, $checkTable);
    
    if (mysqli_num_rows($result) == 0) {
        $createTable = "CREATE TABLE promotional_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20),
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_email (email),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($con, $createTable);
    }
}

// Initialize tables on first access
checkPromotionalEmailsTable($con);
?>

