<?php
include_once('config.php'); // Make sure to include your database connection

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dentist_id = $_POST['dentist_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $specialization = $_POST['specialization'];
    $status = $_POST['status'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Update the dentist details in the database
    $updateSql = "UPDATE tbl_dentists SET 
                  first_name = ?, last_name = ?, specialization = ?, 
                  status = ?, email = ?, phone = ? 
                  WHERE dentist_id = ?";

    if ($stmt = $con->prepare($updateSql)) {
        // Bind parameters and execute the query
        $stmt->bind_param("ssssssi", $first_name, $last_name, $specialization, $status, $email, $phone, $dentist_id);

        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>alert('Staff details updated successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Error updating dentist.'); window.history.back();</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error preparing statement: " . $con->error . "'); window.history.back();</script>";
    }
}
?>
