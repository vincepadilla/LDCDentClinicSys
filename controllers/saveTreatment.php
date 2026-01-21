<?php
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = trim($_POST['patient_id'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $prescription_given = trim($_POST['prescription_given'] ?? '');
    $notes = trim($_POST['treatment_notes'] ?? '');
    $treatment_cost = trim($_POST['treatment_cost'] ?? '');
    $appointment_id = trim($_POST['appointment_id'] ?? '');

    $errors = [];
    if (empty($patient_id)) $errors[] = "Patient ID is required.";
    if (empty($treatment)) $errors[] = "Treatment type is required.";
    if (empty($prescription_given)) $errors[] = "Prescription is required.";
    if (empty($notes)) $errors[] = "Treatment notes are required.";
    if ($treatment_cost === '' || !is_numeric($treatment_cost) || $treatment_cost < 0) {
        $errors[] = "Please enter a valid treatment cost.";
    }

    $checkPatient = $con->prepare("SELECT patient_id FROM patient_information WHERE patient_id = ?");
    $checkPatient->bind_param("s", $patient_id);
    $checkPatient->execute();
    $result = $checkPatient->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Patient ID not found in records.";
    }
    $checkPatient->close();

    if (!empty($errors)) {
        $error_message = implode("\\n", $errors);
        echo "<script>alert('Error:\\n$error_message'); window.history.back();</script>";
        exit;
    }

    $queryLast = $con->query("SELECT treatment_id FROM treatment_history ORDER BY treatment_id DESC LIMIT 1");
    if ($queryLast && $queryLast->num_rows > 0) {
        $row = $queryLast->fetch_assoc();
        $lastID = intval(substr($row['treatment_id'], 2)) + 1;
        $treatment_id = "TR" . str_pad($lastID, 4, "0", STR_PAD_LEFT);
    } else {
        $treatment_id = "TR0001";
    }

    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $insert = $con->prepare("INSERT INTO treatment_history 
        (treatment_id, patient_id, treatment, prescription_given, notes, treatment_cost, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$insert) {
        echo "<script>alert('Database prepare error: " . addslashes($con->error) . "'); window.history.back();</script>";
        exit;
    }

    $insert->bind_param(
        "sssssdss",
        $treatment_id,
        $patient_id,
        $treatment,
        $prescription_given,
        $notes,
        $treatment_cost,
        $created_at,
        $updated_at
    );

    // 🧾 Execute and validate
    if ($insert->execute()) {

        // 🦷 Optional: Mark appointment as complete if provided
        if (!empty($appointment_id)) {
            $updateStatus = $con->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
            $updateStatus->bind_param("s", $appointment_id);
            $updateStatus->execute();
            $updateStatus->close();
        }

        echo "<script>
                alert('✅ Treatment record saved successfully!');
                window.location.href = 'admin.php#appointment';
              </script>";
    } else {
        echo "<script>
                alert('❌ Failed to save treatment record. Please try again.');
                window.history.back();
              </script>";
    }

    $insert->close();
    $con->close();

} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}
?>
