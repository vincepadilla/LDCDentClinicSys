<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['service_id'];
    $name = mysqli_real_escape_string($con, trim($_POST['service_name']));
    $desc = mysqli_real_escape_string($con, trim($_POST['description']));
    $price = (float)$_POST['price'];

    $query = "UPDATE tbl_services SET service_name='$name', description='$desc', price='$price' WHERE service_id=$id";

    if (mysqli_query($con, $query)) {
        echo "<script>alert('Service updated successfully'); window.location.href='admin.php#services';</script>";
    } else {
        echo "<script>alert('Error updating service'); window.location.href='admin_page.php#services';</script>";
    }

    mysqli_close($con);
}
?>