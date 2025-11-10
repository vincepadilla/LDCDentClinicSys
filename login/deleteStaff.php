<?php
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dentist_id'])) {
    $dentist_id = $_POST['dentist_id'];

    // Prepare and execute delete query for tbl_dentists
    $stmt = $con->prepare("DELETE FROM tbl_dentists WHERE dentist_id = ?");
    $stmt->bind_param("i", $dentist_id);

    if ($stmt->execute()) {
        echo "<script>
            alert('Dentist deleted successfully!');
            window.location.href = 'admin.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Error deleting dentist.');
            window.location.href = 'admin.php';
        </script>";
    }
} else {
    echo "<script>
        alert('Invalid request.');
        window.location.href = 'admin.php';
    </script>";
}
?>
