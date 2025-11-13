<?php
session_start();
include_once("config.php");

if (!isset($_SESSION['userID']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['admin_verified'])) {
    header("Location: admin_verify.php");
    exit();

}

$sql = "SELECT a.appointment_id, p.patient_id, p.first_name, p.last_name, s.service_category, s.sub_service,
               d.first_name as dentist_first, d.last_name as dentist_last,
               a.appointment_date, a.appointment_time, a.status, a.branch
        FROM appointments a
        LEFT JOIN patient_information p ON a.patient_id = p.patient_id
        LEFT JOIN services s ON a.service_id = s.service_id
        LEFT JOIN multidisciplinary_dental_team d ON a.team_id = d.team_id
        ORDER BY a.appointment_date ASC";
$result = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dental Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="adminstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../landerologo.png">
    </div>
    <nav class="sidebar-nav">
        <a href="#" class="active" onclick="showSection('dashboard', this)"><i class="fa fa-tachometer"></i> Dashboard</a>
        <a href="#appointment" onclick="showSection('appointment', this)"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="#schedules" onclick="showSection('schedules', this)"><i class="fas fa-calendar-days"></i> Schedules</a>
        <a href="#services" onclick="showSection('services', this)"><i class="fa-solid fa-teeth"></i> Services</a>
        <a href="#patients" onclick="showSection('patients', this)"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="#treatment" onclick="showSection('treatment', this)"><i class="fa-solid fa-notes-medical"></i> History</a>
        <a href="#dentists" onclick="showSection('dentists', this)"><i class="fa-solid fa-user-doctor"></i> Dentists & Staff</a>
        <a href="#payments" onclick="showSection('payment', this)"><i class="fa-solid fa-money-bill"></i> Transactions</a> 
        <a href="#reports" onclick="showSection('reports', this)"><i class="fa-solid fa-square-poll-vertical"></i> Reports</a> 
        <a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>

<?php
    // Get total number of appointments
    $appointmentCountQuery = "SELECT COUNT(*) AS total_appointments FROM appointments";
    $appointmentCountResult = mysqli_query($con, $appointmentCountQuery);
    $appointmentCount = mysqli_fetch_assoc($appointmentCountResult)['total_appointments'];

    // Get total number of services
    $servicesCountQuery = "SELECT COUNT(*) AS total_services FROM services";
    $servicesCountResult = mysqli_query($con, $servicesCountQuery);
    $servicesCount = mysqli_fetch_assoc($servicesCountResult)['total_services'];

    // Get number of active dentists
    $activeDentistQuery = "SELECT COUNT(*) AS active_dentists FROM multidisciplinary_dental_team WHERE status = 'active'";
    $activeDentistResult = mysqli_query($con, $activeDentistQuery);
    $activeDentists = mysqli_fetch_assoc($activeDentistResult)['active_dentists'];

    // Get today's appointments
    $todaysAppointmentsQuery = "SELECT a.appointment_id, p.first_name, p.last_name, s.service_category,
                                       d.first_name as dentist_first, d.last_name as dentist_last,
                                       a.appointment_date, a.appointment_time, a.status
                                FROM appointments a
                                LEFT JOIN patient_information p ON a.patient_id = p.patient_id
                                LEFT JOIN services s ON a.service_id = s.service_id
                                LEFT JOIN multidisciplinary_dental_team d ON a.team_id = d.team_id
                                WHERE a.appointment_date = CURDATE() AND a.status != 'Cancelled' 
                                ORDER BY a.appointment_time ASC";
    $todaysAppointmentsResult = mysqli_query($con, $todaysAppointmentsQuery);
    $todaysAppointmentsCount = mysqli_num_rows($todaysAppointmentsResult);

    // Get today's appointment summary by hour
    $summaryQuery = "SELECT HOUR(appointment_time) AS hour, COUNT(*) AS total 
                     FROM appointments 
                     WHERE appointment_date = CURDATE() 
                     GROUP BY HOUR(appointment_time) 
                     ORDER BY hour";
    $summaryResult = mysqli_query($con, $summaryQuery);

    $appointmentHours = [];
    $appointmentCounts = [];

    while ($row = mysqli_fetch_assoc($summaryResult)) {
        $appointmentHours[] = $row['hour'] . ':00';
        $appointmentCounts[] = $row['total'];
    }

    // Upcoming Appointments
    $upcomingAppointmentsQuery = "SELECT a.appointment_id, p.first_name, p.last_name, 
                                         a.appointment_date, a.appointment_time
                                  FROM appointments a
                                  LEFT JOIN patient_information p ON a.patient_id = p.patient_id
                                  WHERE a.appointment_date > CURDATE() AND a.status != 'Cancelled' 
                                  ORDER BY a.appointment_date ASC, a.appointment_time ASC 
                                  LIMIT 5";
    $upcomingAppointmentsResult = mysqli_query($con, $upcomingAppointmentsQuery);
    $upcomingAppointmentsCount = mysqli_num_rows($upcomingAppointmentsResult);
?>

<div class="main-content" id="dashboard">
    <h1>Dashboard Overview</h1>
    <p>Welcome Admin!</p>

    <!-- Stats Section -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <i class="fas fa-calendar-check fa-2x"></i>
            <div class="stat-info">
                <h3><?php echo $appointmentCount; ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>

        <div class="stat-card">
            <i class="fas fa-user-md fa-2x"></i>
            <div class="stat-info">
                <h3><?php echo $activeDentists; ?></h3>
                <p>Active Dentists</p>
            </div>
        </div>

        <div class="stat-card">
            <i class="fa-solid fa-teeth"></i>
            <div class="stat-info">
                <h3><?php echo $servicesCount; ?></h3>
                <p>Total Services</p>
            </div>
        </div>
    </div>

    <!-- Appointments Side-by-Side Layout -->
    <div class="appointments-container" style="display: flex; flex-wrap: wrap; gap: 20px;">
        <!-- Today's Appointments Section -->
        <div class="today-appointments">
            <h2>Today's Appointments (<?php echo $todaysAppointmentsCount; ?>)</h2>

            <?php if ($todaysAppointmentsCount > 0) { ?>
                <div class="appointments-table">
                    <div class="appointments-table-header">
                        <div class="appointments-table-column"><strong>Time</strong></div>
                        <div class="appointments-table-column"><strong>Patient Name</strong></div>
                        <div class="appointments-table-column"><strong>Service</strong></div>
                        <div class="appointments-table-column"><strong>Dentist</strong></div>
                        <div class="appointments-table-column"><strong>Status</strong></div>
                    </div>

                    <?php while ($row = mysqli_fetch_assoc($todaysAppointmentsResult)) { ?>
                        <div class="appointments-table-row">
                            <div class="appointments-table-column"><?php echo htmlspecialchars($row['appointment_time']); ?></div>
                            <div class="appointments-table-column">
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </div>
                            <div class="appointments-table-column">
                                <?php echo htmlspecialchars($row['service_category']); ?>
                            </div>
                            <div class="appointments-table-column">
                                <?php echo htmlspecialchars($row['dentist_first'] . ' ' . $row['dentist_last']); ?>
                            </div>
                            <div class="appointments-table-column"><?php echo htmlspecialchars($row['status']); ?></div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No appointments scheduled for today.</p>
            <?php } ?>
        </div>

        <div class="upcoming-appointments">
            <h2>Upcoming Appointments (<?php echo $upcomingAppointmentsCount; ?>)</h2>

            <?php if ($upcomingAppointmentsCount > 0) { ?>
                <div class="appointments-table">
                    <div class="appointments-table-header">
                        <div class="appointments-table-column"><strong>Date</strong></div>
                        <div class="appointments-table-column"><strong>Time</strong></div>
                        <div class="appointments-table-column"><strong>Patient</strong></div>
                    </div>

                    <?php while ($row = mysqli_fetch_assoc($upcomingAppointmentsResult)) { ?>
                        <div class="appointments-table-row">
                            <div class="appointments-table-column"><?php echo date('M j', strtotime($row['appointment_date'])); ?></div>
                            <div class="appointments-table-column"><?php echo htmlspecialchars($row['appointment_time']); ?></div>
                            <div class="appointments-table-column">
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No upcoming appointments.</p>
            <?php } ?>
        </div>
    </div>

    <div class="graph-container" style="margin-top: 30px;">
        <h3>Appointment Time Summary</h3>
        <canvas id="appointmentSummaryChart" width="500" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const timeLabels = <?php echo json_encode($appointmentHours); ?>;
        const appointmentData = <?php echo json_encode($appointmentCounts); ?>;

        const ctx = document.getElementById('appointmentSummaryChart').getContext('2d');

        // Predefined set of 5 colors
        const barColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
        ];

        // Repeat the color set if there are more than 5 bars
        const colorsForBars = appointmentData.map((_, index) => barColors[index % barColors.length]);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Appointments per Hour',
                    data: appointmentData,
                    backgroundColor: colorsForBars,
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Today\'s Appointment Distribution by Time'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, 
                        stepSize: 1,         
                        title: {
                            display: true,
                            text: 'Number of Patients'
                        },
                        ticks: {
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time (Hourly)'
                        }
                    }
                }
            }
        });
    </script>
</div>

<!-- Appointment Details -->
<div class="main-content" id="appointment" style="display:none">
    <div class="container">
        <h2><i class="fas fa-calendar-alt"></i> APPOINTMENTS</h2>
        
        <div class="filter-container">
            <div class="filter-group">
                <label for="filter-date"><i class="fas fa-calendar-day"></i> Date:</label>
                <input type="date" id="filter-date" onchange="filterAppointments()">
            </div>
            
            <div class="filter-group">
                <label for="filter-status"><i class="fas fa-filter"></i> Status:</label>
                <select id="filter-status" onchange="filterAppointments()">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="reschedule">Reschedule</option>
                    <option value="complete">Complete</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="no-show">No-Show</option>
                </select> 
            </div>

            <button class="btn btn-primary" id="openAddAppointmentBtn">
                <i class="fa-solid fa-calendar-plus"></i> Add Appointment
            </button>
            
            <button class="btn btn-accent" onclick="printAppointments()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

        <div class="table-responsive">
            <table id="appointments-table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Service</th>
                        <th>Dentist</th>
                        <th>Appointment Date</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) { 
                            $statusClass = 'status-' . strtolower($row['status']);
                    ?>
                        <tr class="appointment-row" data-date="<?php echo $row['appointment_date']; ?>" data-status="<?php echo strtolower($row['status']); ?>">
                            <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['sub_service']); ?></td>
                            <td><?php echo htmlspecialchars($row['dentist_first'] . ' ' . $row['dentist_last']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td><span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <div class="action-btns">
                                    <form action="confirmAppointment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                        <button type="submit" class="action-btn btn-primary-confirmed" title="Confirm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <a href="#" 
                                        class="action-btn btn-accent" 
                                        id="reschedBtn<?= $row['appointment_id'] ?>" 
                                        data-id="<?= $row['appointment_id'] ?>"
                                        onclick="openReschedModalWithID(this)"
                                        title="Reschedule">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>

                                    <button type="button" class="action-btn btn-completed" title="Mark as Completed"
                                        data-patientid="<?php echo htmlspecialchars($row['patient_id']); ?>"
                                        data-appointmentid="<?php echo htmlspecialchars($row['appointment_id']); ?>"
                                        onclick="openCompleteAppointmentModal(this)">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </button>
                                    

                                    <form action="noshowAppointment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                        <button class="action-btn btn-danger" title="No-Show">
                                            <i class="fa-regular fa-eye-slash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-calendar-times fa-2x"></i>
                                <p>No appointments found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complete Appointment Modal -->
<div id="complete-appointment-modal" class="complete-appointment-modal">
    <div class="complete-appointment-content">
        <div class="complete-appointment-header">
            <h3><i class="fa-solid fa-check-to-slot"></i>Complete Appointment</h3>
            <span class="complete-appointment-close">&times;</span>
        </div>
        <div class="complete-appointment-body">
            <form id="treatmentForm" action="saveTreatment.php" method="post">
                <input type="hidden" id="treatment_patient_id" name="patient_id">
                <input type="hidden" id="treatment_appointment_id" name="appointment_id">

                <div class="complete-appointment-form-group">
                    <label for="patient-id">Patient ID:</label>
                    <input type="text" id="patient_id" name="patient_id" value="<?php echo isset($patient_id) ? htmlspecialchars($patient_id) : ''; ?>" readonly>
                </div>
                
                <div class="complete-appointment-form-group">
                    <label for="treatment_type">Treatment:</label>
                    <input type="text" id="treatment_type" name="treatment" required>
                </div>
                
                <div class="complete-appointment-form-group">
                    <label for="prescription_given">Prescription:</label>
                    <input type="text" id="prescription_given" name="prescription_given" required>
                </div>
                
                <div class="complete-appointment-form-group">
                    <label for="treatment_notes">Notes:</label>
                    <input type="text" id="treatment_notes" name="treatment_notes" required>
                </div>
                
                <div class="complete-appointment-form-group">
                    <label for="treatment_cost">Treatment Cost (₱):</label>
                    <input type="number" id="treatment_cost" name="treatment_cost" step="0.01" min="0" required>
                </div>
                
                <div class="complete-appointment-actions">
                    <button type="button" class="btn btn-danger" id="cancelCompleteAppointment">CANCEL</button>
                    <button type="submit" class="btn btn-completed">COMPLETE AND SAVE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Appointment Modal -->
<?php
$patientsQuery = "
    SELECT p.patient_id, p.first_name, p.last_name
    FROM patient_information p
    ORDER BY p.patient_id ASC
";

$patientsResult = mysqli_query($con, $patientsQuery);

$patientsMap = [];
while ($row = mysqli_fetch_assoc($patientsResult)) {
    $fullName = $row['first_name'] . ' ' . $row['last_name'];
    $patientsMap[$row['patient_id']] = $fullName;
}

// Get services
$servicesQuery = "SELECT service_id, service_category, sub_service FROM services";
$servicesResult = mysqli_query($con, $servicesQuery);

// Get dentists
$dentistsQuery = "SELECT team_id, first_name, last_name FROM multidisciplinary_dental_team WHERE status = 'active'";
$dentistsResult = mysqli_query($con, $dentistsQuery);
?>

<div id="addAppointmentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>ADD NEW APPOINTMENT</h3>

        <form action="addAppointment.php" method="POST">
            <!-- Row 1: Patient ID and Patient Name -->
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="patient_id">Patient ID:</label>
                    <select name="patient_id" id="patient_id" onchange="updatePatientName()" required>
                        <option value="">Select Patient ID</option>
                        <?php
                        foreach ($patientsMap as $id => $name) {
                            echo "<option value=\"$id\">$id</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="patient_name">Patient Name:</label>
                    <input type="text" name="patient_name" id="patient_name" readonly required>
                </div>
            </div>

            <!-- Row 2: Service and Dentist -->
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="service_id">Service:</label>
                    <select name="service_id" id="service_id" required>
                        <option value="">Select Service</option>
                        <?php
                        while ($service = mysqli_fetch_assoc($servicesResult)) {
                            echo "<option value=\"{$service['service_id']}\">{$service['service_category']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="flex: 1;">
                    <label for="team_id">Dentist:</label>
                    <select name="team_id" id="team_id" required>
                        <option value="">Select Dentist</option>
                        <?php
                        while ($dentist = mysqli_fetch_assoc($dentistsResult)) {
                            echo "<option value=\"{$dentist['team_id']}\">Dr. {$dentist['first_name']} {$dentist['last_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Row 3: Date and Time -->
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="appointment_date">Appointment Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                </div>

                <div style="flex: 1;">
                    <label for="appointment_time">Appointment Time:</label>
                    <select name="time_slot" id="appointment_time" required>
                        <option value="">Select Time</option>
                        <option value="firstBatch">Morning (8AM-9AM)</option>
                        <option value="secondBatch">Morning (9AM-10AM)</option>
                        <option value="thirdBatch">Morning (10AM-11AM)</option>
                        <option value="fourthBatch">Afternoon (11AM-12PM)</option>
                        <option value="fifthBatch">Afternoon (1PM-2PM)</option>
                        <option value="sixthBatch">Afternoon (2PM-3PM)</option>
                        <option value="sevenBatch">Afternoon (3PM-4PM)</option>
                        <option value="eightBatch">Afternoon (4PM-5PM)</option>
                        <option value="nineBatch">Afternoon (5PM-6PM)</option>
                        <option value="tenBatch">Evening (6PM-7PM)</option>
                        <option value="lastBatch">Evening (7PM-8PM)</option>
                    </select>
                </div>
            </div>

            <!-- Branch -->
            <div style="margin-top: 10px;">
                <label for="branch">Branch:</label>
                <select name="branch" id="branch" required>
                    <option value="">Select Branch</option>
                    <option value="Main">Main Branch</option>
                    <option value="North">North Branch</option>
                    <option value="South">South Branch</option>
                </select>
            </div>

            <!-- Buttons -->
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Save Appointment</button>
                <button type="button" onclick="closeAddAppointmentModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="reschedModal" class="modal">
    <div class="modal-content">
        <h3>Reschedule Appointment</h3>
        <form action="rescheduleAppointment.php" method="POST">
            <input type="hidden" id="modalAppointmentID" name="appointment_id">
            
            <label for="new_date">Select New Date:</label>
            <input type="date" id="new_date_resched" name="new_date_resched" required min="<?= date('Y-m-d') ?>">

            <label for="new_time">Select New Time:</label>
            <select id="new_time_resched" name="new_time_slot" required>
                <option value="">Select Time</option>
                <option value="firstBatch">Morning (8AM-9AM)</option>
                <option value="secondBatch">Morning (9AM-10AM)</option>
                <option value="thirdBatch">Morning (10AM-11AM)</option>
                <option value="fourthBatch">Afternoon (11AM-12PM)</option>
                <option value="fifthBatch">Afternoon (1PM-2PM)</option>
                <option value="sixthBatch">Afternoon (2PM-3PM)</option>
                <option value="sevenBatch">Afternoon (3PM-4PM)</option>
                <option value="eightBatch">Afternoon (4PM-5PM)</option>
                <option value="nineBatch">Afternoon (5PM-6PM)</option>
                <option value="tenBatch">Evening (6PM-7PM)</option>
                <option value="lastBatch">Evening (7PM-8PM)</option>
            </select>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">CONFIRM SCHEDULE</button>
                <button type="button" onclick="closeReschedModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Dentist Schedule -->
<div id="schedules" class="main-content" style="display:none">
    <div class="container">
            <h2><i class="fa-solid fa-calendar-days"></i> DENTIST SCHEDULE MANAGEMENT</h2>
            
            <div class="schedule-controls">
                <div class="control-group">
                    <label for="dentistSelectSchedule">Select Dentist:</label>
                    <select id="dentistSelectSchedule">
                        <option value="">Select Dentist</option>
                        <?php
                        $dentistsQuery = "SELECT team_id, first_name, last_name FROM multidisciplinary_dental_team WHERE status = 'active'";
                        $dentistsResult = mysqli_query($con, $dentistsQuery);
                        while ($dentist = mysqli_fetch_assoc($dentistsResult)) {
                            echo "<option value='{$dentist['team_id']}'>Dr. {$dentist['first_name']} {$dentist['last_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="viewType">View Type:</label>
                    <select id="viewType" onchange="changeScheduleView()">
                        <option value="weekly">Weekly View</option>
                        <option value="monthly">Monthly View</option>
                    </select>
                </div>
                
                <button class="btn btn-primary" onclick="openAddBlockModal()">
                    <i class="fa-solid fa-ban"></i> Block Time Slots
                </button>
                
                <button class="btn btn-success" onclick="openAddAvailabilityModal()">
                    <i class="fa-solid fa-calendar-plus"></i> Add Special Availability
                </button>
            </div>

            <!-- Weekly Schedule View -->
            <div id="weeklyView" class="schedule-view">
                <div class="week-navigation">
                    <button class="btn btn-accent" onclick="changeWeek(-1)">
                        <i class="fas fa-chevron-left"></i> Previous Week
                    </button>
                    <h3 id="currentWeekRange">Week of ...</h3>
                    <button class="btn btn-accent" onclick="changeWeek(1)">
                        Next Week <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div class="weekly-schedule">
                    <div class="time-slots-header">
                        <div class="time-label">Time</div>
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        $currentDate = new DateTime();
                        $currentDate->modify('monday this week');
                        
                        for ($i = 0; $i < 6; $i++) {
                            $dayDate = clone $currentDate;
                            $dayDate->modify("+$i days");
                            echo "<div class='day-header'>";
                            echo "<div class='day-name'>{$days[$i]}</div>";
                            echo "<div class='day-date'>{$dayDate->format('M j')}</div>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <div class="time-slots-container">
                        <?php
                        $timeSlots = [
                            'firstBatch' => '8:00-9:00 AM',
                            'secondBatch' => '9:00-10:00 AM',
                            'thirdBatch' => '10:00-11:00 AM',
                            'fourthBatch' => '11:00-12:00 PM',
                            'fifthBatch' => '1:00-2:00 PM',
                            'sixthBatch' => '2:00-3:00 PM',
                            'sevenBatch' => '3:00-4:00 PM',
                            'eightBatch' => '4:00-5:00 PM',
                            'nineBatch' => '5:00-6:00 PM',
                            'tenBatch' => '6:00-7:00 PM',
                            'lastBatch' => '7:00-8:00 PM'
                        ];

                        foreach ($timeSlots as $slotKey => $slotTime) {
                            echo "<div class='time-slot-row'>";
                            echo "<div class='time-label'>{$slotTime}</div>";
                            
                            for ($i = 0; $i < 6; $i++) {
                                $dayDate = clone $currentDate;
                                $dayDate->modify("+$i days");
                                $dateString = $dayDate->format('Y-m-d');
                                
                                echo "<div class='time-slot-cell' data-date='{$dateString}' data-slot='{$slotKey}'>";
                                echo "<div class='slot-status available' onclick=\"toggleTimeSlot(this, '{$dateString}', '{$slotKey}')\">";
                                echo "<i class='fas fa-check-circle'></i>";
                                echo "<span>Available</span>";
                                echo "</div>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Monthly View -->
            <div id="monthlyView" class="schedule-view" style="display:none;">
                <div class="month-navigation">
                    <button class="btn btn-accent" onclick="changeMonth(-1)">
                        <i class="fas fa-chevron-left"></i> Previous Month
                    </button>
                    <h3 id="currentMonth">Month Year</h3>
                    <button class="btn btn-accent" onclick="changeMonth(1)">
                        Next Month <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div class="monthly-calendar" id="monthlyCalendar">
                    <!-- Monthly calendar will be generated by JavaScript -->
                </div>
            </div>

            <!-- Blocked Time Slots List -->
            <div class="blocked-slots-section">
                <h3><i class="fa-solid fa-clock"></i> Blocked Time Slots</h3>
                <div class="table-responsive">
                    <table id="blockedSlotsTable">
                        <thead>
                            <tr>
                                <th>Dentist</th>
                                <th>Date</th>
                                <th>Time Slot</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="blockedSlotsBody">
                            <!-- Blocked slots will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Block Time Slots Modal -->
    <div id="blockTimeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3><i class="fa-solid fa-ban"></i> Block Time Slots</h3>
            <form id="blockTimeForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="blockDentist">Dentist:</label>
                        <select id="blockDentist" name="dentist_id" required>
                            <option value="">Select Dentist</option>
                            <?php
                            $dentistsResult = mysqli_query($con, $dentistsQuery);
                            while ($dentist = mysqli_fetch_assoc($dentistsResult)) {
                                echo "<option value='{$dentist['team_id']}'>Dr. {$dentist['first_name']} {$dentist['last_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="blockDate">Date:</label>
                        <input type="date" id="blockDate" name="block_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Time Slots to Block:</label>
                    <div class="time-slots-checkboxes">
                        <?php foreach ($timeSlots as $slotKey => $slotTime): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="time_slots[]" value="<?= $slotKey ?>">
                                <span class="checkmark"></span>
                                <?= $slotTime ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="blockReason">Reason:</label>
                    <select id="blockReason" name="reason" required>
                        <option value="">Select Reason</option>
                        <option value="Vacation">Vacation</option>
                        <option value="Training">Training</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Personal">Personal</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customReason">Custom Reason (if Other):</label>
                    <input type="text" id="customReason" name="custom_reason" placeholder="Enter custom reason">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Block Selected Slots</button>
                    <button type="button" class="btn btn-secondary" onclick="closeBlockModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Special Availability Modal -->
    <div id="addAvailabilityModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3><i class="fa-solid fa-calendar-plus"></i> Add Special Availability</h3>
            <form id="addAvailabilityForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="availDentist">Dentist:</label>
                        <select id="availDentist" name="dentist_id" required>
                            <option value="">Select Dentist</option>
                            <?php
                            $dentistsResult = mysqli_query($con, $dentistsQuery);
                            while ($dentist = mysqli_fetch_assoc($dentistsResult)) {
                                echo "<option value='{$dentist['team_id']}'>Dr. {$dentist['first_name']} {$dentist['last_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="availDate">Date:</label>
                        <input type="date" id="availDate" name="avail_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Available Time Slots:</label>
                    <div class="time-slots-checkboxes">
                        <?php foreach ($timeSlots as $slotKey => $slotTime): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="time_slots[]" value="<?= $slotKey ?>">
                                <span class="checkmark"></span>
                                <?= $slotTime ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="availNotes">Notes:</label>
                    <textarea id="availNotes" name="notes" placeholder="Any special notes about this availability..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Add Availability</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAvailabilityModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Services -->
<div id="services" class="main-content" style="display:none;">
    <div class="container">
        <h2><i class="fas fa-procedures"></i> SERVICES</h2>
        <button class="btn btn-primary" id="openAddServiceBtn">ADD NEW SERVICE</button>

        <?php
            $servicesSql = "SELECT service_id, service_category, sub_service, description, price FROM services";
            $servicesResult = mysqli_query($con, $servicesSql);
        ?>

        <div class="table-responsive">
            <table id="services-table">
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Service Category</th>
                        <th>Sub Service</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($servicesResult) > 0) {
                        while ($row = mysqli_fetch_assoc($servicesResult)) { 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['service_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['service_category']); ?></td>
                            <td><?php echo htmlspecialchars($row['sub_service']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>₱<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn btn-primary" title="Edit" onclick="editService('<?php echo $row['service_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="deleteService.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                                        <button type="submit" class="action-btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this service?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                <p>No services found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>ADD SERVICE</h3>
        <form action="addServices.php" method="POST">
            <label for="service_category">Service Category:</label>
            <input type="text" name="service_category" required>

            <label for="sub_service">Sub Service:</label>
            <input type="text" name="sub_service">

            <label for="description">Description:</label>
            <textarea name="description" required></textarea>

            <label for="price">Price (₱):</label>
            <input type="number" name="price" step="0.01" required>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Add Service</button>
                <button type="button" onclick="closeAddModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editServiceModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>EDIT SERVICE</h3>
        <form id="editServiceForm" method="POST" action="updateService.php">
            <input type="hidden" name="service_id" id="editServiceId">

            <label for="editServiceCategory">Service Category:</label>
            <input type="text" name="service_category" id="editServiceCategory" required>

            <label for="editSubService">Sub Service:</label>
            <input type="text" name="sub_service" id="editSubService">

            <label for="editDescription">Description:</label>
            <textarea name="description" id="editDescription" required></textarea>

            <label for="editPrice">Price (₱):</label>
            <input type="number" name="price" id="editPrice" step="0.01" required>
            
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Update Service</button>
                <button type="button" onclick="closeEditModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Patients -->
<div id="patients" class="main-content" style="display:none;">
    <div class="container">
        <h2><i class="fa-solid fa-hospital-user"></i> PATIENTS</h2>

        <?php
            $patientSql = "SELECT patient_id, first_name, last_name, birthdate, gender, email, phone, address 
                          FROM patient_information";
            $patientResult = mysqli_query($con, $patientSql);
        ?>

        <div class="table-responsive">
            <table id="patients-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Birthdate</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($patientResult) > 0) {
                        while ($row = mysqli_fetch_assoc($patientResult)) { 
                            // Calculate age from birthdate
                            $birthdate = new DateTime($row['birthdate']);
                            $today = new DateTime();
                            $age = $birthdate->diff($today)->y;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['birthdate'])); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn btn-primary" title="Edit" onclick="editPatient('<?php echo $row['patient_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="action-btn btn-danger" title="Archive" onclick="editPatient('<?php echo $row['patient_id']; ?>')">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>

                                    <button class="action-btn btn-gray" title="See More" onclick="seeTreatmentHistory('<?php echo $row['patient_id']; ?>')">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </button>
                                </div>

                                
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                <p>No Patients found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>EDIT PATIENT</h3>
        <form id="editPatientForm" method="POST" action="updatePatient.php">
            <input type="hidden" name="patient_id" id="editPatientId">

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="editFirstName">First Name:</label>
                    <input type="text" name="first_name" id="editFirstName" required>
                </div>
                <div style="flex: 1;">
                    <label for="editLastName">Last Name:</label>
                    <input type="text" name="last_name" id="editLastName" required>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="editBirthdate">Birthdate:</label>
                    <input type="date" name="birthdate" id="editBirthdate" required>
                </div>
                <div style="flex: 1;">
                    <label for="editGender">Gender:</label>
                    <select name="gender" id="editGender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <label for="editEmail">Email:</label>
                <input type="email" name="email" id="editEmail" required>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="editPhone">Phone:</label>
                    <input type="text" name="phone" id="editPhone" required>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <label for="editAddress">Address:</label>
                <input type="text" name="address" id="editAddress" required>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Update Patient</button>
                <button type="button" onclick="closeEditPatientModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Treatment History Modal -->
<div id="treatmentHistoryModal" class="treatment-modal" style="display:none;">
    <div class="treatment-modal-content">
        <div class="treatment-modal-header">
            <h3><i class="fa-solid fa-notes-medical"></i> Treatment History</h3>
            <span class="treatment-close-btn" onclick="closeTreatmentModal()">&times;</span>
        </div>
            <div class="treatment-modal-body">
                <table id="treatmentHistoryTable" class="treatment-table" border="1" cellspacing="0" cellpadding="5" style="width:100%;">
                    <thead>
                    <tr>
                        <th>Treatment</th>
                        <th>Prescription</th>
                        <th>Notes</th>
                        <th>Cost (₱)</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody id="treatmentHistoryBody">
                    <tr><td colspan="5" style="text-align:center;">No data available</td></tr>
                    </tbody>
                </table>
            </div>
    </div>
</div>

<!-- Dentists & Staff -->
<div id="dentists" class="main-content" style="display:none;">
    <div class="container">
        <h2><i class="fa-solid fa-user-doctor"></i> DENTISTS AND STAFF</h2>
        <button class="btn btn-primary" id="openAddDentistBtn">ADD NEW DENTIST/STAFF</button>

        <?php
            $dentistSql = "SELECT team_id, first_name, last_name, specialization, email, phone, status 
                          FROM multidisciplinary_dental_team";
            $dentistResult = mysqli_query($con, $dentistSql);
        ?>

        <div class="table-responsive">
            <table id="dentists-table">
                <thead>
                    <tr>
                        <th>Team ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($dentistResult) > 0) {
                        while ($row = mysqli_fetch_assoc($dentistResult)) { 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['team_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn btn-primary-editStaff" title="Edit" onclick="editDentist('<?php echo $row['team_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form action="deleteStaff.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="team_id" value="<?php echo $row['team_id']; ?>">
                                        <button type="submit" class="action-btn btn-deleteStaff" title="Delete" onclick="return confirm('Are you sure you want to delete this staff?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                <p>No Dentists found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div id="addDentistModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>ADD DENTIST/STAFF</h3>
        <form action="addStaff.php" method="POST">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="addFirstName">First Name:</label>
                    <input type="text" name="first_name" id="addFirstName" required>
                </div>
                <div style="flex: 1;">
                    <label for="addLastName">Last Name:</label>
                    <input type="text" name="last_name" id="addLastName" required>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="addSpecialization">Specialization:</label>
                    <input type="text" name="specialization" id="addSpecialization" required>
                </div>
                <div style="flex: 1;">
                    <label for="addStatus">Status:</label>
                    <select name="status" id="addStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="addEmail">Email:</label>
                    <input type="email" name="email" id="addEmail" required>
                </div>
                <div style="flex: 1;">
                    <label for="addPhone">Phone:</label>
                    <input type="text" name="phone" id="addPhone" required>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Add Staff</button>
                <button type="button" onclick="closeDentistModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Dentist Modal -->
<div id="editDentistModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>EDIT DENTIST/STAFF</h3>
        <form id="editDentistForm" method="POST" action="updateStaff.php">
            <input type="hidden" name="team_id" id="editDentistId">

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="editDentistFirstName">First Name:</label>
                    <input type="text" name="first_name" id="editDentistFirstName" required>
                </div>
                <div style="flex: 1;">
                    <label for="editDentistLastName">Last Name:</label>
                    <input type="text" name="last_name" id="editDentistLastName" required>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="editDentistSpecialization">Specialization:</label>
                    <input type="text" name="specialization" id="editDentistSpecialization" required>
                </div>
                <div style="flex: 1;">
                    <label for="editDentistStatus">Status:</label>
                    <select name="status" id="editDentistStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="editDentistEmail">Email:</label>
                    <input type="email" name="email" id="editDentistEmail" required>
                </div>
                <div style="flex: 1;">
                    <label for="editDentistPhone">Phone:</label>
                    <input type="text" name="phone" id="editDentistPhone" required>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Update Details</button>
                <button type="button" onclick="closeEditDentistModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>


<!-- Patient Treatment History -->
<div id="treatment" class="main-content" style="display:none">
    <div class="container">
        <?php
                $historySql = "SELECT patient_id, treatment, prescription_given, treatment_cost, notes
                            FROM treatment_history";

                $historyResult = mysqli_query($con, $historySql);
            ?>
        <h2><i class="fa-solid fa-notes-medical"></i> Patient Treatment History</h2>

        <div class="table-responsive">
            <table id="appointments-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Treatment</th>
                        <th>Prescription Given</th>
                        <th>Treatment Cost</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($historyResult) > 0) {
                        while ($row = mysqli_fetch_assoc($historyResult)) { 
                    ?>
                        <tr class="history-row">
                            <td><?php echo htmlspecialchars($row['patient_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['treatment']); ?></td>
                            <td><?php echo htmlspecialchars($row['prescription_given']); ?></td>
                            <td><?php echo htmlspecialchars($row['treatment_cost']); ?></td>
                            <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <form action="archiveHistory.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="patient_id" value="<?php echo $row['patient_id']; ?>">
                                        <button type="submit" class="action-btn btn-primary-confirmed" title="Archive">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </button>
                                    </form>

                                    
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-calendar-times fa-2x"></i>
                                <p>No Patient History found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Transactions -->
<div id="payment" class="main-content" style="display:none;">
    <div class="container">
        <h2><i class="fa-solid fa-money-bill"></i> PAYMENT TRANSACTIONS</h2>

        <?php
            $paymentSql = "SELECT p.payment_id, p.appointment_id, p.method, p.account_name, 
                                  p.account_number, p.amount, p.reference_no, p.proof_image, p.status,
                                  a.patient_id
                           FROM payment p
                           LEFT JOIN appointments a ON p.appointment_id = a.appointment_id";
            $paymentResult = mysqli_query($con, $paymentSql);
        ?>

        <div class="table-responsive">
            <table id="payment-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Appointment ID</th>
                        <th>Method</th>
                        <th>Account Name</th>
                        <th>Account Number</th>
                        <th>Amount</th>
                        <th>Reference No.</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($paymentResult) > 0) {
                        while ($row = mysqli_fetch_assoc($paymentResult)) { 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['payment_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['method']); ?></td>
                            <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['account_number']); ?></td>
                            <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['reference_no']); ?></td>
                            <td>
                                <?php if (!empty($row['proof_image'])): ?>
                                    <?php 
                                    $clean_path = ltrim($row['proof_image'], '/');
                                    $clean_path = str_replace('uploads/', '', $clean_path);
                                    $image_path = '/uploads/' . $clean_path;
                                    ?>
                                    <button type="button" onclick="viewImage('<?php echo htmlspecialchars($image_path); ?>')" 
                                        style="background:none; border:none; color:#007bff; text-decoration:underline; cursor:pointer;">
                                        View Image
                                    </button>
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <form action="confirmPayment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                        <button type="submit" class="action-btn btn-primary-confirmedPayment" title="Confirm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <form action="failedPayment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                        <button type="submit" class="action-btn btn-danger" title="Mark as failed">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="10" class="no-data">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                <p>No Payment found</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
    <span onclick="closeModal()" style="position:absolute; top:20px; right:30px; font-size:30px; color:white; cursor:pointer;">&times;</span>
    <img id="modalImage" src="" alt="Proof Image" style="max-width:90%; max-height:80%; border:5px solid white; box-shadow:0 0 10px black;">
</div>


<!-- Reports Section -->
<div id="reports" class="main-content" style="display:none;">

    <?php include("chatreport.php")?>
    <div class="container reports-container">
        <h2 class="report-header">
            <i class="fa-solid fa-square-poll-vertical"></i> REPORTS & ANALYTICS
        </h2>

        <!-- Report Selector -->
        <div class="report-selector">
            <label for="reportType">Filter Reports:</label>
            <select id="reportType" onchange="filterReports()">
                <option value="all" selected>Show All Reports</option>
                <!--<option value="overview">Dashboard Overview</option> -->
                <option value="service">Monthly Service Distribution</option>
                <option value="appointments">Appointments Per Day</option>
                <option value="financial">Revenue by Services</option>
            </select>
        </div>

        <!-- Dashboard Overview -->
        <div id="overviewReport" class="report-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-pie"></i> Dashboard Overview</h3>
            </div>
            <?php
            // Total Appointments
            $totalAppointments = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM appointments"))['total'];
            
            // Total Down Payment
            $totaldownPayment = mysqli_fetch_assoc(mysqli_query($con, "SELECT IFNULL(SUM(amount), 0) AS total FROM payment WHERE status = 'paid'"))['total'];

            $totalRevenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT IFNULL(SUM(treatment_cost), 0) AS total FROM treatment_history"))['total'];
            
            // Today's Appointments
            $todayAppointments = mysqli_fetch_assoc(mysqli_query($con, "
                SELECT COUNT(*) AS total FROM appointments 
                WHERE DATE(appointment_date) = CURDATE()
            "))['total'];
            
            // No-Show Rate Calculation
            $noShowData = mysqli_fetch_assoc(mysqli_query($con, "
                SELECT 
                    COUNT(*) as total_appointments,
                    SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_shows
                FROM appointments
            "));
            $noShowRate = $noShowData['total_appointments'] > 0 ? 
                round(($noShowData['no_shows'] / $noShowData['total_appointments']) * 100, 2) : 0;
                
            
            // Appointment Status Breakdown
            $statusQuery = mysqli_query($con, "
                SELECT status, COUNT(*) as count 
                FROM appointments 
                GROUP BY status
            ");
            $appointmentStatuses = [];
            while ($row = mysqli_fetch_assoc($statusQuery)) {
                $appointmentStatuses[$row['status']] = $row['count'];
            }
            
            // Total Downpayment by Services
            $serviceRevenueQuery = mysqli_query($con, "
                SELECT s.service_category, SUM(p.amount) as total_amount
                FROM payment p
                INNER JOIN appointments a ON p.appointment_id = a.appointment_id
                INNER JOIN services s ON a.service_id = s.service_id
                WHERE p.status = 'paid'
                GROUP BY s.service_category
            ");
            $serviceRevenueData = [];
            $serviceRevenueLabels = [];
            $serviceRevenueAmounts = [];
            while ($row = mysqli_fetch_assoc($serviceRevenueQuery)) {
                $serviceRevenueData[] = $row;
                $serviceRevenueLabels[] = $row['service_category'];
                $serviceRevenueAmounts[] = (float)$row['total_amount'];
            }
            
            // Services Availed Count (based on sub_service)
            $servicesAvailedQuery = mysqli_query($con, "
                SELECT s.sub_service, COUNT(*) as count
                FROM appointments a
                INNER JOIN services s ON a.service_id = s.service_id
                GROUP BY s.sub_service
                ORDER BY count DESC
            ");
            $servicesAvailedLabels = [];
            $servicesAvailedCounts = [];
            while ($row = mysqli_fetch_assoc($servicesAvailedQuery)) {
                $servicesAvailedLabels[] = $row['sub_service'];
                $servicesAvailedCounts[] = (int)$row['count'];
            }
            ?>

            <!-- Stats Cards Row -->
            <div class="stats-grid">
                <div class="report-stat-card">
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-value"><?php echo $totalAppointments; ?></div>
                </div>
                <div class="report-stat-card">
                    <div class="stat-label">Total Down Payment</div>
                    <div class="stat-value">₱<?php echo number_format($totaldownPayment, 2); ?></div>
                </div>
                <div class="report-stat-card">
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value"><?php echo $todayAppointments; ?></div>
                </div>
                <div class="report-stat-card">
                    <div class="stat-label">Total Revenue By Services</div>
                    <div class="stat-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="charts-row">
                <!-- Appointment Status Chart -->
                <div class="chart-box">
                    <h3>Appointment Status</h3>
                    <canvas id="appointmentStatusChart"></canvas>
                </div>

                <!-- Total Downpayment by Services -->
                <div class="chart-box">
                    <h3>Total Downpayment by Services</h3>
                    <canvas id="serviceRevenueChart"></canvas>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="charts-row">
                <!-- Additional Stats or Chart Placeholder -->
                <div class="chart-box">
                    <h3>Appointment Summary</h3>
                    <div class="status-summary">
                        <?php
                        $statusColors = [
                            'pending' => '#F59E0B',
                            'confirmed' => '#10B981', 
                            'rescheduled' => '#3B82F6',
                            'cancelled' => '#EF4444',
                            'no-show' => '#6B7280'
                        ];
                        
                        foreach ($appointmentStatuses as $status => $count) {
                            $color = $statusColors[strtolower($status)] ?? '#6B7280';
                            $percentage = $totalAppointments > 0 ? round(($count / $totalAppointments) * 100, 1) : 0;
                            echo "
                            <div class='status-item'>
                                <div class='status-info'>
                                    <div class='status-dot' style='background: $color'></div>
                                    <span class='status-name'>" . ucfirst($status) . "</span>
                                </div>
                                <div class='status-numbers'>
                                    <span class='status-count'>$count</span>
                                    <span class='status-percentage'>($percentage%)</span>
                                </div>
                            </div>
                            ";
                        }
                        ?>
                    </div>
                </div>

                <!-- Services Availed Count -->
                <div class="chart-box">
                    <h3>Services Availed Count</h3>
                    <canvas id="servicesAvailedChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Service Distribution -->
        <div id="serviceReport" class="report-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Monthly Service Distribution</h3>
            </div>
            <?php
            $monthlyServiceData = [];
            $currentYear = date('Y');
            for ($month = 1; $month <= 12; $month++) {
                $sql = "SELECT s.service_category, COUNT(*) AS count
                        FROM appointments a
                        LEFT JOIN services s ON a.service_id = s.service_id
                        WHERE MONTH(a.appointment_date) = $month 
                        AND YEAR(a.appointment_date) = $currentYear
                        GROUP BY s.service_category";
                $result = mysqli_query($con, $sql);
                $services = [];
                $counts = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $services[] = $row['service_category'];
                    $counts[] = (int)$row['count'];
                }
                $monthlyServiceData[$month] = [
                    'labels' => $services,
                    'counts' => $counts,
                    'total' => array_sum($counts)
                ];
            }
            ?>

            <div class="chart-box">
                <div class="chart-controls">
                    <label for="monthSelect">Select Month:</label>
                    <select id="monthSelect" onchange="updateChart()">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $monthName = date('F', mktime(0, 0, 0, $m, 10));
                            $selected = $m == date('n') ? 'selected' : '';
                            echo "<option value='$m' $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
                <canvas id="servicePieChart"></canvas>
                <div id="colorGuide" class="color-guide"></div>
            </div>
        </div>

        <!-- Appointments Per Day -->
        <div id="appointmentsReport" class="report-section">
            <div class="section-header">
                <h3><i class="fas fa-calendar-alt"></i> Appointments Per Day</h3>
            </div>
            <div class="chart-box">
                <?php
                $sql = "SELECT appointment_date, COUNT(*) as count FROM appointments 
                        WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        GROUP BY appointment_date ORDER BY appointment_date";
                $result = mysqli_query($con, $sql);
                $dates = [];
                $counts = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $dates[] = date('M j', strtotime($row['appointment_date']));
                    $counts[] = (int)$row['count'];
                }
                ?>
                <canvas id="appointmentsBarChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Services Report -->
        <div id="financialReport" class="report-section">
            <div class="section-header">
                <h3><i class="fas fa-money-bill-wave"></i> Revenue by Services</h3>
            </div>
            <?php
            // Query to get revenue by services from treatment_history only
            $revenueQuery = mysqli_query($con, "
                SELECT 
                    th.treatment,
                    SUM(th.treatment_cost) as total_revenue,
                    COUNT(*) as treatment_count
                FROM treatment_history th
                WHERE th.treatment_cost > 0
                GROUP BY th.treatment
                ORDER BY total_revenue DESC
            ");
            
            $serviceNames = [];
            $serviceRevenues = [];
            $treatmentCounts = [];
            $totalRevenue = 0;
            
            while ($row = mysqli_fetch_assoc($revenueQuery)) {
                $serviceNames[] = $row['treatment'];
                $serviceRevenues[] = (float)$row['total_revenue'];
                $treatmentCounts[] = (int)$row['treatment_count'];
                $totalRevenue += $row['total_revenue'];
            }
            
            // If no data found, show sample data based on treatment_history
            if (empty($serviceNames)) {
                $serviceNames = ['Braces Adjustment', 'Tooth Filling', 'Dental Cleaning', 'Tooth Extraction', 'Oral Check-up'];
                $serviceRevenues = [3500, 2800, 1500, 1200, 800];
                $treatmentCounts = [12, 8, 15, 6, 20];
                $totalRevenue = array_sum($serviceRevenues);
            }
            ?>

            <!-- Revenue Chart and Details -->
            <div class="revenue-content">
                <div class="chart-container">
                    <div class="chart-box">
                        <canvas id="revenueByServicesChart"></canvas>
                    </div>
                </div>
                
                <!-- Service Details -->
                <div class="service-details">
                    <h4>Service Revenue Details</h4>
                    <div class="service-list">
                        <?php foreach ($serviceNames as $index => $service): ?>
                        <div class="service-item">
                            <div class="service-info">
                                <div class="service-name"><?php echo htmlspecialchars($service); ?></div>
                                <div class="service-stats">
                                    <span class="treatment-count"><?php echo $treatmentCounts[$index]; ?> treatments</span>
                                    <span class="service-revenue">₱<?php echo number_format($serviceRevenues[$index], 2); ?></span>
                                </div>
                            </div>
                            <div class="revenue-percentage">
                                <?php echo $totalRevenue > 0 ? round(($serviceRevenues[$index] / $totalRevenue) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const monthlyData = <?php echo json_encode($monthlyServiceData); ?>;
            const colorPalette = ['#4F46E5', '#22C55E', '#F59E0B', '#EF4444', '#06B6D4', '#8B5CF6', '#84CC16', '#EC4899'];
            let pieChart, appointmentsChart, revenueByServicesChart, appointmentStatusChart, serviceRevenueChart, servicesAvailedChart;

            // Initialize Dashboard Charts
            function initDashboardCharts() {
                // Appointment Status Chart
                const statusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
                appointmentStatusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_keys($appointmentStatuses)); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_values($appointmentStatuses)); ?>,
                            backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#EF4444', '#6B7280'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });

                // TOTAL DOWNPAYMENT BY SERVICES CHART - ADD THIS
                const serviceRevenueCtx = document.getElementById('serviceRevenueChart').getContext('2d');
                serviceRevenueChart = new Chart(serviceRevenueCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($serviceRevenueLabels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($serviceRevenueAmounts); ?>,
                            backgroundColor: colorPalette,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Revenue by Services Chart - Clean Pie Chart Version
                const revenueByServicesCtx = document.getElementById('revenueByServicesChart').getContext('2d');
                revenueByServicesChart = new Chart(revenueByServicesCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($serviceNames); ?>,
                        datasets: [{
                            data: <?php echo json_encode($serviceRevenues); ?>,
                            backgroundColor: [
                                '#4F46E5', '#22C55E', '#F59E0B', '#EF4444', '#06B6D4',
                                '#8B5CF6', '#84CC16', '#EC4899', '#F97316', '#0EA5E9'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    },
                                    // Remove numbers from legend labels
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map(function(label, i) {
                                                const value = data.datasets[0].data[i];
                                                return {
                                                    text: label, // Only show service name, no numbers
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: isNaN(data.datasets[0].data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        // Remove all scales and axes
                        scales: {},
                        // Remove animation if you want it to be completely clean
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        },
                        cutout: '0%'
                    }
                });

                // Services Availed Count Bar Chart
                const availedCtx = document.getElementById('servicesAvailedChart').getContext('2d');
                servicesAvailedChart = new Chart(availedCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($servicesAvailedLabels); ?>,
                        datasets: [{
                            label: 'Number of Appointments',
                            data: <?php echo json_encode($servicesAvailedCounts); ?>,
                            backgroundColor: '#4F46E5',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });

                // Appointments Chart
                const appointmentsCtx = document.getElementById('appointmentsBarChart').getContext('2d');
                appointmentsChart = new Chart(appointmentsCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($dates); ?>,
                        datasets: [{
                            label: 'Appointments',
                            data: <?php echo json_encode($counts); ?>,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgb(63, 137, 255)',
                            tension: 0.2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: {
                                display: false
                            }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true 
                            } 
                        }
                    }
                });

                // Revenue by Services Chart
                revenueByServicesCtx = document.getElementById('revenueByServicesChart').getContext('2d');
                revenueByServicesChart = new Chart(revenueByServicesCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($serviceNames); ?>,
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: <?php echo json_encode($serviceRevenues); ?>,
                            backgroundColor: [
                                '#4F46E5', '#22C55E', '#F59E0B', '#EF4444', '#06B6D4',
                                '#8B5CF6', '#84CC16', '#EC4899', '#F97316', '#0EA5E9'
                            ],
                            borderRadius: 6,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `₱${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Revenue (₱)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Services'
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
            }

            function updateChart() {
                const selectedMonth = document.getElementById('monthSelect').value;
                const data = monthlyData[selectedMonth];
                const serviceCtx = document.getElementById('servicePieChart').getContext('2d');
                const colorGuide = document.getElementById('colorGuide');

                colorGuide.innerHTML = '';
                data.labels.forEach((label, index) => {
                    colorGuide.innerHTML += `
                        <div class="color-item">
                            <div class="color-dot" style="background:${colorPalette[index % colorPalette.length]}"></div>
                            <span>${label}</span>
                        </div>`;
                });

                if (pieChart) pieChart.destroy();
                pieChart = new Chart(serviceCtx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.counts,
                            backgroundColor: data.labels.map((_, i) => colorPalette[i % colorPalette.length])
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: `Patients per Service - ${getMonthName(selectedMonth)} <?php echo $currentYear; ?>`
                            },
                            legend: { display: false }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                title: { display: true, text: 'Patients' } 
                            },
                            x: { 
                                title: { display: true, text: 'Services' } 
                            }
                        }
                    }
                });
            }

            function getMonthName(m) {
                const d = new Date(); d.setMonth(m - 1);
                return d.toLocaleString('default', { month: 'long' });
            }

            function filterReports() {
                const selected = document.getElementById('reportType').value;
                const reportSections = document.querySelectorAll('.report-section');
                
                if (selected === 'all') {
                    // Show all reports
                    reportSections.forEach(section => {
                        section.style.display = 'block';
                    });
                } else {
                    // Hide all reports first
                    reportSections.forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Show only the selected report
                    const selectedSection = document.getElementById(selected + 'Report');
                    if (selectedSection) {
                        selectedSection.style.display = 'block';
                        
                        // Smooth scroll to the selected report section
                        setTimeout(() => {
                            selectedSection.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'start'
                            });
                        }, 100);
                    }
                }
            }

            // Initialize charts when page loads
            document.addEventListener('DOMContentLoaded', function() {
                updateChart();
                initDashboardCharts();
                
                // All reports are visible by default
                filterReports(); // This will show all reports initially
            });
        </script>

        <style>
            .reports-container { 
                width:100%; 
                margin:auto; 
                padding:20px; 
                position: relative;
            }
            .report-header { 
                color: #374151; 
                padding: 0 0 15px 0; 
                margin-bottom: 25px;
                font-size: 24px;
                font-weight: 600;
                border-bottom: 1px solid #e5e7eb;
            }
            .report-selector { 
                margin-bottom: 25px; 
                display: flex; 
                align-items: center; 
                gap: 10px;
                position: sticky;
                top: 10px;
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 10;
            }
            .report-selector select { 
                padding:8px 12px; 
                border-radius:8px; 
                border:1px solid #d1d5db; 
                background:white;
                font-size: 16px;
                cursor: pointer;
            }
            .report-selector label {
                font-weight: 600;
                color: #374151;
            }
            
            .section-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            
            .section-header h3 {
                margin: 0;
                font-size: 22px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .section-header i {
                font-size: 24px;
            }
            
            .stats-grid {
                display:grid; 
                grid-template-columns: repeat(auto-fit,minmax(230px,1fr));
                gap:20px;
                margin-bottom: 30px;
            }

            .report-stat-card {
                background: #fff;
                border-radius: 8px;
                padding: 25px 20px;
                text-align: center;
                border: 1px solid #e5e7eb;
                transition: all 0.2s ease;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                min-height: 120px;
                border: 1px solid #d1d5db;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .stat-label { 
                color: #6B7280; 
                font-size: 14px; 
                margin-bottom: 8px;
                font-weight: 500;
            }
            .stat-value { 
                font-size: 25px; 
                font-weight: bold; 
                color: #111827; 
                line-height: 1;
            }
            
            .report-stat-card:hover { 
                border-color: #3B82F6;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
                transform: translateY(-2px);
            }
        
            .charts-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px;
                margin-bottom: 25px;
            }
            
            .chart-box { 
                background:#fff; 
                border-radius:8px; 
                padding:25px; 
                border: 1px solid #e5e7eb;
                height: 420px;
                display: flex;
                flex-direction: column;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                position: relative;
                overflow: hidden;
            }
            .chart-box:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .chart-box h3 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #374151;
                font-size: 18px;
                font-weight: 600;
                border-bottom: 1px solid #f3f4f6;
                padding-bottom: 10px;
            }
            .chart-box canvas {
                flex: 1;
                width: 100% !important;
                max-height: 320px;
                min-height: 280px;
            }
            
            .chart-controls { 
                margin-bottom:15px; 
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .chart-controls label {
                font-weight: 500;
                color: #374151;
            }
            .chart-controls select {
                padding: 6px 10px;
                border-radius: 6px;
                border: 1px solid #d1d5db;
            }
            .color-guide { 
                margin-top:15px; 
                display:flex; 
                flex-wrap:wrap; 
                gap:8px; 
                justify-content:center; 
            }
            .color-item { 
                display:flex; 
                align-items:center; 
                gap:6px; 
                font-size: 14px;
            }
            .color-dot { 
                width:14px; 
                height:14px; 
                border-radius:3px; 
                border:1px solid #ddd; 
            }
            
            .status-summary {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .status-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f3f4f6;
            }
            .status-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .status-dot {
                width: 12px;
                height: 12px;
                border-radius: 50%;
            }
            .status-name {
                font-weight: 500;
                color: #374151;
            }
            .status-numbers {
                display: flex;
                gap: 5px;
            }
            .status-count {
                font-weight: 600;
                color: #111827;
            }
            .status-percentage {
                color: #6B7280;
                font-size: 14px;
            }
            
            .report-section {
                margin-bottom: 40px;
                transition: all 0.3s ease;
            }
            
            /* Revenue by Services Specific Styles */
            .revenue-summary {
                margin-bottom: 25px;
            }

            .total-revenue-card {
                background: linear-gradient(135deg, #10B981 0%, #059669 100%);
                color: white;
                padding: 25px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 5px;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            }

            .revenue-icon {
                font-size: 40px;
                opacity: 0.9;
            }

            .revenue-info {
                flex: 1;
            }

            .revenue-label {
                font-size: 16px;
                opacity: 0.9;
                margin-bottom: 5px;
            }

            .revenue-amount {
                font-size: 32px;
                font-weight: bold;
            }

            .revenue-content {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 25px;
                margin-bottom: 20px;
            }

            .service-details {
                background: #fff;
                border-radius: 8px;
                padding: 20px;
                border: 1px solid #e5e7eb;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                height: fit-content;
            }

            .service-details h4 {
                margin: 0 0 20px 0;
                color: #374151;
                font-size: 18px;
                font-weight: 600;
                border-bottom: 1px solid #f3f4f6;
                padding-bottom: 10px;
            }

            .service-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .service-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                background: #f8f9fa;
                border-radius: 6px;
                border: 1px solid #e5e7eb;
                transition: all 0.2s ease;
            }

            .service-item:hover {
                background: #f1f5f9;
                border-color: #3B82F6;
            }

            .service-info {
                flex: 1;
            }

            .service-name {
                font-weight: 600;
                color: #374151;
                margin-bottom: 4px;
                font-size: 14px;
            }

            .service-stats {
                display: flex;
                gap: 15px;
                font-size: 12px;
            }

            .treatment-count {
                color: #6B7280;
            }

            .service-revenue {
                color: #059669;
                font-weight: 600;
            }

            .revenue-percentage {
                background: #3B82F6;
                color: white;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                min-width: 60px;
                text-align: center;
            }

            /* Responsive design */
            @media (max-width: 1024px) {
                .charts-row {
                    grid-template-columns: 1fr;
                }
                
                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
                
                .revenue-content {
                    grid-template-columns: 1fr;
                }
                
                .service-details {
                    order: -1;
                }
            }
            
            @media (max-width: 768px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }
                
                .report-selector {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .chart-controls {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .section-header h3 {
                    font-size: 18px;
                }
                
                .total-revenue-card {
                    flex-direction: column;
                    text-align: center;
                    gap: 15px;
                }
                
                .revenue-icon {
                    font-size: 32px;
                }
                
                .revenue-amount {
                    font-size: 28px;
                }
                
                .service-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
                
                .revenue-percentage {
                    align-self: flex-end;
                }
                
                .chart-box {
                    height: 380px;
                    padding: 20px;
                }
                
                .chart-box canvas {
                    max-height: 280px;
                }
            }
        </style>
    </div>
</div>


<script>
    //Complete Appointment Modal
    function openCompleteAppointmentModal(button) {
    const patientId = button.getAttribute('data-patientid');
    const appointmentId = button.getAttribute('data-appointmentid');
    
    // Set the values in the modal form
    document.getElementById('treatment_patient_id').value = patientId;
    document.getElementById('treatment_appointment_id').value = appointmentId;
    document.getElementById("patient_id").value = patientId; // show in the disabled field

    // Show the modal
    document.getElementById('complete-appointment-modal').style.display = 'block';
    }

    // Function to close the modal
    function closeCompleteAppointmentModal() {
        document.getElementById('complete-appointment-modal').style.display = 'none';
    }

    // Event listeners for modal close
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('complete-appointment-modal');
        
        // Check if modal exists before adding event listeners
        if (!modal) {
            console.error('Complete appointment modal not found');
            return;
        }
        
        const closeBtn = modal.querySelector('.complete-appointment-close');
        const cancelBtn = document.getElementById('cancelCompleteAppointment');
        const treatmentForm = document.getElementById('treatmentForm');
        
        // Add event listeners only if elements exist
        if (closeBtn) {
            closeBtn.addEventListener('click', closeCompleteAppointmentModal);
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeCompleteAppointmentModal);
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeCompleteAppointmentModal();
            }
        });
        
    });

    // Patient data for appointment modal
    const patientsMap = <?php echo json_encode($patientsMap); ?>;

    function updatePatientName() {
        const selectedID = document.getElementById("patient_id").value;
        document.getElementById("patient_name").value = patientsMap[selectedID] || '';
    }
    
    // See More Patient Modal
    function seeTreatmentHistory(patientId) {
        const modal = document.getElementById("treatmentHistoryModal");
        const tbody = document.getElementById("treatmentHistoryBody");

        // Clear table first
        tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>Loading...</td></tr>";

        // Fetch data via AJAX
        fetch("getTreatmentHistory.php?patient_id=" + patientId)
            .then(response => response.json())
            .then(data => {
                if (data.status === "success" && data.data.length > 0) {
                    tbody.innerHTML = "";
                    data.data.forEach(treatment => {
                        const row = `
                            <tr>
                                <td>${treatment.treatment}</td>
                                <td>${treatment.prescription_given}</td>
                                <td>${treatment.notes}</td>
                                <td>₱${parseFloat(treatment.treatment_cost).toFixed(2)}</td>
                                <td>${treatment.created_at}</td>
                            </tr>`;
                        tbody.insertAdjacentHTML("beforeend", row);
                    });
                } else {
                    tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No treatment history found.</td></tr>";
                }
            })
            .catch(error => {
                console.error("Error fetching treatment history:", error);
                tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;color:red;'>Error loading data</td></tr>";
            });

        modal.style.display = "block";
    }

    // Close modal
    function closeTreatmentModal() {
        document.getElementById("treatmentHistoryModal").style.display = "none";
    }

    // Close when clicking outside
    window.addEventListener("click", function(event) {
        const modal = document.getElementById("treatmentHistoryModal");
        if (event.target === modal) {
            closeTreatmentModal();
        }
    });

    // Appointment availability checking
    $(document).ready(function(){
        function checkAvailabilityAdminAdd() {
            var selectedDate = $("#appointment_date").val();
            if (selectedDate) {
                $.ajax({
                    url: 'getAppointmentsAdmin.php',
                    type: 'GET',
                    data: { appointment_date: selectedDate },
                    dataType: 'json',
                    success: function(bookedSlots) {
                        $("#appointment_time option").prop("disabled", false);
                        $.each(bookedSlots, function(index, slot) {
                            $("#appointment_time option[value='" + slot + "']").prop("disabled", true);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching appointment data:", error);
                    }
                });
            }
        }

        $("#appointment_date").on("change", function(){
            checkAvailabilityAdminAdd();
        });

        setInterval(function(){
            checkAvailabilityAdminAdd();
        }, 100);
    });

    $(document).ready(function(){
        function checkAvailabilityAdminResched() {
            var selectedDate = $("#new_date_resched").val();
            if (selectedDate) {
                $.ajax({
                    url: 'getAppointmentsAdminResched.php',
                    type: 'GET',
                    data: { new_date_resched: selectedDate },
                    dataType: 'json',
                    success: function(bookedSlots) {
                        $("#new_time_resched option").prop("disabled", false);
                        $.each(bookedSlots, function(index, slot) {
                            $("#new_time_resched option[value='" + slot + "']").prop("disabled", true);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching appointment data:", error);
                    }
                });
            }
        }

        $("#new_date_resched").on("change", function(){
            checkAvailabilityAdminResched();
        });

        setInterval(function(){
            checkAvailabilityAdminResched();
        }, 100);
    });

    // Filter appointments
    function filterAppointments() {
        let selectedDate = document.getElementById("filter-date").value;
        let selectedStatus = document.getElementById("filter-status").value.toLowerCase();
        let rows = document.querySelectorAll(".appointment-row");
        
        rows.forEach(row => {
            let rowDate = row.getAttribute("data-date");
            let rowStatus = row.getAttribute("data-status");
            
            let dateMatch = selectedDate === "" || rowDate === selectedDate;
            let statusMatch = selectedStatus === "" || rowStatus === selectedStatus;
            
            if (dateMatch && statusMatch) {
                row.style.display = "table-row";
            } else {
                row.style.display = "none";
            }
        });
    }

    // Sidebar functions
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("active");
    }

    function showSection(sectionId, clickedElement) {
        const sections = document.querySelectorAll('.main-content');
        sections.forEach(sec => sec.style.display = 'none');

        const sectionToShow = document.getElementById(sectionId);
        if (sectionToShow) sectionToShow.style.display = 'block';

        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        sidebarLinks.forEach(link => link.classList.remove('active'));

        clickedElement.classList.add('active');
    }

    function printAppointments() {
        window.print();
    }

    // Modal functions
    document.addEventListener('DOMContentLoaded', function () {
        // Add Appointment Modal
        const openAppointmentBtn = document.getElementById('openAddAppointmentBtn');
        const appointmentModal = document.getElementById('addAppointmentModal');
        
        if (openAppointmentBtn) {
            openAppointmentBtn.addEventListener('click', function () {
                appointmentModal.style.display = 'block';
            });
        }

        // Add Service Modal
        const openServiceBtn = document.getElementById('openAddServiceBtn');
        const serviceModal = document.getElementById('addServiceModal');
        
        if (openServiceBtn) {
            openServiceBtn.addEventListener('click', function () {
                serviceModal.style.display = 'block';
            });
        }

        // Add Dentist Modal
        const openDentistBtn = document.getElementById('openAddDentistBtn');
        const dentistModal = document.getElementById('addDentistModal');
        
        if (openDentistBtn) {
            openDentistBtn.addEventListener('click', function () {
                dentistModal.style.display = 'block';
            });
        }

        // Close modals when clicking outside
        window.addEventListener('click', function (event) {
            if (event.target === appointmentModal) {
                appointmentModal.style.display = 'none';
            }
            if (event.target === serviceModal) {
                serviceModal.style.display = 'none';
            }
            if (event.target === dentistModal) {
                dentistModal.style.display = 'none';
            }
        });
    });

    // Close modal functions
    function closeAddAppointmentModal() {
        document.getElementById('addAppointmentModal').style.display = 'none';
    }

    function closeAddModal() {
        document.getElementById('addServiceModal').style.display = 'none';
    }

    function closeDentistModal() {
        document.getElementById('addDentistModal').style.display = 'none';
    }

    function closeEditModal() {
        document.getElementById('editServiceModal').style.display = 'none';
    }

    function closeEditPatientModal() {
        document.getElementById('editPatientModal').style.display = 'none';
    }

    function closeEditDentistModal() {
        document.getElementById('editDentistModal').style.display = 'none';
    }

    // Reschedule functions
    function openReschedModalWithID(btn) {
        const appointmentID = btn.getAttribute('data-id');
        document.getElementById('modalAppointmentID').value = appointmentID;
        openReschedModal();
    }

    function openReschedModal() {
        document.getElementById("reschedModal").style.display = "block";
    }

    function closeReschedModal() {
        document.getElementById("reschedModal").style.display = "none";
    }

    // Image modal functions
    function viewImage(imageSrc) {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        modalImg.src = imageSrc;
        modal.style.display = "flex"; 
    }

    function closeModal() {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        modal.style.display = "none";
        modalImg.src = ""; 
    }

    // Edit functions (you'll need to implement the AJAX calls to fetch data)
    function editService(serviceId) {
        // Implement AJAX call to fetch service data and populate edit modal
        console.log('Edit service:', serviceId);
        // Show edit service modal and populate with data
    }

    function editPatient(patientId) {
        // Implement AJAX call to fetch patient data and populate edit modal
        console.log('Edit patient:', patientId);
        // Show edit patient modal and populate with data
    }

    function editDentist(dentistId) {
        // Implement AJAX call to fetch dentist data and populate edit modal
        console.log('Edit dentist:', dentistId);
        // Show edit dentist modal and populate with data
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (window.innerWidth <= 768 && sidebar.classList.contains('active') && 
            !sidebar.contains(event.target) && event.target !== menuToggle) {
            sidebar.classList.remove('active');
        }
    });

    // Dentist Schedules
    let currentWeekStart = new Date();
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Initialize schedule
    document.addEventListener('DOMContentLoaded', function() {
        updateWeekDisplay();
        loadBlockedSlots();
        generateMonthlyCalendar();
    });

    function changeScheduleView() {
        const viewType = document.getElementById('viewType').value;
        document.getElementById('weeklyView').style.display = viewType === 'weekly' ? 'block' : 'none';
        document.getElementById('monthlyView').style.display = viewType === 'monthly' ? 'block' : 'none';
    }

    function changeWeek(direction) {
        currentWeekStart.setDate(currentWeekStart.getDate() + (direction * 7));
        updateWeekDisplay();
        loadScheduleData();
    }

    function updateWeekDisplay() {
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 5); // Monday to Saturday
        
        const options = { month: 'short', day: 'numeric' };
        const startStr = currentWeekStart.toLocaleDateString('en-US', options);
        const endStr = weekEnd.toLocaleDateString('en-US', options);
        
        document.getElementById('currentWeekRange').textContent = `Week of ${startStr} - ${endStr}`;
    }

    function changeMonth(direction) {
        currentMonth += direction;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        generateMonthlyCalendar();
    }

    function generateMonthlyCalendar() {
        const calendar = document.getElementById('monthlyCalendar');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const startingDay = firstDay.getDay();
        
        let calendarHTML = '';
        
        // Day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            calendarHTML += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // Empty cells for days before the first day of month
        for (let i = 0; i < startingDay; i++) {
            const prevDate = new Date(currentYear, currentMonth, -i);
            calendarHTML += `<div class="calendar-day other-month">${prevDate.getDate()}</div>`;
        }
        
        // Days of the month
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const date = new Date(currentYear, currentMonth, day);
            const isToday = new Date().toDateString() === date.toDateString();
            const dayClass = isToday ? 'calendar-day today' : 'calendar-day';
            
            calendarHTML += `
                <div class="${dayClass}" data-date="${date.toISOString().split('T')[0]}">
                    <div class="calendar-day-header">${day}</div>
                    <div class="day-slots">
                        <div><span class="slot-indicator available"></span> 8 available</div>
                        <div><span class="slot-indicator blocked"></span> 2 blocked</div>
                        <div><span class="slot-indicator booked"></span> 3 booked</div>
                    </div>
                </div>
            `;
        }
        
        calendar.innerHTML = calendarHTML;
    }

    function toggleTimeSlot(element, date, slot) {
        const currentStatus = element.classList.contains('available') ? 'available' : 
                            element.classList.contains('blocked') ? 'blocked' : 'booked';
        
        if (currentStatus === 'booked') {
            alert('This slot is already booked and cannot be modified.');
            return;
        }
        
        const newStatus = currentStatus === 'available' ? 'blocked' : 'available';
        
        // Update UI immediately
        element.className = `slot-status ${newStatus}`;
        element.innerHTML = newStatus === 'available' ? 
            '<i class="fas fa-check-circle"></i><span>Available</span>' :
            '<i class="fas fa-times-circle"></i><span>Blocked</span>';
        
        // Send AJAX request to update database
        updateTimeSlotStatus(date, slot, newStatus);
    }

    function updateTimeSlotStatus(date, slot, status) {
        const dentistId = document.getElementById('dentistSelectSchedule').value;
        
        if (!dentistId) {
            alert('Please select a dentist first.');
            return;
        }
        
        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                dentist_id: dentistId,
                date: date,
                time_slot: slot,
                status: status,
                action: 'update_slot'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error updating slot: ' + data.message);
                // Revert UI change
                // You might want to implement a more sophisticated rollback
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating slot. Please try again.');
        });
    }

    function openAddBlockModal() {
        document.getElementById('blockTimeModal').style.display = 'block';
    }

    function closeBlockModal() {
        document.getElementById('blockTimeModal').style.display = 'none';
        document.getElementById('blockTimeForm').reset();
    }

    function openAddAvailabilityModal() {
        document.getElementById('addAvailabilityModal').style.display = 'block';
    }

    function closeAvailabilityModal() {
        document.getElementById('addAvailabilityModal').style.display = 'none';
        document.getElementById('addAvailabilityForm').reset();
    }

    function loadBlockedSlots() {
        fetch('get_blocked_slots.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('blockedSlotsBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="no-data">No blocked time slots found</td></tr>';
                return;
            }
            
            data.forEach(slot => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${slot.dentist_name}</td>
                    <td>${slot.date}</td>
                    <td>${slot.time_slot_display}</td>
                    <td>${slot.reason}</td>
                    <td>
                        <button class="action-btn btn-danger" onclick="unblockSlot('${slot.id}')" title="Unblock">
                            <i class="fas fa-unlock"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading blocked slots:', error);
        });
    }

    function unblockSlot(blockId) {
        if (!confirm('Are you sure you want to unblock this time slot?')) {
            return;
        }
        
        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                block_id: blockId,
                action: 'unblock_slot'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Time slot unblocked successfully');
                loadBlockedSlots();
                loadScheduleData();
            } else {
                alert('Error unblocking slot: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error unblocking slot. Please try again.');
        });
    }

    function loadScheduleData() {
        const dentistId = document.getElementById('dentistSelectSchedule').value;
        if (!dentistId) return;
        
        // This would load actual schedule data from the server
        // For now, we'll just update the display
        console.log('Loading schedule data for dentist:', dentistId);
    }

    // Form submissions
    document.getElementById('blockTimeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const timeSlots = formData.getAll('time_slots[]');
        
        if (timeSlots.length === 0) {
            alert('Please select at least one time slot to block.');
            return;
        }
        
        const data = {
            dentist_id: formData.get('dentist_id'),
            date: formData.get('block_date'),
            time_slots: timeSlots,
            reason: formData.get('reason'),
            custom_reason: formData.get('custom_reason'),
            action: 'block_slots'
        };
        
        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Time slots blocked successfully');
                closeBlockModal();
                loadBlockedSlots();
                loadScheduleData();
            } else {
                alert('Error blocking slots: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error blocking slots. Please try again.');
        });
    });

    document.getElementById('addAvailabilityForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const timeSlots = formData.getAll('time_slots[]');
        
        if (timeSlots.length === 0) {
            alert('Please select at least one time slot.');
            return;
        }
        
        const data = {
            dentist_id: formData.get('dentist_id'),
            date: formData.get('avail_date'),
            time_slots: timeSlots,
            notes: formData.get('notes'),
            action: 'add_availability'
        };
        
        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Special availability added successfully');
                closeAvailabilityModal();
                loadScheduleData();
            } else {
                alert('Error adding availability: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding availability. Please try again.');
        });
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('blockTimeModal')) {
            closeBlockModal();
        }
        if (event.target === document.getElementById('addAvailabilityModal')) {
            closeAvailabilityModal();
        }
    });
</script>
</body>
</html>