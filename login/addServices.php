<?php
include_once("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_name = $_POST['serviceName'] ?? null;
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'] ?? null;

    if ($service_name && $description && is_numeric($price)) {
        $stmt = $con->prepare("INSERT INTO tbl_services (service_name, description, price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $service_name, $description, $price);

        if ($stmt->execute()) {
            echo "<script>alert('Service added successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Error adding service.'); window.history.back();</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Please fill in all fields correctly.'); window.history.back();</script>";
    }
} else {
    header("Location: admin_services.php");
    exit();
}
?>
