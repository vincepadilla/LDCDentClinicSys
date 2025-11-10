<?php
include_once('config.php');
if (isset($_GET['appointment_date'])) {
    $selectedDate = $_GET['appointment_date'];

    $bookedSlots = array();
    $query = "SELECT time_slot FROM tbl_appointments WHERE appointment_date = ?";
    $stmt  = $con->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[] = $row['time_slot'];
        }
        $stmt->close(); 
    }
    header('Content-Type: application/json');
    echo json_encode($bookedSlots);
}
?>