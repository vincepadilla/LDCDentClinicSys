<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    header("Location: login.php");
    exit("You must be logged in to view this page.");
}

define("TITLE", "Payment");
include_once('../database/config.php');

$fname = $lname = $birthdate = $age = $email = $gender = $phone = '';
$address = $service_id = $subService = $branch = $date = $time = '';
$price = 500;

$dentist = 'Dr. Michelle Landero';

$timeRanges = [
    'firstBatch' => '8:00AM-9:00AM',
    'secondBatch' => '9:00AM-10:00AM',
    'thirdBatch' => '10:00AM-11:00AM',
    'fourthBatch' => '11:00AM-12:00PM',
    'fifthBatch' => '1:00PM-2:00PM',
    'sixthBatch' => '2:00PM-3:00PM',
    'sevenBatch' => '3:00PM-4:00PM',
    'eightBatch' => '4:00PM-5:00PM',
    'nineBatch' => '5:00PM-6:00PM',
    'tenBatch' => '6:00PM-7:00PM',
    'lastBatch' => '7:00PM-8:00PM'
];

// Override with POST values if form submitted (coming from index.php modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $fname = htmlspecialchars($_POST['fname'] ?? $fname);
    $lname = htmlspecialchars($_POST['lname'] ?? $lname);
    $birthdate = htmlspecialchars($_POST['birthdate'] ?? $birthdate);
    $age = htmlspecialchars($_POST['age'] ?? $age);
    $email = htmlspecialchars($_POST['email'] ?? $email);
    $gender = htmlspecialchars($_POST['gender'] ?? $gender);
    $phone = htmlspecialchars($_POST['phone'] ?? $phone);
    $address = htmlspecialchars($_POST['address'] ?? $address);

    $subService = htmlspecialchars($_POST['sub_service'] ?? $subService);

    // Map subService to service_id
    switch ($subService) {
        // General Dentistry
        case 'Checkups':                     $service_id = 'S001'; break;
        case 'Oral Prophylaxis (Cleaning)':  $service_id = 'S1001'; break;
        case 'Fluoride Application':         $service_id = 'S1002'; break;
        case 'Pit & Fissure Sealants':       $service_id = 'S1003'; break;
        case 'Tooth Restoration (Pasta)':    $service_id = 'S1004'; break;
        // Orthodontics
        case 'Braces':                       $service_id = 'S002'; break;
        case 'Retainers':                    $service_id = 'S2001'; break;
        // Oral Surgery
        case 'Tooth Extraction (Bunot)':     $service_id = 'S003'; break;
        // Endodontics
        case 'Root Canal Treatment':         $service_id = 'S004'; break;
        // Prosthodontics
        case 'Crowns':                       $service_id = 'S005'; break;
        case 'Dentures':                     $service_id = 'S5001'; break;
        default: $service_id = 'N/A'; break;
    }

    // Map service_id to category name
    switch ($service_id) {
        case 'S001':
        case 'S1001':
        case 'S1002':
        case 'S1003':
        case 'S1004':
            $service_name = 'General Dentistry';
            break;

        case 'S002':
        case 'S2001':
            $service_name = 'Orthodontics';
            break;

        case 'S003':
            $service_name = 'Oral Surgery';
            break;

        case 'S004':
            $service_name = 'Endodontics';
            break;

        case 'S005':
        case 'S5001':
            $service_name = 'Prosthodontics Treatments (Pustiso)';
            break;

        default:
            $service_name = 'Unknown Service';
            break;
    }

    $branch = htmlspecialchars($_POST['branch'] ?? $branch);

    // Format branch names
    if (strtolower($branch) === 'comembo') {
        $branch = 'Comembo Branch';
    } elseif (strtolower($branch) === 'taytay') {
        $branch = 'Taytay Rizal Branch';
    }

    // If user clicked Reserve Appointment (walk-in), do NOT save to DB.
    if (!empty($_POST['reserve_walkin'])) {
        $_SESSION['walkin_appointment'] = [
            'created_at' => date('Y-m-d H:i:s'),
            'first_name' => $fname,
            'last_name' => $lname,
            'age' => $age,
            'gender' => $gender,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'service_id' => $service_id,
            'service_name' => $service_name ?? 'Walk-In Service',
            'sub_service' => $subService,
            'branch' => $branch,
            'dentist' => $dentist,
            'payment_method' => 'Cash',
        ];

        header("Location: account.php?walkin=1");
        exit();
    }
}

// Calculate age (fallback)
if (!empty($birthdate) && $birthdate !== 'N/A') {
    try {
        $birthDateObj = new DateTime($birthdate);
        $todayObj = new DateTime();
        $age = $todayObj->diff($birthDateObj)->y;
    } catch (Exception $e) {
        // ignore
    }
}
?>

<?php include_once('../layouts/header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm & Pay - SmileCare Dental</title>
    <link rel="stylesheet" href="../assets/css/paymentstyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hover tooltip for calendar times */
        .cal-tooltip {
            position: fixed;
            z-index: 99999;
            max-width: 320px;
            background: rgba(17, 24, 39, 0.95);
            color: #fff;
            border-radius: 10px;
            padding: 12px 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
            font-size: 12px;
            line-height: 1.35;
            pointer-events: none;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 120ms ease, transform 120ms ease;
        }
        .cal-tooltip.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .cal-tooltip .tt-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .cal-tooltip .tt-row {
            margin: 6px 0;
        }
        .cal-tooltip .tt-label {
            font-weight: 700;
            display: inline-block;
            margin-right: 6px;
        }
        .cal-tooltip ul {
            margin: 6px 0 0 0;
            padding-left: 18px;
        }
        .cal-tooltip li {
            margin: 2px 0;
        }
        .cal-tooltip .tt-none {
            color: rgba(255,255,255,0.75);
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="header-section">
        <h1>Walk-In Appointment Reservation</h1>
        <p>Review your details and check the clinic's schedule before reserving.</p>
    </div>

    <form id="paymentForm" action="walkin.php" method="POST">
        <div class="content-grid">
            <!-- Appointment Summary -->
            <div class="summary-section">
                <div class="section-header">
                    <h2>Appointment Summary</h2>
                    <p>Please verify your appointment details.</p>
                </div>

                <div class="info-section">
                    <h3 class="section-title">A. Patient Information</h3>
                    <div class="patient-details">
                        <div class="patient-row">
                            <div class="patient-label">Full Name:</div>
                            <div class="patient-value"><?= strtoupper("$fname $lname") ?></div>
                        </div>
                        <div class="patient-row">
                            <div class="patient-label">Age:</div>
                            <div class="patient-value"><?= $age ?></div>
                        </div>
                        <div class="patient-row">
                            <div class="patient-label">Gender:</div>
                            <div class="patient-value"><?= strtoupper($gender) ?></div>
                            <input type="hidden" name="address" value="<?= $address ?>">
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title">C. Appointment Details</h3>
                    <div class="appointment-details">
                        <div class="detail-row">
                            <div class="detail-label">Service</div>
                            <div class="detail-value"><?= ucwords($service_name) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Sub-Service</div>
                            <div class="detail-value"><?= ucwords($subService) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Dentist</div>
                            <div class="detail-value"><?= strtoupper($dentist) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Branch</div>
                            <div class="detail-value"><?= strtoupper($branch) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="fname" value="<?= $fname ?>">
                <input type="hidden" name="lname" value="<?= $lname ?>">
                <input type="hidden" name="age" value="<?= $age ?>">
                <input type="hidden" name="birthdate" value="<?= $birthdate ?>">
                <input type="hidden" name="gender" value="<?= $gender ?>">
                <input type="hidden" name="email" value="<?= $email ?>">
                <input type="hidden" name="phone" value="<?= $phone ?>">
                <input type="hidden" name="street" value="<?= $address ?>">
                <input type="hidden" name="service_id" value="<?= $service_id ?>">
                <input type="hidden" name="subService" value="<?= $subService ?>">
                <input type="hidden" name="subservice_id" value="<?= $subservice_id ?? '' ?>">
                <input type="hidden" name="dentist" value="<?= $dentist ?>">
                <input type="hidden" name="branch" value="<?= $branch ?>">
                <!-- For walk-in flow, date and time are chosen at the clinic, so they are not required here -->
            </div>

            <!-- Doctor Availability (replaces Payment Information for walk-in) -->
            <div class="payment-section">
                <div class="section-header">
                    <h2>Doctor's Availability</h2>
                    <p>Select a day that works for you. Final date and time will be arranged at the clinic.</p>
                </div>

                <div class="payment-method-section">
                    <h3 class="section-title">Clinic Calendar</h3>
                    <div id="doctorAvailabilityCalendar" class="availability-calendar"></div>
                    <p style="margin-top: 15px; color: #555; font-size: 0.9rem;">
                        This calendar shows general clinic operating days. Exact appointment date and time for walk-in payment
                        will be finalized with the receptionist during your visit.
                    </p>

                    <!-- Walk-in payments are always treated as Cash in the backend -->
                    <input type="hidden" name="paymentMethod" value="Cash">
                    <input type="hidden" name="reserve_walkin" value="1">

                    <div class="payment-form" style="margin-top: 20px;">
                        <button type="submit" class="pay-button" id="cashPayBtn">Reserve Appointment</button>
                    </div>
                </div>
                
                <div class="fee-notice">
                    <p><strong>Consultation Fee:</strong> ₱500.00</p>
                    <p>This appointment fee will be deducted from the total payment.</p>
                </div>
            </div>
        </div>

        <!-- Hidden IDs -->
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>">
        <input type="hidden" name="appointment_id" value="<?= $appointment_id ?? '' ?>">
    </form>
</div>

<?php include_once('../layouts/footer.php'); ?>

<script>
// Doctor / clinic availability calendar based on admin time-slot scheduling
(function() {
    const calendarEl = document.getElementById('doctorAvailabilityCalendar');
    if (!calendarEl) return;

    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    const totalSlotsPerDay = 11; // should match admin time-slot configuration

    const timeSlotKeys = ['firstBatch','secondBatch','thirdBatch','fourthBatch','fifthBatch','sixthBatch','sevenBatch','eightBatch','nineBatch','tenBatch','lastBatch'];
    const timeSlotLabels = {
        firstBatch:  '8:00AM-9:00AM',
        secondBatch: '9:00AM-10:00AM',
        thirdBatch:  '10:00AM-11:00AM',
        fourthBatch: '11:00AM-12:00PM',
        fifthBatch:  '1:00PM-2:00PM',
        sixthBatch:  '2:00PM-3:00PM',
        sevenBatch:  '3:00PM-4:00PM',
        eightBatch:  '4:00PM-5:00PM',
        nineBatch:   '5:00PM-6:00PM',
        tenBatch:    '6:00PM-7:00PM',
        lastBatch:   '7:00PM-8:00PM'
    };

    // Helper to format dates in local time as YYYY-MM-DD (avoids timezone shift issues)
    function formatLocalDate(dateObj) {
        const yyyy = dateObj.getFullYear();
        const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
        const dd = String(dateObj.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    let currentMonth = (new Date()).getMonth();
    let currentYear  = (new Date()).getFullYear();

    // Tooltip element
    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'cal-tooltip';
    document.body.appendChild(tooltipEl);

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function uniqueValidKeys(arr) {
        const s = new Set();
        (arr || []).forEach(k => {
            if (timeSlotLabels[k]) s.add(k);
        });
        // keep consistent order
        return timeSlotKeys.filter(k => s.has(k));
    }

    function buildTooltipHtml(dateStr, dayData) {
        const bookedKeys = uniqueValidKeys(dayData?.bookedSlots || []);
        const blockedKeys = uniqueValidKeys(dayData?.blockedSlots || []);
        const availableKeys = timeSlotKeys.filter(k => !bookedKeys.includes(k) && !blockedKeys.includes(k));

        function listHtml(keys) {
            if (!keys.length) return `<div class="tt-none">None</div>`;
            return `<ul>${keys.map(k => `<li>${escapeHtml(timeSlotLabels[k])}</li>`).join('')}</ul>`;
        }

        return `
            <div class="tt-title">${escapeHtml(dateStr)}</div>
            <div class="tt-row"><span class="tt-label">Available (${availableKeys.length}):</span>${listHtml(availableKeys)}</div>
            <div class="tt-row"><span class="tt-label">Booked (${bookedKeys.length}):</span>${listHtml(bookedKeys)}</div>
            <div class="tt-row"><span class="tt-label">Blocked (${blockedKeys.length}):</span>${listHtml(blockedKeys)}</div>
        `;
    }

    function positionTooltip(anchorRect) {
        const padding = 12;
        const maxW = Math.min(340, window.innerWidth - padding * 2);
        tooltipEl.style.maxWidth = maxW + 'px';

        const ttRect = tooltipEl.getBoundingClientRect();
        let left = anchorRect.left + (anchorRect.width / 2) - (ttRect.width / 2);
        let top  = anchorRect.top - ttRect.height - 10;

        // If not enough space above, place below
        if (top < padding) top = anchorRect.bottom + 10;

        // Clamp to viewport
        left = Math.max(padding, Math.min(left, window.innerWidth - ttRect.width - padding));
        top  = Math.max(padding, Math.min(top, window.innerHeight - ttRect.height - padding));

        tooltipEl.style.left = left + 'px';
        tooltipEl.style.top  = top + 'px';
    }

    async function loadMonthlyData(firstDay, lastDay) {
        const scheduleData = {};

        try {
            // 1) Load all blocked slots (for all dentists)
            const blockedResponse = await fetch('../controllers/get_blocked_slots.php');
            const blockedSlots = await blockedResponse.json();

            // 2) Pre-initialize all dates in range (using local date, not UTC)
            for (let d = new Date(firstDay); d <= lastDay; d.setDate(d.getDate() + 1)) {
                const dateStr = formatLocalDate(d);
                scheduleData[dateStr] = { blockedSlots: [], bookedSlots: [] };
            }

            // 3) Collect blocked slots per date (dedupe later for display)
            blockedSlots.forEach(slot => {
                const date = slot.date;
                if (scheduleData[date]) {
                    scheduleData[date].blockedSlots.push(slot.time_slot);
                }
            });

            // 4) Load appointments per day (no dentist filter = whole clinic)
            const appointmentPromises = [];
            for (let d = new Date(firstDay); d <= lastDay; d.setDate(d.getDate() + 1)) {
                const dateStr = formatLocalDate(d);
                appointmentPromises.push(
                    fetch(`../controllers/getAppointmentsAdmin.php?appointment_date=${dateStr}`)
                        .then(res => res.ok ? res.json() : [])
                        .then(slots => {
                            if (!Array.isArray(slots)) return;
                            if (!scheduleData[dateStr]) {
                                scheduleData[dateStr] = { blockedSlots: [], bookedSlots: [] };
                            }
                            scheduleData[dateStr].bookedSlots = slots;
                        })
                        .catch(() => {
                            // Fail silently for this date
                        })
                );
            }

            await Promise.all(appointmentPromises);
        } catch (err) {
            console.error('Error loading monthly availability data:', err);
        }

        return scheduleData;
    }

    async function renderCalendar() {
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay  = new Date(currentYear, currentMonth + 1, 0);

        const year  = currentYear;
        const month = currentMonth;

        const startingDay = firstDay.getDay(); // 0 = Sun

        // Fetch availability data for this month
        const scheduleData = await loadMonthlyData(firstDay, lastDay);

        let html = '<div class="availability-header">';
        html += '<button type="button" class="cal-nav prev">&lt;</button>';
        html += `<span class="cal-month">${monthNames[month]} ${year}</span>`;
        html += '<button type="button" class="cal-nav next">&gt;</button>';
        html += '</div>';

        html += '<div class="cal-grid">';
        const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        weekdays.forEach(d => {
            html += `<div class="cal-cell cal-head">${d}</div>`;
        });

        // Empty cells before first day
        for (let i = 0; i < startingDay; i++) {
            html += '<div class="cal-cell cal-empty"></div>';
        }

        const today = new Date();

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const date = new Date(year, month, day);
            const dateStr = formatLocalDate(date);
            const isToday = date.toDateString() === today.toDateString();

            const dayData = scheduleData[dateStr] || { blockedSlots: [], bookedSlots: [] };
            const bookedKeys = uniqueValidKeys(dayData.bookedSlots);
            const blockedKeys = uniqueValidKeys(dayData.blockedSlots);
            const availableKeys = timeSlotKeys.filter(k => !bookedKeys.includes(k) && !blockedKeys.includes(k));
            let available = availableKeys.length;
            let bookedCount = bookedKeys.length;
            let blockedCount = blockedKeys.length;

            let statusClass = 'status-available';
            let statusLabel = 'Plenty of slots';

            if (available === 0) {
                statusClass = 'status-full';
                statusLabel = 'Fully booked';
            } else if (bookedCount > 0 || blockedCount > 0) {
                statusClass = 'status-limited';
                statusLabel = 'Limited slots';
            }

            const classes = ['cal-cell', 'cal-day', statusClass];
            if (isToday) classes.push('cal-today');

            html += `
                <div class="${classes.join(' ')}" data-date="${dateStr}">
                    <div class="day-number">${day}</div>
                    <div class="day-status-text">${statusLabel}</div>
                    <div class="day-status-bars">
                        <span class="bar available-bar" title="Available">${available}</span>
                        <span class="bar booked-bar" title="Booked">${bookedCount}</span>
                        <span class="bar blocked-bar" title="Blocked">${blockedCount}</span>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        html += '<div class="cal-legend">';
        html += '<span class="legend-item"><span class="legend-dot legend-available"></span> Available</span>';
        html += '<span class="legend-item"><span class="legend-dot legend-limited"></span> Limited</span>';
        html += '<span class="legend-item"><span class="legend-dot legend-full"></span> Fully booked / blocked</span>';
        html += '</div>';

        calendarEl.innerHTML = html;

        const prevBtn = calendarEl.querySelector('.cal-nav.prev');
        const nextBtn = calendarEl.querySelector('.cal-nav.next');
        if (prevBtn) prevBtn.onclick = () => { changeMonth(-1); };
        if (nextBtn) nextBtn.onclick = () => { changeMonth(1); };

        // Hover tooltip events (show times)
        const dayCells = calendarEl.querySelectorAll('.cal-day[data-date]');
        dayCells.forEach(cell => {
            cell.addEventListener('mouseenter', () => {
                const dateStr = cell.getAttribute('data-date');
                const dayData = scheduleData[dateStr] || { blockedSlots: [], bookedSlots: [] };
                tooltipEl.innerHTML = buildTooltipHtml(dateStr, dayData);
                tooltipEl.classList.add('visible');
                // position after content set
                positionTooltip(cell.getBoundingClientRect());
            });
            cell.addEventListener('mouseleave', () => {
                tooltipEl.classList.remove('visible');
            });
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
        renderCalendar();
    }

    // Initial render
    renderCalendar();
})();
</script>

</body>
</html>