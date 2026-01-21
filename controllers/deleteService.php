<?php
include_once('config.php'); // Make sure you include the correct DB connection file

// Check if the service_id is sent via POST
if (isset($_POST['service_id'])) {
    $service_id = intval($_POST['service_id']);  // Sanitize and validate service_id

    // SQL to delete the service
    $sql = "DELETE FROM tbl_services WHERE service_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        // Display success alert and redirect to services.php
        echo "<script>
                alert('Service deleted successfully!');
                window.location.href = 'admin.php';
              </script>";
        exit();
    } else {
        // Display error alert and redirect to services.php
        echo "<script>
                alert('Error deleting service. Please try again.');
                window.location.href = 'admin.php';
              </script>";
        exit();
    }
} else {
    // Redirect back if service_id is not provided
    echo "<script>
            alert('No service ID provided.');
            window.location.href = 'admin.php';
          </script>";
    exit();
}
?>
