<?php
session_start();
include_once("../database/config.php");

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
    <link rel="stylesheet" href="../assets/css/adminstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Notification System Styles */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
        }

        .notification {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 320px;
            animation: slideInRight 0.4s ease-out;
            position: relative;
            overflow: hidden;
        }

        .notification.success {
            border-left: 4px solid #10B981;
        }

        .notification.warning {
            border-left: 4px solid #F59E0B;
        }

        .notification.error {
            border-left: 4px solid #EF4444;
        }

        .notification.info {
            border-left: 4px solid #3B82F6;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOutRight 0.3s ease-out forwards;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .notification.success .notification-icon {
            background: #D1FAE5;
            color: #10B981;
        }

        .notification.warning .notification-icon {
            background: #FEF3C7;
            color: #F59E0B;
        }

        .notification.error .notification-icon {
            background: #FEE2E2;
            color: #EF4444;
        }

        .notification.info .notification-icon {
            background: #DBEAFE;
            color: #3B82F6;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            font-size: 16px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        .notification-message {
            font-size: 14px;
            color: #6B7280;
            margin: 0;
        }

        .notification-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 20px;
            color: #9CA3AF;
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .notification-close:hover {
            background: #F3F4F6;
            color: #374151;
        }

        /* Check Animation */
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }

        .check-animation {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.6s ease-out forwards;
        }

        /* Calendar Animation */
        @keyframes calendarPop {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .calendar-animation {
            animation: calendarPop 0.5s ease-out;
        }

        /* Warning Pulse Animation */
        @keyframes warningPulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .warning-animation {
            animation: warningPulse 0.8s ease-out 2;
        }

        /* Success Scale Animation */
        @keyframes successScale {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-scale-animation {
            animation: successScale 0.5s ease-out;
        }

        /* Progress Bar */
        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #E5E7EB;
            width: 100%;
        }

        .notification-progress-bar {
            height: 100%;
            background: currentColor;
            animation: progressBar 5s linear forwards;
        }

        @keyframes progressBar {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }
        /* Follow-Up Button Styles */
        .btn-followup {
            background: #8B5CF6;
            color: white;
        }

        .btn-followup:hover {
            background: #7C3AED;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pagination-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pagination-btn:hover:not(:disabled) {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-numbers {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .pagination-number {
            min-width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination-number:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .pagination-number.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination-number.ellipsis {
            cursor: default;
            border: none;
            background: transparent;
        }

        .pagination-number.ellipsis:hover {
            background: transparent;
        }
    </style>
</head>
<body>

<!-- Notification Container -->
<div class="notification-container" id="notificationContainer"></div>

<div class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/landerologo.png">
    </div>
    <nav class="sidebar-nav">
        <a href="#" class="active" onclick="showSection('dashboard', this)"><i class="fa fa-tachometer"></i> Dashboard</a>
        <a href="#appointment" onclick="showSection('appointment', this)"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="#schedules" onclick="showSection('schedules', this)"><i class="fas fa-calendar-days"></i> Time Slots</a>
        <a href="#services" onclick="showSection('services', this)"><i class="fa-solid fa-teeth"></i> Services</a>
        <a href="#patients" onclick="showSection('patients', this)"><i class="fa-solid fa-hospital-user"></i> Patients</a>
        <a href="#treatment" onclick="showSection('treatment', this)"><i class="fa-solid fa-notes-medical"></i> History</a>
        <a href="#dentists" onclick="showSection('dentists', this)"><i class="fa-solid fa-user-doctor"></i> Dentists & Staff</a>
        <a href="#payments" onclick="showSection('payment', this)"><i class="fa-solid fa-money-bill"></i> Transactions</a> 
        <a href="#reports" onclick="showSection('reports', this)"><i class="fa-solid fa-square-poll-vertical"></i> Reports</a> 
        <a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <div class="sidebar-divider"></div>
        <button class="sidebar-btn-clinic-control" onclick="showControlsPopup()" title="Controls">
            <i class="fas fa-cog"></i> <span>Controls</span>
        </button>
    </nav>
</div>

<!-- Controls Popup Modal -->
<div id="controlsPopupModal" class="modal" style="display:none; z-index: 10001;">
    <div class="modal-content" style="max-width: 400px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-sliders-h"></i> Select Control
        </h3>
        <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
            <button class="control-option-btn" onclick="navigateToClinicControl()">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="control-icon" style="background: #f59e0b20; color: #f59e0b;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-weight: 600; font-size: 16px;">Clinic Control</div>
                        <div style="font-size: 13px; color: #6b7280; margin-top: 3px;">Manage closures & holidays</div>
                    </div>
                </div>
            </button>
            
            <button class="control-option-btn" onclick="navigateToUserControl()">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="control-icon" style="background: #3b82f620; color: #3b82f6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-weight: 600; font-size: 16px;">User Control</div>
                        <div style="font-size: 13px; color: #6b7280; margin-top: 3px;">Manage users & accounts</div>
                    </div>
                </div>
            </button>
        </div>
    </div>
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
                <label for="filter-date-category"><i class="fas fa-calendar-day"></i> Date Category:</label>
                <select id="filter-date-category" onchange="handleDateCategoryChange()">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Date</option>
                </select>
                <input type="date" id="filter-date" onchange="filterAppointments()" style="display:none; margin-left:10px;">
            </div>
            
            <div class="filter-group">
                <label for="filter-status"><i class="fas fa-filter"></i> Status Category:</label>        
                <select id="filter-status" onchange="filterAppointments()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="reschedule">Reschedule</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="no-show">No-Show</option>
                </select> 
            </div>

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
                                    <?php if (strtolower($row['status']) === 'pending'): ?>
                                    <button type="button" class="action-btn btn-primary-confirmed" title="Confirm"
                                        data-appointment-id="<?php echo $row['appointment_id']; ?>"
                                        onclick="confirmAppointment(this)">
                                        <i class="fas fa-check"></i>
                                    </button>

                                    <a href="#" 
                                        class="action-btn btn-accent" 
                                        id="reschedBtn<?= $row['appointment_id'] ?>" 
                                        data-id="<?= $row['appointment_id'] ?>"
                                        onclick="return openReschedModalWithID(this, event);"
                                        title="Reschedule">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>

                                    <button type="button" class="action-btn btn-danger" title="Cancel"
                                        data-appointment-id="<?php echo $row['appointment_id']; ?>"
                                        onclick="cancelAppointmentByAdmin(this)">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    <button type="button" class="action-btn btn-danger" title="No-Show"
                                        data-appointment-id="<?php echo $row['appointment_id']; ?>"
                                        onclick="markNoShow(this)">
                                        <i class="fa-regular fa-eye-slash"></i>
                                    </button>
                                    <?php else: ?>
                                    <a href="#" 
                                        class="action-btn btn-accent <?php echo (in_array(strtolower($row['status']), ['completed', 'cancelled', 'no-show']) ? 'disabled-action' : ''); ?>" 
                                        id="reschedBtn<?= $row['appointment_id'] ?>" 
                                        data-id="<?= $row['appointment_id'] ?>"
                                        onclick="<?php echo (in_array(strtolower($row['status']), ['completed', 'cancelled', 'no-show']) ? 'event.preventDefault(); return false;' : 'return openReschedModalWithID(this, event);'); ?>"
                                        title="<?php echo (in_array(strtolower($row['status']), ['completed', 'cancelled', 'no-show']) ? 'Cannot reschedule this appointment' : 'Reschedule'); ?>">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>

                                    <button type="button" class="action-btn btn-completed" title="Mark as Completed"
                                        data-patientid="<?php echo htmlspecialchars($row['patient_id']); ?>"
                                        data-appointmentid="<?php echo htmlspecialchars($row['appointment_id']); ?>"
                                        onclick="openCompleteAppointmentModal(this)">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </button>

                                    <?php if (strtolower($row['status']) === 'completed'): ?>
                                    <button type="button" class="action-btn btn-followup" title="Follow-Up"
                                        data-appointment-id="<?php echo htmlspecialchars($row['appointment_id']); ?>"
                                        data-patient-id="<?php echo htmlspecialchars($row['patient_id']); ?>"
                                        data-patient-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>"
                                        onclick="openFollowUpModal(this)">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                    <?php endif; ?>

                                    <button type="button" class="action-btn btn-danger" title="No-Show"
                                        data-appointment-id="<?php echo $row['appointment_id']; ?>"
                                        onclick="markNoShow(this)">
                                        <i class="fa-regular fa-eye-slash"></i>
                                    </button>
                                    <?php endif; ?>
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
        
        <!-- Pagination Controls -->
        <div class="pagination-container" id="pagination-container">
            <div class="pagination-info" id="pagination-info"></div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="prev-page-btn" onclick="changePage(-1)" disabled>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <div class="pagination-numbers" id="pagination-numbers"></div>
                <button class="pagination-btn" id="next-page-btn" onclick="changePage(1)" disabled>
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
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
            <form id="treatmentForm" onsubmit="handleTreatmentSubmit(event)">
                <input type="hidden" id="treatment_patient_id" name="patient_id">
                <input type="hidden" id="treatment_appointment_id" name="appointment_id">

                <div class="complete-appointment-form-group">
                    <label for="patient-id">Patient ID:</label>
                    <input type="text" id="patient_id" value="" readonly>
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

        <form action="../controllers/addAppointment.php" method="POST">
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
        <form id="rescheduleForm" onsubmit="handleRescheduleSubmit(event)">
            <input type="hidden" id="modalAppointmentID" name="appointment_id">
            
            <label for="new_date">Select New Date:</label>
            <input type="date" id="new_date_resched" name="new_date_resched" required min="<?= date('Y-m-d') ?>" onchange="loadBookedSlots()">

            <label for="new_time">Select New Time:</label>
            <select id="new_time_resched" name="new_time_slot" required>
                <option value="">Select Time Slot</option>
                <option value="firstBatch" data-slot="8AM-9AM">Morning (8AM-9AM)</option>
                <option value="secondBatch" data-slot="9AM-10AM">Morning (9AM-10AM)</option>
                <option value="thirdBatch" data-slot="10AM-11AM">Morning (10AM-11AM)</option>
                <option value="fourthBatch" data-slot="11AM-12PM">Afternoon (11AM-12PM)</option>
                <option value="fifthBatch" data-slot="1PM-2PM">Afternoon (1PM-2PM)</option>
                <option value="sixthBatch" data-slot="2PM-3PM">Afternoon (2PM-3PM)</option>
                <option value="sevenBatch" data-slot="3PM-4PM">Afternoon (3PM-4PM)</option>
                <option value="eightBatch" data-slot="4PM-5PM">Afternoon (4PM-5PM)</option>
                <option value="nineBatch" data-slot="5PM-6PM">Afternoon (5PM-6PM)</option>
                <option value="tenBatch" data-slot="6PM-7PM">Evening (6PM-7PM)</option>
                <option value="lastBatch" data-slot="7PM-8PM">Evening (7PM-8PM)</option>
            </select>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">CONFIRM SCHEDULE</button>
                <button type="button" onclick="closeReschedModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Follow-Up Modal -->
<div id="followUpModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3><i class="fa-solid fa-arrow-right"></i> Schedule Follow-Up Appointment</h3>
        <form id="followUpForm" action="../controllers/saveFollowUp.php" method="POST">
            <input type="hidden" id="followup_patient_id" name="patient_id">
            <input type="hidden" id="followup_appointment_id" name="original_appointment_id">
            
            <div class="form-group">
                <label for="followup_patient_name">Patient Name:</label>
                <input type="text" id="followup_patient_name" name="patient_name" readonly required>
            </div>

            <div class="form-group">
                <label for="followup_date">Follow-Up Date:</label>
                <input type="date" id="followup_date" name="appointment_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>

            <div class="form-group">
                <label for="followup_time">Follow-Up Time:</label>
                <select id="followup_time" name="time_slot" required>
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

            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-success">Save Follow-Up</button>
                <button type="button" onclick="closeFollowUpModal()" class="modal-close-btn">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Dentist Schedule -->
<div id="schedules" class="main-content" style="display:none">
    <div class="container">
            <h2><i class="fa-solid fa-calendar-days"></i> TIME SLOT SCHEDULING CONTROL</h2>
            
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
                
            </div>

            <!-- Weekly Schedule View -->
            <div id="weeklyView" class="schedule-view">
                <div class="week-navigation">
                    <button id="prevWeekBtn" class="btn btn-accent" onclick="changeWeek(-1)">
                        <i class="fas fa-chevron-left"></i> Previous Week
                    </button>
                    <h3 id="currentWeekRange">Week of ...</h3>
                    <button id="nextWeekBtn" class="btn btn-accent" onclick="changeWeek(1)">
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
</div>

<!-- Block Entire Day Modal -->
<div id="blockDayModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeBlockDayModal()">&times;</span>
        <h3><i class="fas fa-calendar-times"></i> Block Entire Day</h3>
        <form id="blockDayForm" onsubmit="handleBlockDaySubmit(event)">
            <div style="margin-bottom: 15px;">
                <label for="blockDayDate"><strong>Select Date:</strong></label>
                <input type="date" id="blockDayDate" name="closure_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label><strong>Closure Type:</strong></label>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="radio" name="closure_type" value="full_day" checked>
                        <span><i class="fas fa-ban" style="color: #dc3545;"></i> Full Day Closure (All appointments blocked)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="radio" name="closure_type" value="no_new_appointments">
                        <span><i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> No New Appointments (Existing appointments remain)</span>
                    </label>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="blockDayReason"><strong>Reason:</strong></label>
                <select id="blockDayReason" name="reason" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; margin-top: 5px;">
                    <option value="">Select Reason</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Staff Training">Staff Training</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Weather">Weather Conditions</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div id="blockDayCustomReasonContainer" style="margin-bottom: 15px; display: none;">
                <label for="blockDayCustomReason"><strong>Custom Reason (if Other):</strong></label>
                <textarea id="blockDayCustomReason" name="custom_reason" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-top: 5px;" placeholder="Enter custom reason..."></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="notifyPatients" name="notify_patients" checked>
                    <span>Notify patients with appointments on this date</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeBlockDayModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Block Day</button>
            </div>
        </form>
    </div>
</div>

<!-- Holiday Management Modal -->
<div id="holidayModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close" onclick="closeHolidayModal()">&times;</span>
        <h3><i class="fas fa-calendar-star"></i> Manage Holidays</h3>
        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <button class="btn btn-primary" onclick="showAddHolidayForm()">
                <i class="fas fa-plus"></i> Add Holiday
            </button>
        </div>
        
        <!-- Add Holiday Form -->
        <div id="addHolidayForm" style="display:none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4>Add New Holiday</h4>
            <form id="holidayForm" onsubmit="handleHolidaySubmit(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="holidayName"><strong>Holiday Name:</strong></label>
                        <input type="text" id="holidayName" name="holiday_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div>
                        <label for="holidayDate"><strong>Date:</strong></label>
                        <input type="date" id="holidayDate" name="holiday_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label><strong>Recurrence:</strong></label>
                    <div style="display: flex; gap: 15px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="recurrence" value="once" checked>
                            <span>One Time</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="recurrence" value="yearly">
                            <span>Yearly (Recurring)</span>
                        </label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="hideAddHolidayForm()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Holiday</button>
                </div>
            </form>
        </div>
        
        <!-- Holidays List -->
        <div id="holidaysList">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <th style="padding: 12px; text-align: left;">Holiday Name</th>
                        <th style="padding: 12px; text-align: left;">Date</th>
                        <th style="padding: 12px; text-align: left;">Recurrence</th>
                        <th style="padding: 12px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="holidaysTableBody">
                    <!-- Holidays will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Emergency Closure Modal -->
<div id="emergencyClosureModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeEmergencyClosureModal()">&times;</span>
        <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Emergency Closure</h3>
        <form id="emergencyClosureForm" onsubmit="handleEmergencyClosureSubmit(event)">
            <div style="margin-bottom: 15px;">
                <label><strong>Closure Duration:</strong></label>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="radio" name="closure_duration" value="single_day" checked>
                        <span>Single Day</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="radio" name="closure_duration" value="date_range">
                        <span>Date Range</span>
                    </label>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="emergencyStartDate"><strong>Start Date:</strong></label>
                <input type="date" id="emergencyStartDate" name="start_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 15px;" id="emergencyEndDateContainer" style="display:none;">
                <label for="emergencyEndDate"><strong>End Date:</strong></label>
                <input type="date" id="emergencyEndDate" name="end_date" min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="emergencyReason"><strong>Emergency Reason:</strong></label>
                <textarea id="emergencyReason" name="reason" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-top: 5px;" placeholder="Describe the emergency situation..."></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="emergencyNotifyPatients" name="notify_patients" checked>
                    <span>Notify all affected patients immediately</span>
                </label>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                <strong style="color: #856404;">⚠️ Warning:</strong>
                <p style="color: #856404; margin: 5px 0 0 0;">This will automatically cancel all appointments during the closure period. Affected patients will be notified.</p>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeEmergencyClosureModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Emergency Closure</button>
            </div>
        </form>
    </div>
</div>

</div>

<!-- Services -->
<div id="services" class="main-content" style="display:none;">
    <div class="container">
        <h2><i class="fas fa-procedures"></i> SERVICES</h2>

        <?php
            // Get unique service categories for filter
            $categoriesQuery = "SELECT DISTINCT service_category FROM services WHERE service_category IS NOT NULL AND service_category != '' ORDER BY service_category";
            $categoriesResult = mysqli_query($con, $categoriesQuery);
            $serviceCategories = [];
            while ($categoryRow = mysqli_fetch_assoc($categoriesResult)) {
                $serviceCategories[] = $categoryRow['service_category'];
            }
            
            // Get all services
            $servicesSql = "SELECT service_id, service_category, sub_service, description, price FROM services ORDER BY service_category, service_id";
            $servicesResult = mysqli_query($con, $servicesSql);
        ?>

        <div class="filter-container">
            <div class="filter-group">
                <label for="filter-service-category"><i class="fas fa-filter"></i> Category:</label>
                <select id="filter-service-category" onchange="filterServices()">
                    <option value="">All Categories</option>
                    <?php foreach ($serviceCategories as $category): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($category)); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button class="btn btn-primary" id="openAddServiceBtn">ADD NEW SERVICE</button>
            
            <button class="btn btn-accent" onclick="printServices()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

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
                        <tr class="service-row" data-category="<?php echo htmlspecialchars(strtolower($row['service_category'])); ?>">
                            <td><?php echo htmlspecialchars($row['service_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['service_category']); ?></td>
                            <td><?php echo htmlspecialchars($row['sub_service']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>₱<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn btn-primary" title="Edit" onclick="editServicebtn('<?php echo $row['service_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="../controllers/deleteService.php" method="POST" style="display:inline;">
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
        
        <!-- Pagination Controls for Services -->
        <div class="pagination-container" id="services-pagination-container">
            <div class="pagination-info" id="services-pagination-info"></div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="services-prev-page-btn" onclick="changeServicesPage(-1)" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="pagination-numbers" id="services-pagination-numbers"></div>
                <button class="pagination-btn" id="services-next-page-btn" onclick="changeServicesPage(1)" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>ADD SERVICE</h3>
        <form action="../controllers/addServices.php" method="POST">
            <label for="service_category">Service Category:</label>
            <select name="service_category" required>
                <option value="" disabled selected>Select a category</option>
                <option value="General Dentistry">General Dentistry</option>
                <option value="Onthodontics">Onthodontics</option>
                <option value="Oral Surgery">Oral Surgery</option>
                <option value="Endodontics">Endodontics</option>
                <option value="Prosthodontics">Prosthodontics</option>
            </select>

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
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>EDIT SERVICE</h3>
        <form id="editServiceForm" method="POST" action="../controllers/updateService.php">
            <input type="hidden" name="service_id" id="editServiceId">

            <label for="editServiceCategory">Service Category:</label>
            <select name="service_category" id="editServiceCategory" required>
                <option value="General Dentistry">General Dentistry</option>
                <option value="Orthodontics">Onthodontics</option>
                <option value="Oral Surgery">Oral Surgery</option>
                <option value="Endodontics">Endodontics</option>
                <option value="Prosthodontics Treatments (Pustiso)">Prosthodontics Treatments (Pustiso)</option>
            </select>

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
                          FROM patient_information
                          ORDER BY patient_id ASC";
            $patientResult = mysqli_query($con, $patientSql);
        ?>

        <div class="filter-container">
            <div class="filter-group">
                <label for="filter-patient-gender"><i class="fas fa-venus-mars"></i> Gender:</label>
                <select id="filter-patient-gender" onchange="filterPatients()">
                    <option value="">All Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-patient-age"><i class="fas fa-calendar-alt"></i> Age Category:</label>
                <select id="filter-patient-age" onchange="filterPatients()">
                    <option value="">All Ages</option>
                    <option value="child">Child (0-12)</option>
                    <option value="teen">Teen (13-19)</option>
                    <option value="adult">Adult (20-59)</option>
                    <option value="senior">Senior (60+)</option>
                </select>
            </div>
            
            <div class="filter-group search-group">
                <label for="filter-patient-search"><i class="fas fa-search"></i> Search:</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="filter-patient-search" class="search-input" 
                           placeholder="Search by name, ID, email..." onkeyup="filterPatients()">
                    <button type="button" class="search-clear-btn" id="clear-search-btn" onclick="clearPatientSearch()" style="display:none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <button class="btn btn-accent" onclick="printPatients()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

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
                            
                            // Determine age category
                            $ageCategory = '';
                            if ($age <= 12) {
                                $ageCategory = 'child';
                            } else if ($age >= 13 && $age <= 19) {
                                $ageCategory = 'teen';
                            } else if ($age >= 20 && $age <= 59) {
                                $ageCategory = 'adult';
                            } else {
                                $ageCategory = 'senior';
                            }
                            
                            // Full name for search
                            $fullName = strtolower($row['first_name'] . ' ' . $row['last_name']);
                            $searchText = strtolower($row['patient_id'] . ' ' . $fullName . ' ' . $row['email']);
                    ?>
                        <tr class="patient-row" 
                            data-gender="<?php echo htmlspecialchars(strtolower($row['gender'])); ?>"
                            data-age-category="<?php echo htmlspecialchars($ageCategory); ?>"
                            data-search="<?php echo htmlspecialchars($searchText); ?>"
                            data-age="<?php echo $age; ?>">
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

                                    <button class="action-btn btn-danger" title="Archive" onclick="archivePatient(<?php echo $row['patient_id']; ?>)">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>

                                    <button class="action-btn btn-gray" title="See More" onclick="seeMoreDetails('<?php echo $row['patient_id']; ?>')">
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
        
        <!-- Pagination Controls for Patients -->
        <div class="pagination-container" id="patients-pagination-container">
            <div class="pagination-info" id="patients-pagination-info"></div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="patients-prev-page-btn" onclick="changePatientsPage(-1)" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="pagination-numbers" id="patients-pagination-numbers"></div>
                <button class="pagination-btn" id="patients-next-page-btn" onclick="changePatientsPage(1)" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>EDIT PATIENT</h3>
        <form id="editPatientForm" onsubmit="handleEditPatientSubmit(event)">
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

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;">
                    <label for="editEmail">Email:</label>
                    <input type="email" name="email" id="editEmail" required>
                </div>

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
                                    
                                    <form action="../controllers/deleteStaff.php" method="POST" style="display:inline;">
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
    <div class="modal-content" style="max-width: 800px; width: 90%; margin: 2% auto; padding: 30px;">
        <h3 style="font-size: 24px; margin-bottom: 25px; text-align: center; ">ADD DENTIST/STAFF</h3>
        <form action="../controllers/addStaff.php" method="POST">
            <!-- User ID Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="userid" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">User ID</label>
                    <select name="userid" id="userid" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                        <option value="">Select User ID</option>
                        <!-- Admin users will be populated here by JavaScript -->
                    </select>
                </div>
            </div>

            <!-- Name Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="addFirstName" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">First Name:</label>
                    <input type="text" name="first_name" id="addFirstName" required readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; background-color: #f8f9fa;">
                </div>
                <div style="flex: 1;">
                    <label for="addLastName" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Last Name:</label>
                    <input type="text" name="last_name" id="addLastName" required readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; background-color: #f8f9fa;">
                </div>
            </div>

            <!-- Specialization & Email Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="addSpecialization" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Specialization:</label>
                    <input type="text" name="specialization" id="addSpecialization" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
                <div style="flex: 1;">
                    <label for="addEmail" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Email:</label>
                    <input type="email" name="email" id="addEmail" required readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; background-color: #f8f9fa;">
                </div>
            </div>

            <!-- Phone & Status Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div style="flex: 1;">
                    <label for="addPhone" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Phone:</label>
                    <input type="text" name="phone" id="addPhone" required readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; background-color: #f8f9fa;">
                </div>
                <div style="flex: 1;">
                    <label for="addStatus" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Status:</label>
                    <select name="status" id="addStatus" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eaeaea;">
                <button type="submit" class="btn btn-success" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">Add Staff</button>
                <button type="button" onclick="closeDentistModal()" class="modal-close-btn" style="padding: 12px 30px; font-size: 16px; font-weight: 600; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Dentist Modal -->
<div id="editDentistModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 800px; width: 90%; margin: 2% auto; padding: 30px;">
        <h3 style="font-size: 24px; margin-bottom: 25px; text-align: center; ">EDIT DENTIST/STAFF</h3>
        <form id="editDentistForm" method="POST" action="../controllers/updateStaff.php">
            <input type="hidden" name="team_id" id="editDentistId">
            <input type="hidden" name="user_id" id="editDentistUserId">

            <!-- Name Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="editDentistFirstName" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">First Name:</label>
                    <input type="text" name="first_name" id="editDentistFirstName" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
                <div style="flex: 1;">
                    <label for="editDentistLastName" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Last Name:</label>
                    <input type="text" name="last_name" id="editDentistLastName" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
            </div>

            <!-- Specialization & Status Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="editDentistSpecialization" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Specialization:</label>
                    <input type="text" name="specialization" id="editDentistSpecialization" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
                <div style="flex: 1;">
                    <label for="editDentistStatus" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Status:</label>
                    <select name="status" id="editDentistStatus" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Email & Phone Section -->
            <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div style="flex: 1;">
                    <label for="editDentistEmail" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Email:</label>
                    <input type="email" name="email" id="editDentistEmail" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
                <div style="flex: 1;">
                    <label for="editDentistPhone" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Phone:</label>
                    <input type="text" name="phone" id="editDentistPhone" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eaeaea;">
                <button type="submit" class="btn btn-success" style="padding: 12px 30px; font-size: 16px; font-weight: 600; color: white; border: none; border-radius: 6px; cursor: pointer;">Update Details</button>
                <button type="button" onclick="closeEditDentistModal()" class="modal-close-btn" style="padding: 12px 30px; font-size: 16px; font-weight: 600; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
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
                                    <button type="button" class="action-btn btn-primary" title="Export/Print" onclick="printTreatmentHistory('<?php echo htmlspecialchars($row['patient_id']); ?>')">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                    <form action="../controllers/archivePatient.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="patient_id" value="<?php echo $row['patient_id']; ?>">
                                        <button type="submit" class="action-btn btn-danger" title="Archive">
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
                                  a.patient_id, a.appointment_date
                           FROM payment p
                           LEFT JOIN appointments a ON p.appointment_id = a.appointment_id
                           ORDER BY a.appointment_date DESC, p.payment_id DESC";
            $paymentResult = mysqli_query($con, $paymentSql);
            
            // Get unique payment methods for filter
            $methodsQuery = "SELECT DISTINCT method FROM payment WHERE method IS NOT NULL AND method != '' ORDER BY method";
            $methodsResult = mysqli_query($con, $methodsQuery);
            $paymentMethods = [];
            while ($methodRow = mysqli_fetch_assoc($methodsResult)) {
                $paymentMethods[] = $methodRow['method'];
            }
            
            // Get unique payment statuses for filter
            $statusQuery = "SELECT DISTINCT status FROM payment WHERE status IS NOT NULL AND status != '' ORDER BY 
                            CASE status 
                                WHEN 'pending' THEN 1 
                                WHEN 'paid' THEN 2 
                                WHEN 'failed' THEN 3 
                                WHEN 'refunded' THEN 4 
                                ELSE 5 
                            END";
            $statusResult = mysqli_query($con, $statusQuery);
            $paymentStatuses = [];
            while ($statusRow = mysqli_fetch_assoc($statusResult)) {
                $paymentStatuses[] = $statusRow['status'];
            }
        ?>

        <div class="filter-container">
            <div class="filter-group">
                <label for="filter-payment-date-category"><i class="fas fa-calendar-day"></i> Date Category:</label>
                <select id="filter-payment-date-category" onchange="handlePaymentDateCategoryChange()">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Date</option>
                </select>
                <input type="date" id="filter-payment-date" onchange="filterPayments()" style="display:none; margin-left:10px;">
            </div>
            
            <div class="filter-group">
                <label for="filter-payment-status"><i class="fas fa-filter"></i> Status Category:</label>
                <select id="filter-payment-status" onchange="filterPayments()">
                    <option value="">All Status</option>
                    <?php foreach ($paymentStatuses as $status): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($status)); ?>">
                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-payment-method"><i class="fas fa-credit-card"></i> Payment Method:</label>
                <select id="filter-payment-method" onchange="filterPayments()">
                    <option value="">All Methods</option>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($method)); ?>"><?php echo htmlspecialchars($method); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button class="btn btn-accent" onclick="printPayments()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

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
                            $appointmentDate = $row['appointment_date'] ?? '';
                            $paymentStatus = strtolower($row['status'] ?? '');
                            $paymentMethod = strtolower($row['method'] ?? '');
                    ?>
                        <tr class="payment-row" 
                            data-date="<?php echo htmlspecialchars($appointmentDate); ?>" 
                            data-status="<?php echo htmlspecialchars($paymentStatus); ?>"
                            data-method="<?php echo htmlspecialchars($paymentMethod); ?>">
                            <td><?php echo htmlspecialchars($row['payment_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['method']); ?></td>
                            <td>
                                <?php 
                                $paymentMethod = strtolower(trim($row['method'] ?? ''));
                                if ($paymentMethod === 'cash') {
                                    echo 'N/A';
                                } else {
                                    echo htmlspecialchars($row['account_name'] ?? '');
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($paymentMethod === 'cash') {
                                    echo 'N/A';
                                } else {
                                    echo htmlspecialchars($row['account_number'] ?? '');
                                }
                                ?>
                            </td>
                            <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <?php 
                                if ($paymentMethod === 'cash') {
                                    echo 'N/A';
                                } else {
                                    echo htmlspecialchars($row['reference_no'] ?? '');
                                }
                                ?>
                            </td>
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
                            <td>
                                <span class="status status-<?php echo htmlspecialchars(strtolower($row['status'] ?? 'pending')); ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Pending')); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php 
                                    $currentStatus = strtolower($row['status'] ?? '');
                                    // Only show confirm button if status is not 'paid' or 'refunded'
                                    if ($currentStatus !== 'paid' && $currentStatus !== 'refunded'): 
                                    ?>
                                    <button type="button" class="action-btn btn-primary-confirmedPayment" title="Confirm"
                                        data-payment-id="<?php echo $row['payment_id']; ?>"
                                        data-payment-amount="<?php echo htmlspecialchars($row['amount']); ?>"
                                        onclick="confirmPayment(this)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Only show failed button if status is not 'failed' or 'refunded'
                                    if ($currentStatus !== 'failed' && $currentStatus !== 'refunded'): 
                                    ?>
                                    <button type="button" class="action-btn btn-danger" title="Mark as failed"
                                        data-payment-id="<?php echo $row['payment_id']; ?>"
                                        onclick="markPaymentFailed(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
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
        
        <!-- Pagination Controls -->
        <div id="payment-pagination" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px;">
            <button id="payment-prev-btn" class="btn btn-secondary" onclick="changePaymentPage(-1)" disabled>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <span id="payment-page-info" style="padding: 8px 15px; font-weight: 500;">
                Page <span id="payment-current-page">1</span> of <span id="payment-total-pages">1</span>
            </span>
            <button id="payment-next-btn" class="btn btn-secondary" onclick="changePaymentPage(1)" disabled>
                Next <i class="fas fa-chevron-right"></i>
            </button>
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
            ?>

            <?php if (!empty($serviceNames)): ?>
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
            <?php else: ?>
                <!-- No Data Message -->
                <div class="no-data-message" style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 12px; margin-top: 20px;">
                    <div style="font-size: 64px; color: #dee2e6; margin-bottom: 20px;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 style="color: #6c757d; margin-bottom: 10px; font-size: 24px;">No Revenue Data Available</h3>
                    <p style="color: #868e96; font-size: 16px; max-width: 500px; margin: 0 auto;">
                        Revenue data will appear here once treatments are completed and recorded in the system.
                    </p>
                </div>
            <?php endif; ?>
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
                <?php if (!empty($serviceNames)): ?>
                const revenueByServicesCtx = document.getElementById('revenueByServicesChart');
                if (revenueByServicesCtx) {
                    revenueByServicesChart = new Chart(revenueByServicesCtx.getContext('2d'), {
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
                }
                <?php endif; ?>

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
                <?php if (!empty($serviceNames)): ?>
                const revenueByServicesElement = document.getElementById('revenueByServicesChart');
                if (revenueByServicesElement) {
                    revenueByServicesCtx = revenueByServicesElement.getContext('2d');
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
                <?php endif; ?>
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
    // ==================== NOTIFICATION SYSTEM ====================
    function showNotification(type, title, message, icon = null, duration = 5000) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Default icons based on type
        let iconHTML = '';
        if (icon) {
            iconHTML = icon;
        } else {
            switch(type) {
                case 'success':
                    iconHTML = '<i class="fas fa-check"></i>';
                    break;
                case 'warning':
                    iconHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                case 'error':
                    iconHTML = '<i class="fas fa-times-circle"></i>';
                    break;
                case 'info':
                    iconHTML = '<i class="fas fa-info-circle"></i>';
                    break;
            }
        }
        
        notification.innerHTML = `
            <div class="notification-icon ${type === 'success' ? 'success-scale-animation' : ''} ${type === 'warning' ? 'warning-animation' : ''} ${type === 'info' ? 'calendar-animation' : ''}">
                ${iconHTML}
            </div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: ${getNotificationColor(type)}"></div>
            </div>
        `;
        
        container.appendChild(notification);
        
        // Auto remove after duration
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, duration);
    }
    
    function getNotificationColor(type) {
        const colors = {
            'success': '#10B981',
            'warning': '#F59E0B',
            'error': '#EF4444',
            'info': '#3B82F6'
        };
        return colors[type] || colors.info;
    }
    
    function closeNotification(btn) {
        const notification = btn.closest('.notification');
        if (notification) {
            notification.classList.add('hide');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }
    
    // Special notification for appointment confirmations
    function showConfirmNotification(appointmentId) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification success';
        
        notification.innerHTML = `
            <div class="notification-icon success-scale-animation">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M5 13l4 4L19 7" class="check-animation" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="notification-content">
                <div class="notification-title">Appointment Confirmed!</div>
                <div class="notification-message">Appointment #${appointmentId} has been successfully confirmed.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #10B981"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 5000);
    }
    
    // Special notification for reschedule
    function showRescheduleNotification(appointmentId, newDate, newTime) {
        // Format date nicely
        const date = new Date(newDate + 'T00:00:00');
        const formattedDate = date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification info';
        
        notification.innerHTML = `
            <div class="notification-icon calendar-animation">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Appointment Rescheduled!</div>
                <div class="notification-message">Appointment #${appointmentId} has been rescheduled to ${formattedDate} at ${newTime}.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #3B82F6"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 6000);
    }
    
    // Special notification for no-show
    function showNoShowNotification(appointmentId) {
        showNotification(
            'warning',
            'No-Show Marked',
            `Appointment #${appointmentId} has been marked as No-Show.`,
            '<i class="fa-regular fa-eye-slash warning-animation"></i>',
            5000
        );
    }
    
    // Special notification for mark as completed
    function showCompletedNotification(appointmentId) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification success';
        
        notification.innerHTML = `
            <div class="notification-icon success-scale-animation">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M5 13l4 4L19 7" class="check-animation" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="notification-content">
                <div class="notification-title">Appointment Completed!</div>
                <div class="notification-message">Appointment #${appointmentId} has been marked as completed and treatment record saved.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #10B981"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 6000);
    }
    
    // Special notification for patient update
    function showPatientUpdatedNotification(patientId, patientName) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification success';
        
        notification.innerHTML = `
            <div class="notification-icon success-scale-animation">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M5 13l4 4L19 7" class="check-animation" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="notification-content">
                <div class="notification-title">Patient Updated!</div>
                <div class="notification-message">Patient #${patientId} (${patientName}) information has been successfully updated.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #10B981"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 5000);
    }
    
    // Special notification for payment confirmation
    function showPaymentConfirmedNotification(paymentId, amount) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification success';
        
        notification.innerHTML = `
            <div class="notification-icon success-scale-animation">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M5 13l4 4L19 7" class="check-animation" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="notification-content">
                <div class="notification-title">Payment Confirmed!</div>
                <div class="notification-message">Payment #${paymentId} (₱${parseFloat(amount).toFixed(2)}) has been successfully confirmed.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #10B981"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 5000);
    }
    
    // Special notification for payment failed
    function showPaymentFailedNotification(paymentId) {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = 'notification error';
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Payment Marked as Failed</div>
                <div class="notification-message">Payment #${paymentId} has been marked as failed.</div>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">&times;</button>
            <div class="notification-progress">
                <div class="notification-progress-bar" style="color: #EF4444"></div>
            </div>
        `;
        
        container.appendChild(notification);
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, 5000);
    }
    // ==================== END NOTIFICATION SYSTEM ====================

    // ==================== AJAX HANDLERS ====================
    
    // Confirm Appointment
    function confirmAppointment(button) {
        const appointmentId = button.getAttribute('data-appointment-id');
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../controllers/confirmAppointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showConfirmNotification(appointmentId);
                // Reload page after 1.5 seconds to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to confirm appointment. Please try again.');
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while confirming the appointment. Please try again.');
            button.disabled = false;
            button.innerHTML = originalHTML;
        });
    }
    
    // Mark No-Show
    function markNoShow(button) {
        const appointmentId = button.getAttribute('data-appointment-id');
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../controllers/noshowAppointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showNoShowNotification(appointmentId);
                // Reload page after 1.5 seconds to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to mark as no-show. Please try again.');
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while marking as no-show. Please try again.');
            button.disabled = false;
            button.innerHTML = originalHTML;
        });
    }
    
    // Cancel Appointment by Admin
    function cancelAppointmentByAdmin(button) {
        const appointmentId = button.getAttribute('data-appointment-id');
        
        // Show confirmation dialog
        if (!confirm(`Are you sure you want to cancel Appointment #${appointmentId}? An email notification will be sent to the patient.`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../controllers/adminCancelAppointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                return { success: true };
            }
        })
        .then(data => {
            if (data.success) {
                showNotification('success', 'Appointment Cancelled', data.message || 'Appointment has been cancelled and email notification sent.');
                // Reload page after 1.5 seconds to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.error || data.message || 'Failed to cancel appointment. Please try again.');
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while cancelling the appointment. Please try again.');
            button.disabled = false;
            button.innerHTML = originalHTML;
        });
    }
    
    // Handle Reschedule Form Submit
    function handleRescheduleSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const appointmentId = document.getElementById('modalAppointmentID').value;
        const newDate = document.getElementById('new_date_resched').value;
        const newTime = document.getElementById('new_time_resched').value;
        
        // Get readable time from option text
        const timeSelect = document.getElementById('new_time_resched');
        const timeText = timeSelect.options[timeSelect.selectedIndex].text;
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('../controllers/rescheduleAppointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showRescheduleNotification(appointmentId, newDate, timeText);
                closeReschedModal();
                // Reload page after 2 seconds to show updated appointment
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to reschedule appointment. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while rescheduling. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Handle Treatment Form Submit (Mark as Completed)
    function handleTreatmentSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const appointmentId = document.getElementById('treatment_appointment_id').value;
        const patientId = document.getElementById('treatment_patient_id').value;
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // Log form data for debugging
        console.log('Submitting treatment form...');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        fetch('../controllers/saveTreatment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // Try to parse as text first, then JSON
                return response.text().then(text => {
                    console.log('Response text:', text);
                    // Remove any whitespace or BOM
                    text = text.trim();
                    // Try to find JSON in the response
                    const jsonMatch = text.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        try {
                            return JSON.parse(jsonMatch[0]);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    }
                    throw new Error('No JSON found in response: ' + text.substring(0, 100));
                });
            }
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success === true || data.status === 'success') {
                showCompletedNotification(appointmentId);
                closeCompleteAppointmentModal();
                // Reset form
                form.reset();
                
                // Refresh the history section if the treatment history modal is open
                const treatmentHistoryModal = document.getElementById('treatmentHistoryModal');
                if (treatmentHistoryModal && treatmentHistoryModal.style.display === 'block') {
                    // Reload treatment history for the patient
                    if (typeof loadTreatmentHistory === 'function' && patientId) {
                        loadTreatmentHistory(patientId);
                    }
                }
                
                // Refresh the appointments table without full page reload
                // Check if we're in the appointment section
                const appointmentSection = document.getElementById('appointment');
                if (appointmentSection && appointmentSection.style.display !== 'none') {
                    // Reload only the appointment section after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // If not in appointment section, just reload after showing notification
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            } else {
                const errorMsg = data.message || 'Failed to save treatment. Please try again.';
                console.error('Save failed:', errorMsg);
                showNotification('error', 'Error', errorMsg);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('error', 'Error', 'An error occurred while saving treatment: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Handle Edit Patient Form Submit
    function handleEditPatientSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const patientId = document.getElementById('editPatientId').value;
        const patientName = document.getElementById('editFirstName').value + ' ' + document.getElementById('editLastName').value;
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        fetch('../controllers/updatePatient.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showPatientUpdatedNotification(patientId, patientName);
                closeEditPatientModal();
                // Reload page after 1.5 seconds to show updated patient
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to update patient. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while updating patient. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Confirm Payment
    function confirmPayment(button) {
        const paymentId = button.getAttribute('data-payment-id');
        const amount = button.getAttribute('data-payment-amount') || '0';
        const formData = new FormData();
        formData.append('payment_id', paymentId);
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../controllers/confirmPayment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showPaymentConfirmedNotification(paymentId, amount);
                // Reload page after 1.5 seconds to show updated payment
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to confirm payment. Please try again.');
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while confirming payment. Please try again.');
            button.disabled = false;
            button.innerHTML = originalHTML;
        });
    }
    
    // Mark Payment as Failed
    function markPaymentFailed(button) {
        const paymentId = button.getAttribute('data-payment-id');
        const formData = new FormData();
        formData.append('payment_id', paymentId);
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('../controllers/failedPayment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            } else {
                // If it's HTML or redirect, assume success
                return { success: true };
            }
        })
        .then(data => {
            if (data.success || data.status === 'success' || !data.message) {
                showPaymentFailedNotification(paymentId);
                // Reload page after 1.5 seconds to show updated payment
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Error', data.message || 'Failed to mark payment as failed. Please try again.');
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while marking payment as failed. Please try again.');
            button.disabled = false;
            button.innerHTML = originalHTML;
        });
    }
    // ==================== END AJAX HANDLERS ====================

    //Complete Appointment Modal
    function openCompleteAppointmentModal(button) {
        const patientId = button.getAttribute('data-patientid');
        const appointmentId = button.getAttribute('data-appointmentid');
        
        // Validate that we have the required data
        if (!patientId || !appointmentId) {
            showNotification('error', 'Error', 'Missing patient or appointment information.');
            return;
        }
        
        const modal = document.getElementById('complete-appointment-modal');
        const patientIdInput = document.getElementById('treatment_patient_id');
        const appointmentIdInput = document.getElementById('treatment_appointment_id');
        const patientIdDisplay = document.getElementById('patient_id');
        
        // Check if modal and form elements exist
        if (!modal) {
            console.error('Complete appointment modal not found');
            showNotification('error', 'Error', 'Modal not found. Please refresh the page.');
            return;
        }
        
        if (!patientIdInput || !appointmentIdInput || !patientIdDisplay) {
            console.error('Form elements not found');
            showNotification('error', 'Error', 'Form elements not found. Please refresh the page.');
            return;
        }
        
        // Set the values in the modal form
        patientIdInput.value = patientId;
        appointmentIdInput.value = appointmentId;
        patientIdDisplay.value = patientId; // show in the disabled field

        // Show the modal
        modal.style.display = 'block';
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    // Function to close the modal
    function closeCompleteAppointmentModal() {
        const modal = document.getElementById('complete-appointment-modal');
        if (modal) {
            modal.style.display = 'none';
            // Restore body scroll
            document.body.style.overflow = 'auto';
        }
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
    
    // Utility function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // See More Patient Modal
    function seeMoreDetails(patientId) {
        const modal = document.getElementById("treatmentHistoryModal");
        if (!modal) {
            console.error("Treatment history modal not found");
            alert("Error: Modal element not found. Please refresh the page.");
            return;
        }
        
        let modalContent = modal.querySelector(".treatment-modal-content");
        
        // If modal content doesn't exist, create it
        if (!modalContent) {
            modalContent = document.createElement("div");
            modalContent.className = "treatment-modal-content";
            modal.appendChild(modalContent);
        }
        
        // Create modal content with all three sections
        const newModalContent = `
            <div class="treatment-modal-header">
                <h3><i class="fa-solid fa-user"></i> Patient Details - ID: ${patientId}</h3>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn btn-primary" onclick="exportPatientDetails('${patientId}')" style="padding: 8px 15px; font-size: 14px; border: none; border-radius: 4px; cursor: pointer; background-color: #007bff; color: white;">
                        <i class="fa-solid fa-print"></i> Export/Print
                    </button>
                    <span class="treatment-close-btn" onclick="closeTreatmentModal()">&times;</span>
                </div>
            </div>
            <div class="treatment-modal-body">
                <!-- Treatment History Section -->
                <div class="section-container">
                    <h3>Treatment History</h3>
                    <div class="table-container">
                        <table class="treatment-table">
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Prescription</th>
                                    <th>Notes</th>
                                    <th>Cost</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="treatmentHistoryBody">
                                <tr><td colspan="5" style="text-align:center;">Loading treatment history...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Appointment History Section -->
                <div class="section-container">
                    <h3>Appointment History</h3>
                    <div class="table-container">
                        <table class="treatment-table">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Dentist</th>
                                    <th>Service</th>
                                    <th>Branch</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentHistoryBody">
                                <tr><td colspan="6" style="text-align:center;">Loading appointments...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Last Transaction Section -->
                <div class="section-container">
                    <h3>Last Transaction</h3>
                    <div class="table-container">
                        <table class="treatment-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Method</th>
                                    <th>Account Name</th>
                                    <th>Amount</th>
                                    <th>Reference No</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="transactionHistoryBody">
                                <tr><td colspan="6" style="text-align:center;">Loading transaction...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        // Update modal content - use the correct selector
        if (modalContent) {
            modalContent.innerHTML = newModalContent;
        }
        
        // Load all data
        loadTreatmentHistory(patientId);
        loadAppointmentHistory(patientId);
        loadLastTransaction(patientId);
        
        // Show the modal
        modal.style.display = "block";
    }

    function loadTreatmentHistory(patientId) {
        const tbody = document.getElementById("treatmentHistoryBody");
        
        fetch("../controllers/getTreatmentHistory.php?patient_id=" + patientId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success" && data.data && data.data.length > 0) {
                    tbody.innerHTML = "";
                    data.data.forEach(treatment => {
                        const row = `
                            <tr>
                                <td>${escapeHtml(treatment.treatment || 'N/A')}</td>
                                <td>${escapeHtml(treatment.prescription_given || 'N/A')}</td>
                                <td>${escapeHtml(treatment.notes || 'N/A')}</td>
                                <td>₱${parseFloat(treatment.treatment_cost || 0).toFixed(2)}</td>
                                <td>${escapeHtml(treatment.created_at || 'N/A')}</td>
                            </tr>`;
                        tbody.insertAdjacentHTML("beforeend", row);
                    });
                } else {
                    tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No treatment history found.</td></tr>";
                }
            })
            .catch(error => {
                console.error("Error fetching treatment history:", error);
                tbody.innerHTML = "<tr><td colspan='5' style='text-align:center;color:red;'>Error loading treatment history</td></tr>";
            });
    }

    function printTreatmentHistory(patientId) {
        // Fetch patient information
        Promise.all([
            fetch('../controllers/getPatients.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ patient_id: patientId, first_name: '', last_name: '' })),
            fetch('../controllers/getTreatmentHistory.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ status: 'error', data: [] }))
        ]).then(([patientData, treatmentData]) => {
            const patientName = patientData.first_name && patientData.last_name 
                ? `${patientData.first_name} ${patientData.last_name}` 
                : `Patient ID: ${patientId}`;
            
            // Create print window
            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            let htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Treatment History - ${patientName}</title>
                    <style>
                        @media print {
                            @page {
                                margin: 1cm;
                            }
                        }
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                            color: #333;
                        }
                        .header {
                            text-align: center;
                            border-bottom: 3px solid #333;
                            padding-bottom: 20px;
                            margin-bottom: 30px;
                        }
                        .header h1 {
                            margin: 0;
                            color: #2c3e50;
                            font-size: 24px;
                        }
                        .header h2 {
                            margin: 10px 0;
                            color: #34495e;
                            font-size: 18px;
                            font-weight: normal;
                        }
                        .patient-info {
                            margin-bottom: 30px;
                            padding: 15px;
                            background-color: #f8f9fa;
                            border-left: 4px solid #007bff;
                        }
                        .patient-info p {
                            margin: 5px 0;
                            font-size: 14px;
                        }
                        .patient-info strong {
                            color: #2c3e50;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                            font-size: 12px;
                        }
                        th {
                            background-color: #007bff;
                            color: white;
                            padding: 12px;
                            text-align: left;
                            border: 1px solid #ddd;
                        }
                        td {
                            padding: 10px;
                            border: 1px solid #ddd;
                        }
                        tr:nth-child(even) {
                            background-color: #f8f9fa;
                        }
                        .no-data {
                            text-align: center;
                            padding: 40px;
                            color: #999;
                            font-style: italic;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 2px solid #ddd;
                            text-align: center;
                            font-size: 11px;
                            color: #666;
                        }
                        .total-cost {
                            margin-top: 20px;
                            text-align: right;
                            font-size: 16px;
                            font-weight: bold;
                            color: #2c3e50;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Landero Dental Clinic</h1>
                        <h2>Patient Treatment History Report</h2>
                    </div>
                    
                    <div class="patient-info">
                        <p><strong>Patient ID:</strong> ${patientId}</p>
                        <p><strong>Patient Name:</strong> ${patientName}</p>
                        <p><strong>Report Date:</strong> ${currentDate}</p>
                    </div>
            `;
            
            if (treatmentData.status === 'success' && treatmentData.data && treatmentData.data.length > 0) {
                htmlContent += `
                    <table>
                        <thead>
                            <tr>
                                <th>Treatment</th>
                                <th>Prescription Given</th>
                                <th>Notes</th>
                                <th>Treatment Cost</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                let totalCost = 0;
                treatmentData.data.forEach(treatment => {
                    const cost = parseFloat(treatment.treatment_cost) || 0;
                    totalCost += cost;
                    htmlContent += `
                        <tr>
                            <td>${treatment.treatment || 'N/A'}</td>
                            <td>${treatment.prescription_given || 'N/A'}</td>
                            <td>${treatment.notes || 'N/A'}</td>
                            <td>₱${cost.toFixed(2)}</td>
                            <td>${treatment.created_at || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                htmlContent += `
                        </tbody>
                    </table>
                    <div class="total-cost">
                        <strong>Total Treatment Cost: ₱${totalCost.toFixed(2)}</strong>
                    </div>
                `;
            } else {
                htmlContent += `
                    <div class="no-data">
                        <p>No treatment history found for this patient.</p>
                    </div>
                `;
            }
            
            htmlContent += `
                    <div class="footer">
                        <p>Generated on ${currentDate}</p>
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }).catch(error => {
            console.error('Error generating print document:', error);
            alert('Error loading treatment history. Please try again.');
        });
    }

    function exportPatientDetails(patientId) {
        // Fetch all patient information
        Promise.all([
            fetch('../controllers/getPatients.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ patient_id: patientId, first_name: '', last_name: '', birthdate: '', gender: '', email: '', phone: '', address: '' })),
            fetch('../controllers/getTreatmentHistory.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ status: 'error', data: [] })),
            fetch('../controllers/getAppointmentHistory.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ status: 'error', data: [] })),
            fetch('../controllers/getLastTransaction.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .catch(() => ({ status: 'error', data: null }))
        ]).then(([patientData, treatmentData, appointmentData, transactionData]) => {
            const patientName = patientData.first_name && patientData.last_name 
                ? `${patientData.first_name} ${patientData.last_name}` 
                : `Patient ID: ${patientId}`;
            
            // Create print window
            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            let htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Patient Details - ${patientName}</title>
                    <style>
                        @media print {
                            @page {
                                margin: 1cm;
                            }
                        }
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                            color: #333;
                        }
                        .header {
                            text-align: center;
                            border-bottom: 3px solid #333;
                            padding-bottom: 20px;
                            margin-bottom: 30px;
                        }
                        .header h1 {
                            margin: 0;
                            color: #2c3e50;
                            font-size: 24px;
                        }
                        .header h2 {
                            margin: 10px 0;
                            color: #34495e;
                            font-size: 18px;
                            font-weight: normal;
                        }
                        .patient-info {
                            margin-bottom: 30px;
                            padding: 15px;
                            background-color: #f8f9fa;
                            border-left: 4px solid #007bff;
                        }
                        .patient-info p {
                            margin: 5px 0;
                            font-size: 14px;
                        }
                        .patient-info strong {
                            color: #2c3e50;
                        }
                        .section-title {
                            font-size: 18px;
                            color: #2c3e50;
                            margin-top: 30px;
                            margin-bottom: 15px;
                            padding-bottom: 10px;
                            border-bottom: 2px solid #007bff;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                            margin-bottom: 20px;
                            font-size: 12px;
                        }
                        th {
                            background-color: #007bff;
                            color: white;
                            padding: 12px;
                            text-align: left;
                            border: 1px solid #ddd;
                        }
                        td {
                            padding: 10px;
                            border: 1px solid #ddd;
                        }
                        tr:nth-child(even) {
                            background-color: #f8f9fa;
                        }
                        .no-data {
                            text-align: center;
                            padding: 20px;
                            color: #999;
                            font-style: italic;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 2px solid #ddd;
                            text-align: center;
                            font-size: 11px;
                            color: #666;
                        }
                        .total-cost {
                            margin-top: 20px;
                            text-align: right;
                            font-size: 16px;
                            font-weight: bold;
                            color: #2c3e50;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Landero Dental Clinic</h1>
                        <h2>Patient Complete Details Report</h2>
                    </div>
                    
                    <div class="patient-info">
                        <p><strong>Patient ID:</strong> ${patientId}</p>
                        <p><strong>Patient Name:</strong> ${patientName}</p>
                        ${patientData.birthdate ? `<p><strong>Birthdate:</strong> ${new Date(patientData.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>` : ''}
                        ${patientData.gender ? `<p><strong>Gender:</strong> ${patientData.gender}</p>` : ''}
                        ${patientData.email ? `<p><strong>Email:</strong> ${patientData.email}</p>` : ''}
                        ${patientData.phone ? `<p><strong>Phone:</strong> ${patientData.phone}</p>` : ''}
                        ${patientData.address ? `<p><strong>Address:</strong> ${patientData.address}</p>` : ''}
                        <p><strong>Report Date:</strong> ${currentDate}</p>
                    </div>
            `;
            
            // Treatment History Section
            htmlContent += `<div class="section-title">Treatment History</div>`;
            if (treatmentData.status === 'success' && treatmentData.data && treatmentData.data.length > 0) {
                htmlContent += `
                    <table>
                        <thead>
                            <tr>
                                <th>Treatment</th>
                                <th>Prescription Given</th>
                                <th>Notes</th>
                                <th>Treatment Cost</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                let totalCost = 0;
                treatmentData.data.forEach(treatment => {
                    const cost = parseFloat(treatment.treatment_cost) || 0;
                    totalCost += cost;
                    htmlContent += `
                        <tr>
                            <td>${treatment.treatment || 'N/A'}</td>
                            <td>${treatment.prescription_given || 'N/A'}</td>
                            <td>${treatment.notes || 'N/A'}</td>
                            <td>₱${cost.toFixed(2)}</td>
                            <td>${treatment.created_at || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                htmlContent += `
                        </tbody>
                    </table>
                    <div class="total-cost">
                        <strong>Total Treatment Cost: ₱${totalCost.toFixed(2)}</strong>
                    </div>
                `;
            } else {
                htmlContent += `<div class="no-data"><p>No treatment history found for this patient.</p></div>`;
            }
            
            // Appointment History Section
            htmlContent += `<div class="section-title">Appointment History</div>`;
            if (appointmentData.status === 'success' && appointmentData.data && appointmentData.data.length > 0) {
                htmlContent += `
                    <table>
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Dentist</th>
                                <th>Service</th>
                                <th>Branch</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                appointmentData.data.forEach(appointment => {
                    htmlContent += `
                        <tr>
                            <td>${appointment.appointment_id || 'N/A'}</td>
                            <td>${appointment.dentist_name || 'N/A'}</td>
                            <td>${appointment.service_name || 'N/A'}</td>
                            <td>${appointment.branch || 'N/A'}</td>
                            <td>${appointment.appointment_date || 'N/A'}</td>
                            <td>${appointment.appointment_time || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                htmlContent += `
                        </tbody>
                    </table>
                `;
            } else {
                htmlContent += `<div class="no-data"><p>No appointment history found for this patient.</p></div>`;
            }
            
            // Last Transaction Section
            htmlContent += `<div class="section-title">Last Transaction</div>`;
            if (transactionData.status === 'success' && transactionData.data) {
                const transaction = transactionData.data;
                htmlContent += `
                    <table>
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Method</th>
                                <th>Account Name</th>
                                <th>Amount</th>
                                <th>Reference No</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${transaction.payment_id || 'N/A'}</td>
                                <td>${transaction.method || 'N/A'}</td>
                                <td>${transaction.account_name || 'N/A'}</td>
                                <td>₱${parseFloat(transaction.amount || 0).toFixed(2)}</td>
                                <td>${transaction.reference_no || 'N/A'}</td>
                                <td>${transaction.status || 'N/A'}</td>
                            </tr>
                        </tbody>
                    </table>
                `;
            } else {
                htmlContent += `<div class="no-data"><p>No transaction history found for this patient.</p></div>`;
            }
            
            htmlContent += `
                    <div class="footer">
                        <p>Generated on ${currentDate}</p>
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }).catch(error => {
            console.error('Error generating print document:', error);
            alert('Error loading patient details. Please try again.');
        });
    }

    function loadAppointmentHistory(patientId) {
        const tbody = document.getElementById("appointmentHistoryBody");
        if (!tbody) {
            console.error("Appointment history tbody not found");
            return;
        }
        
        fetch("../controllers/getAppointmentHistory.php?patient_id=" + patientId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success" && data.data && data.data.length > 0) {
                    tbody.innerHTML = "";
                    data.data.forEach(appointment => {
                        const row = `
                            <tr>
                                <td>${escapeHtml(appointment.appointment_id || 'N/A')}</td>
                                <td>${escapeHtml(appointment.dentist_name || 'N/A')}</td>
                                <td>${escapeHtml(appointment.service_name || 'N/A')}</td>
                                <td>${escapeHtml(appointment.branch || 'N/A')}</td>
                                <td>${escapeHtml(appointment.appointment_date || 'N/A')}</td>
                                <td>${escapeHtml(appointment.appointment_time || 'N/A')}</td>
                            </tr>`;
                        tbody.insertAdjacentHTML("beforeend", row);
                    });
                } else {
                    tbody.innerHTML = "<tr><td colspan='6' style='text-align:center;'>No appointment history found.</td></tr>";
                }
            })
            .catch(error => {
                console.error("Error fetching appointment history:", error);
                tbody.innerHTML = "<tr><td colspan='6' style='text-align:center;color:red;'>Error loading appointments</td></tr>";
            });
    }

    function loadLastTransaction(patientId) {
        const tbody = document.getElementById("transactionHistoryBody");
        if (!tbody) {
            console.error("Transaction history tbody not found");
            return;
        }
        
        fetch("../controllers/getLastTransaction.php?patient_id=" + patientId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success" && data.data) {
                    tbody.innerHTML = "";
                    const transaction = data.data;
                    const row = `
                        <tr>
                            <td>${escapeHtml(transaction.payment_id || 'N/A')}</td>
                            <td>${escapeHtml(transaction.method || 'N/A')}</td>
                            <td>${escapeHtml(transaction.account_name || 'N/A')}</td>
                            <td>₱${parseFloat(transaction.amount || 0).toFixed(2)}</td>
                            <td>${escapeHtml(transaction.reference_no || 'N/A')}</td>
                            <td><span class="status status-${(transaction.status || '').toLowerCase()}">${escapeHtml(transaction.status || 'N/A')}</span></td>
                        </tr>`;
                    tbody.insertAdjacentHTML("beforeend", row);
                } else {
                    tbody.innerHTML = "<tr><td colspan='6' style='text-align:center;'>No transaction history found.</td></tr>";
                }
            })
            .catch(error => {
                console.error("Error fetching transaction history:", error);
                tbody.innerHTML = "<tr><td colspan='6' style='text-align:center;color:red;'>Error loading transaction</td></tr>";
            });
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

    //Modal Break
    // Appointment availability checking
    $(document).ready(function(){
        function checkAvailabilityAdminAdd() {
            var selectedDate = $("#appointment_date").val();
            if (selectedDate) {
                $.ajax({
                    url: '../controllers/getAppointmentsAdmin.php',
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
                    url: '../controllers/getAppointmentsAdminResched.php',
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

    // Handle date category change
    function handleDateCategoryChange() {
        const dateCategory = document.getElementById("filter-date-category").value;
        const dateInput = document.getElementById("filter-date");
        
        if (dateCategory === "custom") {
            dateInput.style.display = "inline-block";
            dateInput.value = "";
        } else {
            dateInput.style.display = "none";
            dateInput.value = "";
            filterAppointments(); // Auto-filter when category changes
        }
    }

    // Pagination state
    let currentPage = 1;
    const rowsPerPage = 5;

    // Pagination state for Services
    let servicesCurrentPage = 1;
    const servicesRowsPerPage = 5;

    // Pagination state for Patients
    let patientsCurrentPage = 1;
    const patientsRowsPerPage = 5;

    // Filter appointments and update pagination
    function filterAppointments() {
        const dateCategory = document.getElementById("filter-date-category").value;
        const selectedDate = document.getElementById("filter-date").value;
        const selectedStatus = document.getElementById("filter-status").value.toLowerCase();
        const rows = document.querySelectorAll(".appointment-row");
        
        // Calculate date ranges once
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayStr = today.toISOString().split('T')[0];
        
        let weekStart = null, weekEnd = null;
        let monthStart = null, monthEnd = null;
        
        if (dateCategory === "week") {
            // This week: Monday to Saturday
            const dayOfWeek = today.getDay(); // 0 = Sunday, 1 = Monday, etc.
            const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            weekStart = new Date(today);
            weekStart.setDate(today.getDate() - daysToMonday);
            weekStart.setHours(0, 0, 0, 0);
            weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 5); // Monday + 5 days = Saturday
            weekEnd.setHours(23, 59, 59, 999);
        } else if (dateCategory === "month") {
            // This month: first day to last day of current month
            monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            monthStart.setHours(0, 0, 0, 0);
            monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            monthEnd.setHours(23, 59, 59, 999);
        }
        
        // Filter rows and mark visible ones
        let visibleRows = [];
        rows.forEach(row => {
            const rowDate = row.getAttribute("data-date");
            const rowStatus = row.getAttribute("data-status").toLowerCase();
            
            // Check date match
            let matchesDate = true;
            
            if (dateCategory === "custom" && selectedDate) {
                // Custom date: exact match
                matchesDate = rowDate === selectedDate;
            } else if (dateCategory === "today") {
                // Today: only today's date
                matchesDate = rowDate === todayStr;
            } else if (dateCategory === "week") {
                // This week: Monday to Saturday
                const rowDateObj = new Date(rowDate);
                rowDateObj.setHours(0, 0, 0, 0);
                matchesDate = rowDateObj >= weekStart && rowDateObj <= weekEnd;
            } else if (dateCategory === "month") {
                // This month: first day to last day of current month
                const rowDateObj = new Date(rowDate);
                rowDateObj.setHours(0, 0, 0, 0);
                matchesDate = rowDateObj >= monthStart && rowDateObj <= monthEnd;
            } else if (dateCategory === "") {
                // All dates
                matchesDate = true;
            }
            
            // Check status match
            const matchesStatus = selectedStatus === "" || rowStatus === selectedStatus;
            
            // Mark row as visible or hidden
            if (matchesDate && matchesStatus) {
                row.setAttribute("data-visible", "true");
                visibleRows.push(row);
            } else {
                row.setAttribute("data-visible", "false");
            }
        });

        // Reset to page 1 after filtering
        currentPage = 1;
        
        // Update pagination
        updatePagination(visibleRows);
        showPage(visibleRows, currentPage);
    }

    // Update pagination controls
    function updatePagination(visibleRows) {
        const totalRows = visibleRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        const paginationContainer = document.getElementById("pagination-container");
        const paginationInfo = document.getElementById("pagination-info");
        const paginationNumbers = document.getElementById("pagination-numbers");
        const prevBtn = document.getElementById("prev-page-btn");
        const nextBtn = document.getElementById("next-page-btn");

        // Hide pagination if no rows
        if (totalRows === 0) {
            paginationContainer.style.display = "none";
            return;
        }

        paginationContainer.style.display = "flex";

        // Update info
        const startRow = (currentPage - 1) * rowsPerPage + 1;
        const endRow = Math.min(currentPage * rowsPerPage, totalRows);
        paginationInfo.textContent = `Showing ${startRow}-${endRow} of ${totalRows} appointments`;

        // Update buttons
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage >= totalPages;

        // Generate page numbers
        paginationNumbers.innerHTML = "";
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        // First page and ellipsis
        if (startPage > 1) {
            createPageNumber(1, paginationNumbers);
            if (startPage > 2) {
                createEllipsis(paginationNumbers);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            createPageNumber(i, paginationNumbers);
        }

        // Last page and ellipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                createEllipsis(paginationNumbers);
            }
            createPageNumber(totalPages, paginationNumbers);
        }
    }

    // Create page number button
    function createPageNumber(pageNum, container) {
        const pageBtn = document.createElement("button");
        pageBtn.className = "pagination-number" + (pageNum === currentPage ? " active" : "");
        pageBtn.textContent = pageNum;
        pageBtn.onclick = () => goToPage(pageNum);
        container.appendChild(pageBtn);
    }

    // Create ellipsis
    function createEllipsis(container) {
        const ellipsis = document.createElement("span");
        ellipsis.className = "pagination-number ellipsis";
        ellipsis.textContent = "...";
        container.appendChild(ellipsis);
    }

    // Show specific page
    function showPage(visibleRows, page) {
        const startIndex = (page - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const rowsToShow = visibleRows.slice(startIndex, endIndex);

        // Hide all visible rows first
        visibleRows.forEach(row => {
            row.style.display = "none";
        });

        // Show only rows for current page
        rowsToShow.forEach(row => {
            row.style.display = "table-row";
        });
    }

    // Go to specific page
    function goToPage(page) {
        const rows = document.querySelectorAll(".appointment-row[data-visible='true']");
        if (rows.length === 0) return;

        currentPage = page;
        const visibleRows = Array.from(rows);
        updatePagination(visibleRows);
        showPage(visibleRows, currentPage);
    }

    // Change page (previous/next)
    function changePage(direction) {
        const rows = document.querySelectorAll(".appointment-row[data-visible='true']");
        if (rows.length === 0) return;

        const totalPages = Math.ceil(rows.length / rowsPerPage);
        const newPage = currentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            goToPage(newPage);
        }
    }

    // Initialize pagination on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Mark all rows as visible initially (before any filtering)
        const allRows = document.querySelectorAll(".appointment-row");
        allRows.forEach(row => {
            row.setAttribute("data-visible", "true");
        });

        // Initialize pagination for appointments if the section is visible
        const appointmentSection = document.getElementById('appointment');
        if (appointmentSection && appointmentSection.style.display !== 'none') {
            setTimeout(() => {
                filterAppointments();
            }, 100);
        } else {
            // Even if section is hidden, set up initial pagination state
            setTimeout(() => {
                const visibleRows = Array.from(document.querySelectorAll(".appointment-row[data-visible='true']"));
                if (visibleRows.length > 0) {
                    updatePagination(visibleRows);
                    showPage(visibleRows, 1);
                }
            }, 100);
        }
    });

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

        // Initialize pagination when appointment section is shown
        if (sectionId === 'appointment') {
            setTimeout(() => {
                filterAppointments();
            }, 100);
        }
        
        // Initialize pagination when services section is shown
        if (sectionId === 'services') {
            setTimeout(() => {
                filterServices();
            }, 100);
        }
        
        // Initialize pagination when patients section is shown
        if (sectionId === 'patients') {
            setTimeout(() => {
                filterPatients();
            }, 100);
        }
    }
    
    // Show Controls Popup
    function showControlsPopup() {
        document.getElementById('controlsPopupModal').style.display = 'block';
    }
    
    // Close Controls Popup
    function closeControlsPopup() {
        document.getElementById('controlsPopupModal').style.display = 'none';
    }
    
    // Navigate to Clinic Control with animation
    function navigateToClinicControl() {
        closeControlsPopup();
        const mainContent = document.querySelector('.main-content');
        const clinicControlBtn = document.querySelector('.sidebar-btn-clinic-control');
        
        if (mainContent) {
            mainContent.classList.add('page-fade-out');
        }
        
        if (clinicControlBtn) {
            clinicControlBtn.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            window.location.href = 'clinicControl.php';
        }, 300);
    }
    
    // Navigate to User Control with animation
    function navigateToUserControl() {
        closeControlsPopup();
        const mainContent = document.querySelector('.main-content');
        const clinicControlBtn = document.querySelector('.sidebar-btn-clinic-control');
        
        if (mainContent) {
            mainContent.classList.add('page-fade-out');
        }
        
        if (clinicControlBtn) {
            clinicControlBtn.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            window.location.href = '../views/userControl.php';
        }, 300);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('controlsPopupModal');
        if (event.target === modal) {
            closeControlsPopup();
        }
    });

    function printAppointments() {
        window.print();
    }

    // ==================== PAYMENT FILTERING ====================
    
    // Handle Payment Date Category Change
    function handlePaymentDateCategoryChange() {
        const dateCategory = document.getElementById("filter-payment-date-category").value;
        const dateInput = document.getElementById("filter-payment-date");
        
        if (dateCategory === "custom") {
            dateInput.style.display = "inline-block";
            dateInput.value = "";
        } else {
            dateInput.style.display = "none";
            dateInput.value = "";
            filterPayments(); // Auto-filter when category changes
        }
    }

    // Payment Pagination Variables
    let currentPaymentPage = 1;
    const paymentRowsPerPage = 5;
    
    // Filter Payments with Pagination
    function filterPayments() {
        const dateCategory = document.getElementById("filter-payment-date-category").value;
        const selectedDate = document.getElementById("filter-payment-date").value;
        const selectedStatus = document.getElementById("filter-payment-status").value.toLowerCase();
        const selectedMethod = document.getElementById("filter-payment-method").value.toLowerCase();
        const rows = document.querySelectorAll(".payment-row");
        
        // Calculate date ranges once
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayStr = today.toISOString().split('T')[0];
        
        let weekStart = null, weekEnd = null;
        let monthStart = null, monthEnd = null;
        
        if (dateCategory === "week") {
            // This week: Monday to Saturday
            const dayOfWeek = today.getDay();
            const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            weekStart = new Date(today);
            weekStart.setDate(today.getDate() - daysToMonday);
            weekStart.setHours(0, 0, 0, 0);
            weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 5);
            weekEnd.setHours(23, 59, 59, 999);
        } else if (dateCategory === "month") {
            // This month: first day to last day of current month
            monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            monthStart.setHours(0, 0, 0, 0);
            monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            monthEnd.setHours(23, 59, 59, 999);
        }
        
        const visibleRows = [];
        
        // Filter rows
        rows.forEach(row => {
            const rowDate = row.getAttribute("data-date");
            const rowStatus = row.getAttribute("data-status").toLowerCase();
            const rowMethod = row.getAttribute("data-method").toLowerCase();
            
            // Check date match
            let matchesDate = true;
            
            if (dateCategory === "custom" && selectedDate) {
                // Custom date: exact match
                matchesDate = rowDate === selectedDate;
            } else if (dateCategory === "today") {
                // Today: only today's date
                matchesDate = rowDate === todayStr;
            } else if (dateCategory === "week") {
                // This week: Monday to Saturday
                if (rowDate) {
                    const rowDateObj = new Date(rowDate);
                    rowDateObj.setHours(0, 0, 0, 0);
                    matchesDate = rowDateObj >= weekStart && rowDateObj <= weekEnd;
                } else {
                    matchesDate = false;
                }
            } else if (dateCategory === "month") {
                // This month: first day to last day of current month
                if (rowDate) {
                    const rowDateObj = new Date(rowDate);
                    rowDateObj.setHours(0, 0, 0, 0);
                    matchesDate = rowDateObj >= monthStart && rowDateObj <= monthEnd;
                } else {
                    matchesDate = false;
                }
            } else if (dateCategory === "") {
                // All dates
                matchesDate = true;
            }
            
            // Check status match
            const matchesStatus = selectedStatus === "" || rowStatus === selectedStatus;
            
            // Check method match
            const matchesMethod = selectedMethod === "" || rowMethod === selectedMethod;
            
            // Show/hide row and track visible rows
            if (matchesDate && matchesStatus && matchesMethod) {
                visibleRows.push(row);
            } else {
                row.style.display = "none";
            }
        });
        
        // Reset to first page when filters change
        currentPaymentPage = 1;
        
        // Apply pagination
        updatePaymentPagination(visibleRows);
    }
    
    // Update Payment Pagination
    function updatePaymentPagination(visibleRows) {
        const totalPages = Math.ceil(visibleRows.length / paymentRowsPerPage);
        const startIndex = (currentPaymentPage - 1) * paymentRowsPerPage;
        const endIndex = startIndex + paymentRowsPerPage;
        
        // Hide all visible rows first
        visibleRows.forEach(row => {
            row.style.display = "none";
        });
        
        // Show only rows for current page
        for (let i = startIndex; i < endIndex && i < visibleRows.length; i++) {
            visibleRows[i].style.display = "table-row";
        }
        
        // Update pagination controls
        document.getElementById("payment-current-page").textContent = currentPaymentPage;
        document.getElementById("payment-total-pages").textContent = totalPages || 1;
        
        const prevBtn = document.getElementById("payment-prev-btn");
        const nextBtn = document.getElementById("payment-next-btn");
        
        prevBtn.disabled = currentPaymentPage <= 1;
        nextBtn.disabled = currentPaymentPage >= totalPages || totalPages === 0;
        
        // Hide pagination if no results or only one page
        const paginationDiv = document.getElementById("payment-pagination");
        if (visibleRows.length === 0 || totalPages <= 1) {
            paginationDiv.style.display = "none";
        } else {
            paginationDiv.style.display = "flex";
        }
    }
    
    // Change Payment Page
    function changePaymentPage(direction) {
        const dateCategory = document.getElementById("filter-payment-date-category").value;
        const selectedDate = document.getElementById("filter-payment-date").value;
        const selectedStatus = document.getElementById("filter-payment-status").value.toLowerCase();
        const selectedMethod = document.getElementById("filter-payment-method").value.toLowerCase();
        const rows = document.querySelectorAll(".payment-row");
        
        // Calculate date ranges (same as filterPayments)
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayStr = today.toISOString().split('T')[0];
        
        let weekStart = null, weekEnd = null;
        let monthStart = null, monthEnd = null;
        
        if (dateCategory === "week") {
            const dayOfWeek = today.getDay();
            const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            weekStart = new Date(today);
            weekStart.setDate(today.getDate() - daysToMonday);
            weekStart.setHours(0, 0, 0, 0);
            weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 5);
            weekEnd.setHours(23, 59, 59, 999);
        } else if (dateCategory === "month") {
            monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            monthStart.setHours(0, 0, 0, 0);
            monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            monthEnd.setHours(23, 59, 59, 999);
        }
        
        const visibleRows = [];
        
        // Get visible rows (same filtering logic)
        rows.forEach(row => {
            const rowDate = row.getAttribute("data-date");
            const rowStatus = row.getAttribute("data-status").toLowerCase();
            const rowMethod = row.getAttribute("data-method").toLowerCase();
            
            let matchesDate = true;
            
            if (dateCategory === "custom" && selectedDate) {
                matchesDate = rowDate === selectedDate;
            } else if (dateCategory === "today") {
                matchesDate = rowDate === todayStr;
            } else if (dateCategory === "week") {
                if (rowDate) {
                    const rowDateObj = new Date(rowDate);
                    rowDateObj.setHours(0, 0, 0, 0);
                    matchesDate = rowDateObj >= weekStart && rowDateObj <= weekEnd;
                } else {
                    matchesDate = false;
                }
            } else if (dateCategory === "month") {
                if (rowDate) {
                    const rowDateObj = new Date(rowDate);
                    rowDateObj.setHours(0, 0, 0, 0);
                    matchesDate = rowDateObj >= monthStart && rowDateObj <= monthEnd;
                } else {
                    matchesDate = false;
                }
            } else if (dateCategory === "") {
                matchesDate = true;
            }
            
            const matchesStatus = selectedStatus === "" || rowStatus === selectedStatus;
            const matchesMethod = selectedMethod === "" || rowMethod === selectedMethod;
            
            if (matchesDate && matchesStatus && matchesMethod) {
                visibleRows.push(row);
            }
        });
        
        const totalPages = Math.ceil(visibleRows.length / paymentRowsPerPage);
        
        // Update current page
        currentPaymentPage += direction;
        if (currentPaymentPage < 1) currentPaymentPage = 1;
        if (currentPaymentPage > totalPages) currentPaymentPage = totalPages;
        
        // Apply pagination
        updatePaymentPagination(visibleRows);
    }
    
    // Print Payments
    function printPayments() {
        window.print();
    }
    
    // Initialize Payment Pagination on Page Load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize pagination for payments table
        // All rows are visible by default, so get all of them
        const paymentRows = document.querySelectorAll(".payment-row");
        if (paymentRows.length > 0) {
            const allRows = Array.from(paymentRows);
            // Set current page to 1 and update pagination
            currentPaymentPage = 1;
            updatePaymentPagination(allRows);
        }
    });
    
    // ==================== END PAYMENT FILTERING ====================

    // ==================== PATIENT FILTERING ====================
    
    // Filter Patients
    function filterPatients() {
        const searchInput = document.getElementById("filter-patient-search");
        const searchText = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const clearBtn = document.getElementById("clear-search-btn");
        
        // Show/hide clear button based on input
        if (clearBtn) {
            if (searchText !== "") {
                clearBtn.style.display = "flex";
            } else {
                clearBtn.style.display = "none";
            }
        }
        
        // Reset to first page after filtering
        patientsCurrentPage = 1;
        
        // Get visible rows and update pagination
        const visibleRows = getVisiblePatientsRows();
        updatePatientsPagination(visibleRows);
        showPatientsPage(visibleRows, patientsCurrentPage);
    }
    
    // Clear Patient Search
    function clearPatientSearch() {
        const searchInput = document.getElementById("filter-patient-search");
        const clearBtn = document.getElementById("clear-search-btn");
        
        searchInput.value = "";
        clearBtn.style.display = "none";
        filterPatients(); // Re-filter to show all patients
        searchInput.focus(); // Focus back on the search input
    }
    
    // Print Patients
    function printPatients() {
        window.print();
    }
    
    // Update Patients Pagination
    function updatePatientsPagination(visibleRows) {
        const totalRows = visibleRows.length;
        const totalPages = Math.ceil(totalRows / patientsRowsPerPage);
        const paginationContainer = document.getElementById("patients-pagination-container");
        const paginationInfo = document.getElementById("patients-pagination-info");
        const paginationNumbers = document.getElementById("patients-pagination-numbers");
        const prevBtn = document.getElementById("patients-prev-page-btn");
        const nextBtn = document.getElementById("patients-next-page-btn");

        // Hide pagination if no rows
        if (totalRows === 0) {
            if (paginationContainer) paginationContainer.style.display = "none";
            return;
        }

        if (paginationContainer) paginationContainer.style.display = "flex";

        // Update info
        const startRow = (patientsCurrentPage - 1) * patientsRowsPerPage + 1;
        const endRow = Math.min(patientsCurrentPage * patientsRowsPerPage, totalRows);
        if (paginationInfo) paginationInfo.textContent = `Showing ${startRow}-${endRow} of ${totalRows} patients`;

        // Update buttons
        if (prevBtn) prevBtn.disabled = patientsCurrentPage === 1;
        if (nextBtn) nextBtn.disabled = patientsCurrentPage >= totalPages;

        // Generate page numbers
        if (paginationNumbers) paginationNumbers.innerHTML = "";
        const maxPagesToShow = 5;
        let startPage = Math.max(1, patientsCurrentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        // First page and ellipsis
        if (startPage > 1 && paginationNumbers) {
            createPatientsPageNumber(1, paginationNumbers);
            if (startPage > 2) {
                createPatientsEllipsis(paginationNumbers);
            }
        }

        // Page numbers
        if (paginationNumbers) {
            for (let i = startPage; i <= endPage; i++) {
                createPatientsPageNumber(i, paginationNumbers);
            }
        }

        // Last page and ellipsis
        if (endPage < totalPages && paginationNumbers) {
            if (endPage < totalPages - 1) {
                createPatientsEllipsis(paginationNumbers);
            }
            createPatientsPageNumber(totalPages, paginationNumbers);
        }
    }

    // Create Patients page number button
    function createPatientsPageNumber(pageNum, container) {
        const pageBtn = document.createElement("button");
        pageBtn.className = "pagination-number" + (pageNum === patientsCurrentPage ? " active" : "");
        pageBtn.textContent = pageNum;
        pageBtn.onclick = () => goToPatientsPage(pageNum);
        container.appendChild(pageBtn);
    }

    // Create Patients ellipsis
    function createPatientsEllipsis(container) {
        const ellipsis = document.createElement("span");
        ellipsis.className = "pagination-number ellipsis";
        ellipsis.textContent = "...";
        container.appendChild(ellipsis);
    }

    // Show Patients specific page
    function showPatientsPage(visibleRows, page) {
        const startIndex = (page - 1) * patientsRowsPerPage;
        const endIndex = startIndex + patientsRowsPerPage;
        const rowsToShow = visibleRows.slice(startIndex, endIndex);

        // Hide all patient rows first
        const allPatientRows = document.querySelectorAll(".patient-row");
        allPatientRows.forEach(row => {
            row.style.display = "none";
        });

        // Show only rows for current page
        rowsToShow.forEach(row => {
            row.style.display = "table-row";
        });
    }

    // Get visible Patients rows based on current filters
    function getVisiblePatientsRows() {
        const selectedGender = document.getElementById("filter-patient-gender").value.toLowerCase();
        const selectedAge = document.getElementById("filter-patient-age").value.toLowerCase();
        const searchInput = document.getElementById("filter-patient-search");
        const searchText = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const rows = document.querySelectorAll(".patient-row");
        const visibleRows = [];
        
        rows.forEach(row => {
            const rowGender = row.getAttribute("data-gender").toLowerCase();
            const rowAgeCategory = row.getAttribute("data-age-category").toLowerCase();
            const rowSearch = row.getAttribute("data-search").toLowerCase();
            
            const matchesGender = selectedGender === "" || rowGender === selectedGender;
            const matchesAge = selectedAge === "" || rowAgeCategory === selectedAge;
            const matchesSearch = searchText === "" || rowSearch.includes(searchText);
            
            if (matchesGender && matchesAge && matchesSearch) {
                visibleRows.push(row);
            }
        });
        
        return visibleRows;
    }

    // Go to Patients specific page
    function goToPatientsPage(page) {
        const visibleRows = getVisiblePatientsRows();
        if (visibleRows.length === 0) return;

        patientsCurrentPage = page;
        updatePatientsPagination(visibleRows);
        showPatientsPage(visibleRows, patientsCurrentPage);
    }

    // Change Patients page (previous/next)
    function changePatientsPage(direction) {
        const visibleRows = getVisiblePatientsRows();
        if (visibleRows.length === 0) return;

        const totalPages = Math.ceil(visibleRows.length / patientsRowsPerPage);
        const newPage = patientsCurrentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            goToPatientsPage(newPage);
        }
    }
    
    // ==================== END PATIENT FILTERING ====================

    // ==================== SERVICE FILTERING ====================
    
    // Filter Services
    function filterServices() {
        // Reset to first page after filtering
        servicesCurrentPage = 1;
        
        // Get visible rows and update pagination
        const visibleRows = getVisibleServicesRows();
        updateServicesPagination(visibleRows);
        showServicesPage(visibleRows, servicesCurrentPage);
    }
    
    // Print Services
    function printServices() {
        window.print();
    }
    
    // Update Services Pagination
    function updateServicesPagination(visibleRows) {
        const totalRows = visibleRows.length;
        const totalPages = Math.ceil(totalRows / servicesRowsPerPage);
        const paginationContainer = document.getElementById("services-pagination-container");
        const paginationInfo = document.getElementById("services-pagination-info");
        const paginationNumbers = document.getElementById("services-pagination-numbers");
        const prevBtn = document.getElementById("services-prev-page-btn");
        const nextBtn = document.getElementById("services-next-page-btn");

        // Hide pagination if no rows
        if (totalRows === 0) {
            if (paginationContainer) paginationContainer.style.display = "none";
            return;
        }

        if (paginationContainer) paginationContainer.style.display = "flex";

        // Update info
        const startRow = (servicesCurrentPage - 1) * servicesRowsPerPage + 1;
        const endRow = Math.min(servicesCurrentPage * servicesRowsPerPage, totalRows);
        if (paginationInfo) paginationInfo.textContent = `Showing ${startRow}-${endRow} of ${totalRows} services`;

        // Update buttons
        if (prevBtn) prevBtn.disabled = servicesCurrentPage === 1;
        if (nextBtn) nextBtn.disabled = servicesCurrentPage >= totalPages;

        // Generate page numbers
        if (paginationNumbers) paginationNumbers.innerHTML = "";
        const maxPagesToShow = 5;
        let startPage = Math.max(1, servicesCurrentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        // First page and ellipsis
        if (startPage > 1 && paginationNumbers) {
            createServicesPageNumber(1, paginationNumbers);
            if (startPage > 2) {
                createServicesEllipsis(paginationNumbers);
            }
        }

        // Page numbers
        if (paginationNumbers) {
            for (let i = startPage; i <= endPage; i++) {
                createServicesPageNumber(i, paginationNumbers);
            }
        }

        // Last page and ellipsis
        if (endPage < totalPages && paginationNumbers) {
            if (endPage < totalPages - 1) {
                createServicesEllipsis(paginationNumbers);
            }
            createServicesPageNumber(totalPages, paginationNumbers);
        }
    }

    // Create Services page number button
    function createServicesPageNumber(pageNum, container) {
        const pageBtn = document.createElement("button");
        pageBtn.className = "pagination-number" + (pageNum === servicesCurrentPage ? " active" : "");
        pageBtn.textContent = pageNum;
        pageBtn.onclick = () => goToServicesPage(pageNum);
        container.appendChild(pageBtn);
    }

    // Create Services ellipsis
    function createServicesEllipsis(container) {
        const ellipsis = document.createElement("span");
        ellipsis.className = "pagination-number ellipsis";
        ellipsis.textContent = "...";
        container.appendChild(ellipsis);
    }

    // Show Services specific page
    function showServicesPage(visibleRows, page) {
        const startIndex = (page - 1) * servicesRowsPerPage;
        const endIndex = startIndex + servicesRowsPerPage;
        const rowsToShow = visibleRows.slice(startIndex, endIndex);

        // Hide all service rows first
        const allServiceRows = document.querySelectorAll(".service-row");
        allServiceRows.forEach(row => {
            row.style.display = "none";
        });

        // Show only rows for current page
        rowsToShow.forEach(row => {
            row.style.display = "table-row";
        });
    }

    // Get visible Services rows based on current filters
    function getVisibleServicesRows() {
        const selectedCategory = document.getElementById("filter-service-category").value.toLowerCase();
        const rows = document.querySelectorAll(".service-row");
        const visibleRows = [];
        
        rows.forEach(row => {
            const rowCategory = row.getAttribute("data-category").toLowerCase();
            const matchesCategory = selectedCategory === "" || rowCategory === selectedCategory;
            if (matchesCategory) {
                visibleRows.push(row);
            }
        });
        
        return visibleRows;
    }

    // Go to Services specific page
    function goToServicesPage(page) {
        const visibleRows = getVisibleServicesRows();
        if (visibleRows.length === 0) return;

        servicesCurrentPage = page;
        updateServicesPagination(visibleRows);
        showServicesPage(visibleRows, servicesCurrentPage);
    }

    // Change Services page (previous/next)
    function changeServicesPage(direction) {
        const visibleRows = getVisibleServicesRows();
        if (visibleRows.length === 0) return;

        const totalPages = Math.ceil(visibleRows.length / servicesRowsPerPage);
        const newPage = servicesCurrentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            goToServicesPage(newPage);
        }
    }
    
    // ==================== END SERVICE FILTERING ====================

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


        // Close modals when clicking outside
        const followUpModal = document.getElementById('followUpModal');
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
            if (followUpModal && event.target === followUpModal) {
                followUpModal.style.display = 'none';
            }
        });
    });

    // Follow-Up Modal Functions
    function openFollowUpModal(button) {
        const appointmentId = button.getAttribute('data-appointment-id');
        const patientId = button.getAttribute('data-patient-id');
        const patientName = button.getAttribute('data-patient-name');
        
        document.getElementById('followup_patient_id').value = patientId;
        document.getElementById('followup_appointment_id').value = appointmentId;
        document.getElementById('followup_patient_name').value = patientName;
        
        // Reset form
        document.getElementById('followup_date').value = '';
        document.getElementById('followup_time').value = '';
        
        document.getElementById('followUpModal').style.display = 'block';
    }

    function closeFollowUpModal() {
        document.getElementById('followUpModal').style.display = 'none';
    }

    // Close modal functions
    function closeAddAppointmentModal() {
        document.getElementById('addAppointmentModal').style.display = 'none';
    }

    function closeAddModal() {
        document.getElementById('addServiceModal').style.display = 'none';
    }


    function closeEditModal() {
    document.getElementById('editServiceModal').style.display = 'none';
    }

    function editServicebtn(serviceId) {
        document.getElementById('editServiceModal').style.display = 'block';
        
        fetch('../controllers/getServices.php?id=' + encodeURIComponent(serviceId))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Fill modal fields
                document.getElementById('editServiceId').value = data.service_id;
                document.getElementById('editServiceCategory').value = data.service_category;
                document.getElementById('editSubService').value = data.sub_service;
                document.getElementById('editDescription').value = data.description;
                document.getElementById('editPrice').value = data.price;
            })
            .catch(error => {
                console.error('Error fetching service:', error);
                showNotification('error', 'Error Loading Service', error.message || 'Failed to load service details.');
            });
    }

    function closeEditDentistModal() {
        document.getElementById('editDentistModal').style.display = 'none';
    }

    // Reschedule functions
    function openReschedModalWithID(btn, event) {
        if (event) {
            event.preventDefault(); // Prevent default link behavior
        }
        const appointmentID = btn.getAttribute('data-id');
        
        if (!appointmentID) {
            showNotification('error', 'Error', 'Appointment ID not found. Please try again.');
            return false;
        }
        
        // Set the appointment ID in the hidden input
        const modalAppointmentIDInput = document.getElementById('modalAppointmentID');
        if (modalAppointmentIDInput) {
            modalAppointmentIDInput.value = appointmentID;
        }
        
        // Reset the form fields (except the appointment ID)
        const reschedForm = document.querySelector('#reschedModal form');
        if (reschedForm) {
            const dateInput = reschedForm.querySelector('#new_date_resched');
            const timeSelect = reschedForm.querySelector('#new_time_resched');
            if (dateInput) dateInput.value = '';
            if (timeSelect) timeSelect.value = '';
            // Ensure appointment ID is still set
            modalAppointmentIDInput.value = appointmentID;
        }
        
        openReschedModal();
        return false; // Prevent link navigation
    }

    // Load booked slots for selected date
    function loadBookedSlots() {
        const dateInput = document.getElementById('new_date_resched');
        const timeSelect = document.getElementById('new_time_resched');
        
        if (!dateInput.value) {
            // Reset time slots if no date selected
            const options = timeSelect.querySelectorAll('option:not(:first-child)');
            options.forEach(opt => {
                opt.disabled = false;
                opt.textContent = opt.textContent.replace(' (Booked)', '');
            });
            return;
        }
        
        // Fetch booked slots for selected date
        fetch(`../controllers/getAppointmentsAdminResched.php?new_date_resched=${dateInput.value}`)
            .then(response => response.json())
            .then(bookedSlots => {
                console.log('Booked slots for ' + dateInput.value + ':', bookedSlots);
                
                // Map time slot values to booked slot strings
                const slotMapping = {
                    'firstBatch': '8AM-9AM',
                    'secondBatch': '9AM-10AM',
                    'thirdBatch': '10AM-11AM',
                    'fourthBatch': '11AM-12PM',
                    'fifthBatch': '1PM-2PM',
                    'sixthBatch': '2PM-3PM',
                    'sevenBatch': '3PM-4PM',
                    'eightBatch': '4PM-5PM',
                    'nineBatch': '5PM-6PM',
                    'tenBatch': '6PM-7PM',
                    'lastBatch': '7PM-8PM'
                };
                
                // Disable booked slots
                const options = timeSelect.querySelectorAll('option');
                options.forEach(opt => {
                    if (opt.value === '') return; // Skip the placeholder option
                    
                    const slotTime = slotMapping[opt.value];
                    const isBooked = bookedSlots.includes(opt.value) || bookedSlots.includes(slotTime);
                    
                    opt.disabled = isBooked;
                    
                    // Update label
                    const baseLabel = opt.textContent.split(' (Booked)')[0];
                    opt.textContent = baseLabel + (isBooked ? ' (Booked)' : '');
                });
                
                // Reset time select
                timeSelect.value = '';
            })
            .catch(error => {
                console.error('Error loading booked slots:', error);
                showNotification('error', 'Error', 'Failed to load available time slots. Please try again.');
            });
    }

    function openReschedModal() {
        document.getElementById("reschedModal").style.display = "block";
    }

    function closeReschedModal() {
        document.getElementById("reschedModal").style.display = "none";
        // Reset form when closing
        const reschedForm = document.querySelector('#reschedModal form');
        if (reschedForm) {
            reschedForm.reset();
        }
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

    function editPatient(patientId) {
        
        document.getElementById('editPatientModal').style.display = 'block';
        
        fetch('../controllers/getPatients.php?patient_id=' + encodeURIComponent(patientId))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                document.getElementById('editPatientId').value = data.patient_id;
                document.getElementById('editFirstName').value = data.first_name;
                document.getElementById('editLastName').value = data.last_name;
                document.getElementById('editBirthdate').value = data.birthdate;
                document.getElementById('editGender').value = data.gender;
                document.getElementById('editEmail').value = data.email;
                document.getElementById('editPhone').value = data.phone;
                document.getElementById('editAddress').value = data.address;
            })
            .catch(error => {
                console.error('Error fetching patient:', error);
                showNotification('error', 'Error Loading Patient', error.message || 'Failed to load patient details.');
            });
    }

    function closeEditPatientModal() {
        document.getElementById('editPatientModal').style.display = 'none';
    }

    function archivePatient(patientId) {
        if (!patientId || patientId <= 0) {
            showNotification('error', 'Invalid Input', 'Invalid patient ID. Please try again.');
            return;
        }

        // Use custom confirmation with notification
        if (confirm('Are you sure you want to archive this patient? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../controllers/archivePatient.php';

            const patientIdInput = document.createElement('input');
            patientIdInput.type = 'hidden';
            patientIdInput.name = 'patient_id';
            patientIdInput.value = patientId;

            form.appendChild(patientIdInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Add Staffs Modal
    async function populateAdminUsers() {
    try {
        console.log('Fetching admin users...');
        const response = await fetch('../controllers/getadminUsers.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is actually JSON
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('Expected JSON but got:', text);
            throw new Error('Server returned non-JSON response. Check PHP errors.');
        }
        
        const adminUsers = await response.json();
        console.log('Admin users received:', adminUsers);
        
        // Check if response contains an error
        if (adminUsers && adminUsers.error) {
            throw new Error(adminUsers.error);
        }
        
        const userSelect = document.getElementById('userid');
        
        if (!userSelect) {
            console.error('userid select element not found');
            return;
        }
        
        // Clear existing options
        userSelect.innerHTML = '<option value="">Select User ID</option>';
        
        // Check if we have admin users
        if (!adminUsers || adminUsers.length === 0) {
            const option = document.createElement('option');
            option.value = "";
            option.textContent = "No admin users found";
            option.disabled = true;
            userSelect.appendChild(option);
            console.warn('No admin users found in database');
            return;
        }
        
        // Populate dropdown with admin users
        adminUsers.forEach(user => {
            // Get user_id - check all possible field names and ensure it's not null/undefined
            const userId = user.user_id || user.userID || user.id || null;
            
            // Skip if user_id is still null/undefined
            if (!userId) {
                console.warn('Skipping user with no user_id:', user);
                return;
            }
            
            const option = document.createElement('option');
            option.value = String(userId); // Ensure it's a string
            option.textContent = String(userId); // Display user_id
            option.setAttribute('data-firstname', user.first_name || '');
            option.setAttribute('data-lastname', user.last_name || '');
            option.setAttribute('data-email', user.email || '');
            option.setAttribute('data-phone', user.phone || '');
            userSelect.appendChild(option);
        });
        
        console.log(`Successfully loaded ${adminUsers.length} admin user(s)`);
        
    } catch (error) {
        console.error('Error fetching admin users:', error);
        console.error('Error stack:', error.stack);
        showNotification('error', 'Error Loading Data', error.message || 'Failed to load user data.');
        
        // Show error in dropdown
        const userSelect = document.getElementById('userid');
        if (userSelect) {
            userSelect.innerHTML = '<option value="">Error loading users</option>';
        }
    }
}

    // Function to handle user selection change
    function handleUserSelection() {
        const userSelect = document.getElementById('userid');
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        
        if (selectedOption.value && selectedOption.value !== "") {
            document.getElementById('addFirstName').value = selectedOption.getAttribute('data-firstname') || '';
            document.getElementById('addLastName').value = selectedOption.getAttribute('data-lastname') || '';
            document.getElementById('addEmail').value = selectedOption.getAttribute('data-email') || '';
            document.getElementById('addPhone').value = selectedOption.getAttribute('data-phone') || '';
        } else {
            // Clear fields if no user selected
            document.getElementById('addFirstName').value = '';
            document.getElementById('addLastName').value = '';
            document.getElementById('addEmail').value = '';
            document.getElementById('addPhone').value = '';
        }
    }

    // Modal open/close functionality
    const openDentistBtn = document.getElementById('openAddDentistBtn');
    const dentistModal = document.getElementById('addDentistModal');

    if (openDentistBtn && dentistModal) {
        openDentistBtn.addEventListener('click', function() {
            dentistModal.style.display = 'block';
            populateAdminUsers(); // Populate when modal opens
        });
    }

    function closeDentistModal() {
        if (dentistModal) {
            dentistModal.style.display = 'none';
        }
        
        // Reset form when closing
        const userSelect = document.getElementById('userid');
        if (userSelect) userSelect.selectedIndex = 0;
        
        const addFirstName = document.getElementById('addFirstName');
        const addLastName = document.getElementById('addLastName');
        const addEmail = document.getElementById('addEmail');
        const addPhone = document.getElementById('addPhone');
        const addSpecialization = document.getElementById('addSpecialization');
        const addStatus = document.getElementById('addStatus');
        
        if (addFirstName) addFirstName.value = '';
        if (addLastName) addLastName.value = '';
        if (addEmail) addEmail.value = '';
        if (addPhone) addPhone.value = '';
        if (addSpecialization) addSpecialization.value = '';
        if (addStatus) addStatus.selectedIndex = 0;
    }

    // Initialize event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const userSelect = document.getElementById('userid');
        if (userSelect) {
            // Remove any existing event listeners and add new one
            userSelect.removeEventListener('change', handleUserSelection);
            userSelect.addEventListener('change', handleUserSelection);
        }
        
        // Close modal when clicking outside
        if (dentistModal) {
            dentistModal.addEventListener('click', function(event) {
                if (event.target === dentistModal) {
                    closeDentistModal();
                }
            });
        }
    });
    
    //For edit Staffs
    function editDentist(teamId) {
        console.log('Edit dentist:', teamId);
        
        // Fetch staff details via AJAX
        fetch('../controllers/getStaff.php?team_id=' + encodeURIComponent(teamId))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const staff = data.data;
                    
                    // Populate form fields
                    document.getElementById('editDentistId').value = staff.team_id;
                    document.getElementById('editDentistUserId').value = staff.user_id || '';
                    document.getElementById('editDentistFirstName').value = staff.first_name || '';
                    document.getElementById('editDentistLastName').value = staff.last_name || '';
                    document.getElementById('editDentistSpecialization').value = staff.specialization || '';
                    document.getElementById('editDentistEmail').value = staff.email || '';
                    document.getElementById('editDentistPhone').value = staff.phone || '';
                    document.getElementById('editDentistStatus').value = staff.status || 'active';
                    
                    // Show the modal
                    document.getElementById('editDentistModal').style.display = 'block';
                } else {
                    showNotification('error', 'Error Loading Staff', data.message || 'Unknown error occurred.');
                }
            })
            .catch(error => {
                console.error('Error fetching staff:', error);
                showNotification('error', 'Error Loading Staff', error.message || 'Failed to load staff details.');
            });
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
    let currentWeekStart = getMondayOf(new Date());
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Initialize schedule
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure we start at the current week
        currentWeekStart = getMondayOf(new Date());
        updateWeekDisplay();
        loadBlockedSlots();
        generateMonthlyCalendar();
        
        // Load schedule data when dentist is selected
        const dentistSelect = document.getElementById('dentistSelectSchedule');
        if (dentistSelect) {
            dentistSelect.addEventListener('change', function() {
                loadScheduleData();
                generateMonthlyCalendar(); // Reload monthly view too
            });
        }
    });

    function changeScheduleView() {
        const viewType = document.getElementById('viewType').value;
        document.getElementById('weeklyView').style.display = viewType === 'weekly' ? 'block' : 'none';
        document.getElementById('monthlyView').style.display = viewType === 'monthly' ? 'block' : 'none';
        
        // Reload data when switching views
        if (viewType === 'monthly') {
            generateMonthlyCalendar();
        } else {
            loadScheduleData();
        }
    }

    // Ensure currentWeekStart is the Monday of the current week
    function getMondayOf(date) {
        const d = new Date(date);
        const day = d.getDay(); // 0 (Sun) .. 6 (Sat)
        const diffToMonday = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diffToMonday);
        d.setHours(0,0,0,0);
        return d;
    }

    function changeWeek(direction) {
        const newWeekStart = new Date(currentWeekStart);
        newWeekStart.setDate(newWeekStart.getDate() + (direction * 7));
        
        // Get the Monday of the current week (this week)
        const thisWeekMonday = getMondayOf(new Date());
        
        // Prevent going to previous weeks (only allow current week and future weeks)
        if (newWeekStart < thisWeekMonday) {
            return; // Don't allow going to past weeks
        }
        
        currentWeekStart = newWeekStart;
        updateWeekDisplay();
        updateWeekNavigationButtons();
        
        // Reload schedule data after updating week display
        setTimeout(() => {
            loadScheduleData();
        }, 100);
    }
    
    function updateWeekNavigationButtons() {
        const prevBtn = document.getElementById('prevWeekBtn');
        const nextBtn = document.getElementById('nextWeekBtn');
        
        if (!prevBtn || !nextBtn) return;
        
        // Get the Monday of the current week (this week)
        const thisWeekMonday = getMondayOf(new Date());
        
        // Disable Previous Week button if we're at the current week
        if (currentWeekStart.getTime() === thisWeekMonday.getTime()) {
            prevBtn.disabled = true;
            prevBtn.style.opacity = '0.5';
            prevBtn.style.cursor = 'not-allowed';
        } else {
            prevBtn.disabled = false;
            prevBtn.style.opacity = '1';
            prevBtn.style.cursor = 'pointer';
        }
        
        // Next Week button is always enabled (can always go to future weeks)
        nextBtn.disabled = false;
        nextBtn.style.opacity = '1';
        nextBtn.style.cursor = 'pointer';
    }


    function updateWeekDisplay() {
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 5); // Monday to Saturday
        const options = { month: 'short', day: 'numeric' };
        const startStr = currentWeekStart.toLocaleDateString('en-US', options);
        const endStr = weekEnd.toLocaleDateString('en-US', options);
        document.getElementById('currentWeekRange').textContent = `Week of ${startStr} - ${endStr}`;

        // ALSO update the individual day headers and time-slot data-date attributes
        updateDayHeadersAndCells();
        
        // Update navigation buttons state
        updateWeekNavigationButtons();
    }

    function updateDayHeadersAndCells() {
        // Update day-date elements under .day-header (assumes there are 6 columns Mon..Sat)
        const dayDateEls = document.querySelectorAll('.time-slots-header .day-header .day-date');
        for (let i = 0; i < dayDateEls.length; i++) {
            const d = new Date(currentWeekStart);
            d.setDate(currentWeekStart.getDate() + i);
            dayDateEls[i].textContent = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); // "Nov 10"
        }

        // Update each .time-slot-cell[data-slot] to carry the correct data-date (Y-m-d)
        const cells = document.querySelectorAll('.time-slot-cell');
        cells.forEach(cell => {
            const slotIndexAttr = cell.getAttribute('data-col-index'); // optional helper if present
            // Find which column index this cell belongs to:
            // If you already output cells in column order (inside for loop), you can read existing data-slot and compute index by counting siblings.
            // We'll compute using the cell's position among its .time-slot-row parent children:
            const parentRow = cell.parentElement; // .time-slot-row
            if (!parentRow) return;
            // Get nodeList of cells in this row
            const rowCells = Array.from(parentRow.querySelectorAll('.time-slot-cell'));
            const colIndex = rowCells.indexOf(cell); // 0..5 (Mon..Sat)
            if (colIndex >= 0) {
            const dateForCell = new Date(currentWeekStart);
            dateForCell.setDate(currentWeekStart.getDate() + colIndex);
            const yyyy = dateForCell.getFullYear();
            const mm = String(dateForCell.getMonth() + 1).padStart(2, '0');
            const dd = String(dateForCell.getDate()).padStart(2, '0');
            const isoDate = `${yyyy}-${mm}-${dd}`;
            cell.setAttribute('data-date', isoDate);

            // If the inner slot-status element shows text with a date or depends on it, you can also update it here.
            }
        });
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
        // Reload schedule data after month change
        setTimeout(() => {
            loadScheduleData();
        }, 100);
    }

    function generateMonthlyCalendar() {
        const calendar = document.getElementById('monthlyCalendar');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const dentistId = document.getElementById('dentistSelectSchedule').value;
        
        document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const startingDay = firstDay.getDay();
        
        // Load schedule data for the month
        loadMonthlyScheduleData(dentistId, firstDay, lastDay).then(scheduleData => {
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
                const dateStr = date.toISOString().split('T')[0];
                const isToday = new Date().toDateString() === date.toDateString();
                const dayClass = isToday ? 'calendar-day today' : 'calendar-day';
                
                // Get counts for this date
                const dayData = scheduleData[dateStr] || { blocked: 0, booked: 0, available: 0 };
                const totalSlots = 11; // Total time slots per day
                const available = totalSlots - dayData.blocked - dayData.booked;
                
                calendarHTML += `
                    <div class="${dayClass}" data-date="${dateStr}">
                        <div class="calendar-day-header">${day}</div>
                        <div class="day-slots">
                            <div><span class="slot-indicator available"></span> ${available} available</div>
                            <div><span class="slot-indicator blocked"></span> ${dayData.blocked} blocked</div>
                            <div><span class="slot-indicator booked"></span> ${dayData.booked} booked</div>
                        </div>
                    </div>
                `;
            }
            
            calendar.innerHTML = calendarHTML;
        });
    }
    
    async function loadMonthlyScheduleData(dentistId, firstDay, lastDay) {
        if (!dentistId) {
            return {};
        }
        
        const scheduleData = {};
        
        try {
            // Load blocked slots
            const blockedResponse = await fetch('../controllers/get_blocked_slots.php');
            const blockedSlots = await blockedResponse.json();
            
            // Load appointments (we need to get appointments for the dentist)
            // For now, we'll use a simplified approach - get all appointments
            const startDate = firstDay.toISOString().split('T')[0];
            const endDate = lastDay.toISOString().split('T')[0];
            
            // Initialize all dates in the month
            for (let d = new Date(firstDay); d <= lastDay; d.setDate(d.getDate() + 1)) {
                const dateStr = d.toISOString().split('T')[0];
                scheduleData[dateStr] = { blocked: 0, booked: 0, available: 0 };
            }
            
            // Count blocked slots per date for selected dentist
            blockedSlots.forEach(slot => {
                if (slot.dentist_id === dentistId && slot.date >= startDate && slot.date <= endDate) {
                    if (!scheduleData[slot.date]) {
                        scheduleData[slot.date] = { blocked: 0, booked: 0, available: 0 };
                    }
                    scheduleData[slot.date].blocked++;
                }
            });
            
            // Count booked appointments per date
            // We'll need to fetch appointments - let's create a simple fetch
            // For now, we'll use a placeholder - you may need to create an endpoint for this
            // Or we can fetch appointments one by one (not efficient but works)
            const appointmentPromises = [];
            for (let d = new Date(firstDay); d <= lastDay; d.setDate(d.getDate() + 1)) {
                const dateStr = d.toISOString().split('T')[0];
                appointmentPromises.push(
                    fetch(`../controllers/getAppointmentsAdmin.php?appointment_date=${dateStr}&dentist_id=${dentistId}`)
                        .then(res => res.json())
                        .then(slots => {
                            if (!scheduleData[dateStr]) {
                                scheduleData[dateStr] = { blocked: 0, booked: 0, available: 0 };
                            }
                            scheduleData[dateStr].booked = slots.length;
                        })
                        .catch(() => {
                            // If endpoint doesn't exist or fails, just continue
                        })
                );
            }
            
            await Promise.all(appointmentPromises);
            
        } catch (error) {
            console.error('Error loading monthly schedule data:', error);
        }
        
        return scheduleData;
    }

    function toggleTimeSlot(element, date, slot) {
        // Check if slot is disabled (booked)
        if (element.classList.contains('booked') || element.style.cursor === 'not-allowed') {
            alert('This slot is already booked and cannot be modified.');
            return;
        }
        
        const currentStatus = element.classList.contains('available') ? 'available' : 
                            element.classList.contains('blocked') ? 'blocked' : 'booked';
        
        if (currentStatus === 'booked') {
            alert('This slot is already booked and cannot be modified.');
            return;
        }
        
        const newStatus = currentStatus === 'available' ? 'blocked' : 'available';
        
        // If blocking, prompt for reason
        if (newStatus === 'blocked') {
            const reason = prompt('Please provide a reason for blocking this time slot:', 'Blocked by admin');
            if (reason === null) {
                // User cancelled
                return;
            }
            if (reason.trim() === '') {
                alert('Reason is required to block a time slot.');
                return;
            }
            
            // Update UI immediately
            element.className = `slot-status ${newStatus}`;
            element.innerHTML = '<i class="fas fa-times-circle"></i><span>Blocked</span>';
            
            // Send AJAX request to update database
            updateTimeSlotStatus(date, slot, newStatus, reason.trim());
        } else {
            // Unblocking
            if (!confirm('Are you sure you want to unblock this time slot?')) {
                return;
            }
            
            // Update UI immediately
            element.className = `slot-status ${newStatus}`;
            element.innerHTML = '<i class="fas fa-check-circle"></i><span>Available</span>';
            
            // Send AJAX request to update database
            updateTimeSlotStatus(date, slot, newStatus);
        }
    }

    function updateTimeSlotStatus(date, slot, status, reason = '') {
        const dentistId = document.getElementById('dentistSelectSchedule').value;
        
        if (!dentistId) {
            alert('Please select a dentist first.');
            // Revert UI
            const element = document.querySelector(`[data-date="${date}"][data-slot="${slot}"] .slot-status`);
            if (element) {
                const currentStatus = status === 'blocked' ? 'available' : 'blocked';
                element.className = `slot-status ${currentStatus}`;
                element.innerHTML = currentStatus === 'available' ? 
                    '<i class="fas fa-check-circle"></i><span>Available</span>' :
                    '<i class="fas fa-times-circle"></i><span>Blocked</span>';
            }
            return;
        }
        
        const requestData = {
            dentist_id: dentistId,
            date: date,
            time_slot: slot,
            status: status,
            action: 'update_slot'
        };
        
        if (reason) {
            requestData.reason = reason;
        }
        
        fetch('../controllers/update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                showNotification('success', 'Success', data.message || 'Time slot updated successfully.');
                // Reload blocked slots list and schedule display
                loadBlockedSlots();
                loadScheduleData();
                // Also regenerate monthly calendar if in monthly view
                const monthlyView = document.getElementById('monthlyView');
                if (monthlyView && monthlyView.style.display !== 'none') {
                    generateMonthlyCalendar();
                }
            } else {
                showNotification('error', 'Error', data.message || 'Failed to update time slot.');
                // Revert UI change
                const element = document.querySelector(`[data-date="${date}"][data-slot="${slot}"] .slot-status`);
                if (element) {
                    const revertStatus = status === 'blocked' ? 'available' : 'blocked';
                    element.className = `slot-status ${revertStatus}`;
                    element.innerHTML = revertStatus === 'available' ? 
                        '<i class="fas fa-check-circle"></i><span>Available</span>' :
                        '<i class="fas fa-times-circle"></i><span>Blocked</span>';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while updating the time slot. Please try again.');
            // Revert UI change
            const element = document.querySelector(`[data-date="${date}"][data-slot="${slot}"] .slot-status`);
            if (element) {
                const revertStatus = status === 'blocked' ? 'available' : 'blocked';
                element.className = `slot-status ${revertStatus}`;
                element.innerHTML = revertStatus === 'available' ? 
                    '<i class="fas fa-check-circle"></i><span>Available</span>' :
                    '<i class="fas fa-times-circle"></i><span>Blocked</span>';
            }
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
        fetch('../controllers/get_blocked_slots.php')
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
        
        fetch('../controllers/update_schedule.php', {
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
                showNotification('success', 'Success', 'Time slot unblocked successfully.');
                loadBlockedSlots();
                loadScheduleData();
                // Also regenerate monthly calendar if in monthly view
                const monthlyView = document.getElementById('monthlyView');
                if (monthlyView && monthlyView.style.display !== 'none') {
                    generateMonthlyCalendar();
                }
            } else {
                showNotification('error', 'Error', data.message || 'Failed to unblock time slot.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while unblocking the time slot. Please try again.');
        });
    }

    function loadScheduleData() {
        const dentistId = document.getElementById('dentistSelectSchedule').value;
        if (!dentistId) return;
        
        // Get all date cells in the current week view
        const dateCells = document.querySelectorAll('.time-slot-cell');
        const dates = new Set();
        dateCells.forEach(cell => {
            const date = cell.getAttribute('data-date');
            if (date) dates.add(date);
        });
        
        // Load blocked slots and appointments
        Promise.all([
            fetch('../controllers/get_blocked_slots.php').then(res => res.json()),
            Promise.all(Array.from(dates).map(date => 
                fetch(`../controllers/getAppointmentsAdmin.php?appointment_date=${date}&dentist_id=${dentistId}`)
                    .then(res => res.json())
                    .then(slots => ({ date, slots }))
                    .catch(() => ({ date, slots: [] }))
            ))
        ])
        .then(([blockedSlots, appointmentData]) => {
            // Create a map of appointments by date and time slot
            const appointmentsByDate = {};
            appointmentData.forEach(({ date, slots }) => {
                appointmentsByDate[date] = new Set(slots);
            });
            
            // Reset all slots to available first and re-enable onclick
            document.querySelectorAll('.slot-status').forEach(slot => {
                slot.className = 'slot-status available';
                slot.innerHTML = '<i class="fas fa-check-circle"></i><span>Available</span>';
                // Re-enable onclick by restoring the onclick attribute
                const cell = slot.closest('.time-slot-cell');
                if (cell) {
                    const date = cell.getAttribute('data-date');
                    const slotKey = cell.getAttribute('data-slot');
                    slot.setAttribute('onclick', `toggleTimeSlot(this, '${date}', '${slotKey}')`);
                    slot.style.cursor = 'pointer';
                    slot.style.opacity = '1';
                }
            });
            
            // Mark booked slots first (they take priority)
            Object.keys(appointmentsByDate).forEach(date => {
                const bookedSlots = appointmentsByDate[date];
                bookedSlots.forEach(timeSlot => {
                    const cell = document.querySelector(`[data-date="${date}"][data-slot="${timeSlot}"]`);
                    if (cell) {
                        const statusElement = cell.querySelector('.slot-status');
                        if (statusElement) {
                            statusElement.className = 'slot-status booked';
                            statusElement.innerHTML = '<i class="fas fa-calendar-check"></i><span>Booked</span>';
                            // Remove onclick and disable interaction
                            statusElement.removeAttribute('onclick');
                            statusElement.style.cursor = 'not-allowed';
                            statusElement.style.opacity = '0.7';
                        }
                    }
                });
            });
            
            // Mark blocked slots (but don't override booked slots)
            blockedSlots.forEach(slot => {
                if (slot.dentist_id === dentistId) {
                    const cell = document.querySelector(`[data-date="${slot.date}"][data-slot="${slot.time_slot}"]`);
                    if (cell) {
                        const statusElement = cell.querySelector('.slot-status');
                        // Only mark as blocked if it's not already booked
                        if (statusElement && !statusElement.classList.contains('booked')) {
                            statusElement.className = 'slot-status blocked';
                            statusElement.innerHTML = '<i class="fas fa-times-circle"></i><span>Blocked</span>';
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading schedule data:', error);
        });
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
        if (event.target === document.getElementById('blockDayModal')) {
            closeBlockDayModal();
        }
        if (event.target === document.getElementById('holidayModal')) {
            closeHolidayModal();
        }
        if (event.target === document.getElementById('emergencyClosureModal')) {
            closeEmergencyClosureModal();
        }
    });

    // ==================== CLINIC CLOSURE MANAGEMENT ====================
    
    // Block Entire Day Modal Functions
    function openBlockDayModal() {
        document.getElementById('blockDayModal').style.display = 'block';
    }
    
    function closeBlockDayModal() {
        document.getElementById('blockDayModal').style.display = 'none';
        document.getElementById('blockDayForm').reset();
        const customReasonContainer = document.getElementById('blockDayCustomReasonContainer');
        if (customReasonContainer) {
            customReasonContainer.style.display = 'none';
        }
    }
    
    // Handle block day form submission
    function handleBlockDaySubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const closureDate = formData.get('closure_date');
        const closureType = formData.get('closure_type');
        let reason = formData.get('reason');
        const customReason = formData.get('custom_reason');
        const notifyPatients = formData.get('notify_patients') === 'on';
        
        // Use custom reason if "Other" is selected
        if (reason === 'Other' && customReason) {
            reason = customReason;
        }
        
        if (!reason || reason.trim() === '') {
            showNotification('error', 'Error', 'Please provide a reason for the closure.');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Create request data
        const requestData = {
            action: 'block_day',
            date: closureDate,
            closure_type: closureType,
            reason: reason,
            custom_reason: customReason || '',
            notify_patients: notifyPatients
        };
        
        fetch('manage_clinic_closure.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Day Blocked Successfully', `Date ${closureDate} has been blocked. ${notifyPatients ? 'Patients have been notified.' : ''}`);
                closeBlockDayModal();
                loadClinicClosures();
                loadScheduleData(); // Reload schedule to reflect changes
            } else {
                showNotification('error', 'Error', data.message || 'Failed to block day. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while blocking the day. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Show custom reason field when "Other" is selected
    document.addEventListener('DOMContentLoaded', function() {
        const reasonSelect = document.getElementById('blockDayReason');
        const customReasonContainer = document.getElementById('blockDayCustomReasonContainer');
        const customReasonTextarea = document.getElementById('blockDayCustomReason');
        if (reasonSelect && customReasonContainer && customReasonTextarea) {
            reasonSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    customReasonContainer.style.display = 'block';
                    customReasonTextarea.setAttribute('required', 'required');
                } else {
                    customReasonContainer.style.display = 'none';
                    customReasonTextarea.removeAttribute('required');
                    customReasonTextarea.value = '';
                }
            });
        }
        
        // Handle closure duration radio buttons for emergency closure
        const closureDurationRadios = document.querySelectorAll('input[name="closure_duration"]');
        const endDateContainer = document.getElementById('emergencyEndDateContainer');
        if (closureDurationRadios.length > 0 && endDateContainer) {
            closureDurationRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'date_range') {
                        endDateContainer.style.display = 'block';
                        document.getElementById('emergencyEndDate').setAttribute('required', 'required');
                    } else {
                        endDateContainer.style.display = 'none';
                        document.getElementById('emergencyEndDate').removeAttribute('required');
                        document.getElementById('emergencyEndDate').value = '';
                    }
                });
            });
        }
    });
    
    // Holiday Management Modal Functions
    function openHolidayModal() {
        document.getElementById('holidayModal').style.display = 'block';
        loadHolidays();
    }
    
    function closeHolidayModal() {
        document.getElementById('holidayModal').style.display = 'none';
        hideAddHolidayForm();
    }
    
    function showAddHolidayForm() {
        document.getElementById('addHolidayForm').style.display = 'block';
    }
    
    function hideAddHolidayForm() {
        document.getElementById('addHolidayForm').style.display = 'none';
        document.getElementById('holidayForm').reset();
    }
    
    // Handle holiday form submission
    function handleHolidaySubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        
        const requestData = {
            action: 'add_holiday',
            holiday_name: formData.get('holiday_name'),
            holiday_date: formData.get('holiday_date'),
            recurrence: formData.get('recurrence')
        };
        
        fetch('manage_clinic_closure.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Holiday Added', `Holiday "${requestData.holiday_name}" has been added.`);
                hideAddHolidayForm();
                loadHolidays();
                loadScheduleData();
            } else {
                showNotification('error', 'Error', data.message || 'Failed to add holiday. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while adding holiday. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Load holidays list
    function loadHolidays() {
        fetch('../controllers/get_holidays.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('holidaysTableBody');
            if (!tbody) return;
            
            if (data.success && data.holidays && data.holidays.length > 0) {
                tbody.innerHTML = '';
                data.holidays.forEach(holiday => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="padding: 12px;">${holiday.holiday_name}</td>
                        <td style="padding: 12px;">${holiday.holiday_date}</td>
                        <td style="padding: 12px;">${holiday.recurrence === 'yearly' ? 'Yearly (Recurring)' : 'One Time'}</td>
                        <td style="padding: 12px; text-align: center;">
                            <button class="action-btn btn-danger" onclick="deleteHoliday(${holiday.id})" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No holidays found. Add one to get started.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading holidays:', error);
        });
    }
    
    // Delete holiday
    function deleteHoliday(holidayId) {
        if (!confirm('Are you sure you want to delete this holiday?')) {
            return;
        }
        
        fetch('manage_clinic_closure.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_holiday',
                holiday_id: holidayId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Holiday Deleted', 'Holiday has been deleted successfully.');
                loadHolidays();
                loadScheduleData();
            } else {
                showNotification('error', 'Error', data.message || 'Failed to delete holiday.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while deleting holiday.');
        });
    }
    
    // Emergency Closure Modal Functions
    function openEmergencyClosureModal() {
        document.getElementById('emergencyClosureModal').style.display = 'block';
    }
    
    function closeEmergencyClosureModal() {
        document.getElementById('emergencyClosureModal').style.display = 'none';
        document.getElementById('emergencyClosureForm').reset();
        document.getElementById('emergencyEndDateContainer').style.display = 'none';
    }
    
    // Handle emergency closure form submission
    function handleEmergencyClosureSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date');
        const closureDuration = formData.get('closure_duration');
        const reason = formData.get('reason');
        const notifyPatients = formData.get('notify_patients') === 'on';
        
        if (!confirm('⚠️ WARNING: This will cancel all appointments during the closure period. Are you absolutely sure you want to proceed?')) {
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Emergency Closure...';
        
        const requestData = {
            action: 'emergency_closure',
            start_date: startDate,
            end_date: closureDuration === 'date_range' ? endDate : startDate,
            reason: reason,
            notify_patients: notifyPatients
        };
        
        fetch('manage_clinic_closure.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('warning', 'Emergency Closure Activated', `Clinic closed from ${startDate} to ${requestData.end_date}. ${data.cancelled_count || 0} appointments cancelled. ${notifyPatients ? 'Patients have been notified.' : ''}`);
                closeEmergencyClosureModal();
                loadClinicClosures();
                loadScheduleData();
            } else {
                showNotification('error', 'Error', data.message || 'Failed to process emergency closure. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while processing emergency closure. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Load clinic closures list
    function loadClinicClosures() {
        fetch('../controllers/get_clinic_closures.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('clinicClosureList');
            if (!container) return;
            
            if (data.success && data.closures && data.closures.length > 0) {
                let html = '<h4 style="margin-bottom: 15px;">Active Closures:</h4>';
                html += '<div style="display: grid; gap: 10px;">';
                data.closures.forEach(closure => {
                    const closureTypeBadge = closure.closure_type === 'full_day' ? 
                        '<span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Full Day</span>' :
                        '<span style="background: #ffc107; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px;">No New Appointments</span>';
                    
                    html += `
                        <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${closure.date}</strong> - ${closure.reason}
                                ${closureTypeBadge}
                            </div>
                            <button class="btn btn-sm btn-secondary" onclick="removeClosure('${closure.date}')" title="Remove Closure">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p style="color: #6c757d; margin: 0;">No active closures.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading clinic closures:', error);
        });
    }
    
    // Remove closure
    function removeClosure(date) {
        if (!confirm(`Are you sure you want to remove the closure for ${date}?`)) {
            return;
        }
        
        fetch('manage_clinic_closure.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove_closure',
                date: date
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Closure Removed', `Closure for ${date} has been removed.`);
                loadClinicClosures();
                loadScheduleData();
            } else {
                showNotification('error', 'Error', data.message || 'Failed to remove closure.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while removing closure.');
        });
    }
    
    // General clinic closure modal (placeholder - can be expanded)
    // ==================== END CLINIC CLOSURE MANAGEMENT ====================
</script>
</body>
</html>