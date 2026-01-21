<?php
include_once('config.php'); 
header('Content-Type: application/json');

try {
    $sql = "SELECT user_id, first_name, last_name, email, phone FROM user_account WHERE role = 'admin'";
    $result = mysqli_query($con, $sql);

    $adminUsers = [];

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $adminUsers[] = $row;
            }
        }
    } else {
        // Query failed
        $error = mysqli_error($con);
        echo json_encode(['error' => 'Database query failed: ' . $error]);
        exit;
    }

    echo json_encode($adminUsers);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>