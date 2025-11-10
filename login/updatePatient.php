<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['patient_id']);
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $gender = $_POST['gender'];
    $age = intval($_POST['age']);
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $con->prepare("UPDATE tbl_patients SET first_name=?, last_name=?, gender=?, age=?, email=?, phone=?, address=? WHERE patient_id=?");
    $stmt->bind_param("sssisssi", $first, $last, $gender, $age, $email, $phone, $address, $id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Patient updated successfully!');
                window.location.href = 'admin.php';
              </script>";
    } else {
        echo "<script>
                alert('Error updating patient.');
                window.location.href = 'admin.php';
              </script>";
    }

    $stmt->close();
    $con->close();
}
?>
