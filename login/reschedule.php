<?php
session_start();
include_once("config.php");
define("TITLE", "Reschedule Appointment");
include_once('../header.php');

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['userID'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    echo "<script>alert('Invalid appointment ID.'); window.location.href='account.php';</script>";
    exit();
}

// Verify the appointment belongs to the logged-in user and fetch appointment details
$appointmentQuery = $con->prepare("
    SELECT a.*, s.service_category, s.sub_service
    FROM appointments a
    INNER JOIN patient_information p ON a.patient_id = p.patient_id
    LEFT JOIN services s ON a.service_id = s.service_id
    WHERE a.appointment_id = ? AND p.user_id = ?
");
$appointmentQuery->bind_param("ss", $appointment_id, $user_id);
$appointmentQuery->execute();
$appointmentResult = $appointmentQuery->get_result();
$appointment = $appointmentResult->fetch_assoc();

if (!$appointment) {
    echo "<script>alert('Appointment not found or you do not have permission to reschedule this appointment.'); window.location.href='account.php';</script>";
    exit();
}

// Check if appointment can be rescheduled
if ($appointment['status'] === 'Cancelled' || $appointment['status'] === 'Complete' || $appointment['status'] === 'Completed') {
    echo "<script>alert('This appointment cannot be rescheduled.'); window.location.href='account.php';</script>";
    exit();
}

// Time Slot Mapping
$timeMap = [
    'firstBatch'   => '8:00AM-9:00AM',
    'secondBatch'  => '9:00AM-10:00AM',
    'thirdBatch'   => '10:00AM-11:00AM',
    'fourthBatch'  => '11:00AM-12:00PM',
    'fifthBatch'   => '1:00PM-2:00PM',
    'sixthBatch'   => '2:00PM-3:00PM',
    'sevenBatch'   => '3:00PM-4:00PM',
    'eightBatch'   => '4:00PM-5:00PM',
    'nineBatch'    => '5:00PM-6:00PM',
    'tenBatch'     => '6:00PM-7:00PM',
    'lastBatch'    => '7:00PM-8:00PM'
];

// Process POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_date = $_POST['new_date'] ?? null;
    $new_time_slot = $_POST['new_time_slot'] ?? null;

    // Validate inputs
    if (!$new_date || !$new_time_slot || !isset($timeMap[$new_time_slot])) {
        echo '<script>alert("Please complete all required fields."); window.location.href="reschedule.php?id=' . $appointment_id . '";</script>';
        exit();
    }

    // Prevent past dates
    $today = date("Y-m-d");
    if ($new_date < $today) {
        echo '<script>alert("New date cannot be in the past."); window.location.href="reschedule.php?id=' . $appointment_id . '";</script>';
        exit();
    }

    $new_time = $timeMap[$new_time_slot];

    // Check if the time slot is already booked for that date (excluding current appointment)
    $checkSlot = $con->prepare("
        SELECT appointment_id FROM appointments 
        WHERE appointment_date = ? AND time_slot = ? AND appointment_id != ? AND status NOT IN ('Cancelled', 'No-show')
    ");
    $checkSlot->bind_param("sss", $new_date, $new_time_slot, $appointment_id);
    $checkSlot->execute();
    $slotResult = $checkSlot->get_result();

    if ($slotResult->num_rows > 0) {
        echo '<script>alert("This time slot is already booked. Please select another time."); window.location.href="reschedule.php?id=' . $appointment_id . '";</script>';
        exit();
    }

    // Update appointment
    $stmt = $con->prepare("
        UPDATE appointments 
        SET appointment_date = ?, appointment_time = ?, time_slot = ?, status = 'Reschedule' 
        WHERE appointment_id = ?
    ");
    $stmt->bind_param("ssss", $new_date, $new_time, $new_time_slot, $appointment_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo '<script>
            alert("Appointment rescheduled successfully!");
            window.location.href = "account.php?reschedule=success";
        </script>';
    } else {
        echo '<script>
            alert("Failed to reschedule the appointment. Please try again.");
            window.location.href = "account.php?reschedule=failed";
        </script>';
    }

    $stmt->close();
    exit();
}

// Calculate date range (today to 1 month later)
$today = date('Y-m-d');
$oneMonthLater = date('Y-m-d', strtotime('+1 month'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo TITLE; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="accountstyle.css">
    <style>
        .reschedule-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
        }
        .reschedule-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .reschedule-header {
            margin-bottom: 30px;
        }
        .reschedule-header h2 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        .current-appointment {
            background: var(--light-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .current-appointment h3 {
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .current-appointment .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .current-appointment .info-row:last-child {
            border-bottom: none;
        }
        .current-appointment .info-label {
            font-weight: 600;
            color: var(--text-light);
        }
        .current-appointment .info-value {
            color: var(--secondary-color);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s;
        }
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .btn-secondary {
            background: var(--text-light);
            color: white;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .time-slot-disabled {
            color: #cbd5e1;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="reschedule-container">
    <div class="reschedule-card">
        <div class="reschedule-header">
            <h2>Reschedule Appointment</h2>
            <p>Select a new date and time for your appointment</p>
        </div>

        <div class="current-appointment">
            <h3>Current Appointment Details</h3>
            <div class="info-row">
                <span class="info-label">Service:</span>
                <span class="info-value"><?= htmlspecialchars($appointment['sub_service'] ?? $appointment['service_category'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Date:</span>
                <span class="info-value"><?= date('F j, Y', strtotime($appointment['appointment_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Time:</span>
                <span class="info-value"><?= htmlspecialchars($appointment['appointment_time']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= htmlspecialchars($appointment['status']); ?></span>
            </div>
        </div>

        <form method="POST" action="reschedule.php?id=<?= htmlspecialchars($appointment_id); ?>" id="rescheduleForm">
            <div class="form-group">
                <label for="new_date">New Date *</label>
                <input type="date" id="new_date" name="new_date" 
                       min="<?= $today; ?>" 
                       max="<?= $oneMonthLater; ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="new_time_slot">New Time Slot *</label>
                <select id="new_time_slot" name="new_time_slot" required>
                    <option value="">Select a time slot</option>
                    <?php foreach ($timeMap as $slot => $time): ?>
                        <option value="<?= $slot; ?>"><?= $time; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Reschedule Appointment</button>
                <a href="account.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('new_date');
    const timeSelect = document.getElementById('new_time_slot');
    
    // Update available time slots when date changes
    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        if (!selectedDate) return;
        
        // Disable all options first
        Array.from(timeSelect.options).forEach(option => {
            if (option.value) {
                option.disabled = false;
                option.classList.remove('time-slot-disabled');
            }
        });
        
        // Fetch booked slots for the selected date
        fetch(`../getAppointments.php?date=${selectedDate}`)
            .then(response => response.json())
            .then(bookedSlots => {
                // Map time display to slot values
                const slotToTimeMap = {
                    'firstBatch': '8:00AM-9:00AM',
                    'secondBatch': '9:00AM-10:00AM',
                    'thirdBatch': '10:00AM-11:00AM',
                    'fourthBatch': '11:00AM-12:00PM',
                    'fifthBatch': '1:00PM-2:00PM',
                    'sixthBatch': '2:00PM-3:00PM',
                    'sevenBatch': '3:00PM-4:00PM',
                    'eightBatch': '4:00PM-5:00PM',
                    'nineBatch': '5:00PM-6:00PM',
                    'tenBatch': '6:00PM-7:00PM',
                    'lastBatch': '7:00PM-8:00PM'
                };
                
                // Create reverse map: time -> slot
                const timeToSlotMap = {};
                Object.keys(slotToTimeMap).forEach(slot => {
                    timeToSlotMap[slotToTimeMap[slot]] = slot;
                });
                
                // Disable booked slots
                Array.from(timeSelect.options).forEach(option => {
                    if (option.value && bookedSlots.includes(option.value)) {
                        option.disabled = true;
                        option.classList.add('time-slot-disabled');
                    }
                });
                
                // If currently selected time is now disabled, clear selection
                if (timeSelect.options[timeSelect.selectedIndex].disabled) {
                    timeSelect.value = '';
                }
            })
            .catch(error => {
                console.error('Error fetching available slots:', error);
            });
    });
    
    // Form validation
    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        const selectedDate = dateInput.value;
        const selectedTime = timeSelect.value;
        
        if (!selectedDate || !selectedTime) {
            e.preventDefault();
            alert('Please select both date and time.');
            return false;
        }
        
        if (timeSelect.options[timeSelect.selectedIndex].disabled) {
            e.preventDefault();
            alert('The selected time slot is not available. Please choose another time.');
            return false;
        }
        
        return confirm('Are you sure you want to reschedule this appointment?');
    });
});
</script>

<?php include_once('../footer.php'); ?>
</body>
</html>
