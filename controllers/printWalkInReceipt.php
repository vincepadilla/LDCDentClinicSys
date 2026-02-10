<?php
session_start();

// Must be logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../views/login.php");
    exit();
}

$walkIn = $_SESSION['walkin_appointment'] ?? null;
if (empty($walkIn) || !is_array($walkIn)) {
    echo "<script>alert('No walk-in reservation found to print.'); window.close();</script>";
    exit();
}

$fullName = trim(($walkIn['first_name'] ?? '') . ' ' . ($walkIn['last_name'] ?? ''));
$service = $walkIn['sub_service'] ?? ($walkIn['service_name'] ?? 'Walk-In Service');
$branch  = $walkIn['branch'] ?? 'N/A';
$dentist = $walkIn['dentist'] ?? 'N/A';
$created = $walkIn['created_at'] ?? date('Y-m-d H:i:s');
$payment = $walkIn['payment_method'] ?? 'Cash';
$fee     = '₱500.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Walk-In Appointment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111827; }
        .receipt { max-width: 720px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
        .header { display:flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
        .clinic { font-weight: 700; font-size: 18px; }
        .meta { text-align: right; font-size: 12px; color: #6b7280; }
        .title { font-size: 16px; font-weight: 700; margin: 10px 0 16px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; }
        .item { border: 1px solid #f3f4f6; border-radius: 10px; padding: 12px; background: #fafafa; }
        .label { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
        .value { font-size: 14px; font-weight: 600; }
        .note { margin-top: 16px; padding: 12px; border-left: 4px solid #10b981; background: #ecfdf5; border-radius: 8px; color: #065f46; }
        .actions { margin-top: 18px; display:flex; gap: 10px; }
        button { padding: 10px 14px; border: 0; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-print { background: #111827; color: #fff; }
        .btn-close { background: #e5e7eb; color: #111827; }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
            .receipt { border: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div>
                <div class="clinic">SmileCare Dental Clinic</div>
                <div style="font-size:12px; color:#6b7280;">Walk-In Appointment Receipt</div>
            </div>
            <div class="meta">
                <div><strong>Status:</strong> Confirmed (Walk-In)</div>
                <div><strong>Issued:</strong> <?= htmlspecialchars($created); ?></div>
            </div>
        </div>

        <div class="title">Reservation Details</div>
        <div class="grid">
            <div class="item">
                <div class="label">Patient</div>
                <div class="value"><?= htmlspecialchars($fullName ?: 'N/A'); ?></div>
            </div>
            <div class="item">
                <div class="label">Branch</div>
                <div class="value"><?= htmlspecialchars($branch); ?></div>
            </div>
            <div class="item">
                <div class="label">Service</div>
                <div class="value"><?= htmlspecialchars($service); ?></div>
            </div>
            <div class="item">
                <div class="label">Dentist</div>
                <div class="value"><?= htmlspecialchars($dentist); ?></div>
            </div>
            <div class="item">
                <div class="label">Schedule</div>
                <div class="value">To be arranged at clinic</div>
            </div>
            <div class="item">
                <div class="label">Payment Method</div>
                <div class="value"><?= htmlspecialchars($payment); ?></div>
            </div>
            <div class="item">
                <div class="label">Consultation Fee</div>
                <div class="value"><?= htmlspecialchars($fee); ?></div>
            </div>
            <div class="item">
                <div class="label">Reference</div>
                <div class="value">WALK-IN</div>
            </div>
        </div>

        <div class="note">
            Please present this receipt at the clinic reception. Final appointment date and time will be arranged on-site.
        </div>

        <div class="actions">
            <button class="btn-print" onclick="window.print()">Print</button>
            <button class="btn-close" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>

