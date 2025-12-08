<?php
include '../auth/config.php';
session_start();

// Only allow citizen
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id']; // Fix undefined variable

$report_id = $_GET['report_id'] ?? null;
if (!$report_id) {
    die("Report ID not specified.");
}

// Fetch the report belonging only to this user
$report_data = supabase_fetch(
    "citizen_reports",
    "?select=id,issue_type,description,image_url,status,created_at,updated_at,admin_remarks,bins(bin_code,location_name)&id=eq.$report_id&user_id=eq.$user_id"
);

// Check if report exists
if (!is_array($report_data) || count($report_data) === 0) {
    die("Report not found or you do not have permission to view it.");
}

// Get the report safely
$report = $report_data[0];

// Bin info (adjust according to your supabase relationship)
$bin = $report['bins'] ?? ['bin_code'=>'N/A','location_name'=>'Unknown'];

// Image URL
$image_url = $report['image_url'] ?? null;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report #R-<?= $report['id'] ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>
<?php include '../includes/citizen/navbar.php'; ?>

<div class="container-xl py-4">
    <section id="viewReportDetailsPage" class="page-content">
        <h1 class="mb-4 text-dark-green fw-bold">Report Details <span>#R-<?= $report['id'] ?></span></h1>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="custom-card p-4 h-100">
                    <h5 class="fw-bold mb-3">Report Overview</h5>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Report ID:</span>
                            <span class="text-primary">#R-<?= $report['id'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Issue Category:</span>
                            <span><?= ucfirst(str_replace('_',' ',$report['issue_type'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Reported Bin:</span>
                            <span><?= htmlspecialchars($bin['bin_code']) ?> - <?= htmlspecialchars($bin['location_name']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Date Submitted:</span>
                            <span><?= date('F j, Y, h:i A', strtotime($report['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Current Status:</span>
                            <span class="badge 
                                <?= match($report['status']) {
                                    'pending' => 'bg-secondary',
                                    'in_progress' => 'bg-warning text-dark',
                                    'resolved' => 'bg-success',
                                    'invalid' => 'bg-danger',
                                    default => 'bg-secondary',
                                } ?>
                            "><?= ucfirst(str_replace('_',' ',$report['status'])) ?></span>
                        </li>
                    </ul>

                    <h5 class="fw-bold mt-4 mb-3">Description & Evidence</h5>
                    <blockquote class="blockquote border-start border-3 ps-3 text-muted">
                        <?= htmlspecialchars($report['description']) ?>
                    </blockquote>

                    <p class="fw-semibold mt-4">Photo Evidence (Optional):</p>
                    <div class="border rounded-3 p-3 bg-light-green text-center">
                       <?php if ($image_url): ?>
    <img src="../<?= htmlspecialchars($image_url) ?>" class="img-fluid rounded mb-3" alt="Report Image">
<?php else: ?>
    <div class="border rounded-3 p-3 bg-light-green text-center">
        <i class="bi bi-camera-fill fs-4 text-muted"></i>
        <p class="mb-0 text-muted small">No photo evidence provided.</p>
    </div>
<?php endif; ?>

                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="custom-card p-4 h-100">
                    <h5 class="fw-bold mb-3">Municipal Response / Timeline</h5>
                    <div class="list-group">
                        <!-- Here you can fetch and display status changes or staff notes if you have a timeline table -->
                        <div class="list-group-item">
                            <small class="text-muted d-block">
                                <?= date('F j, Y, h:i A', strtotime($report['updated_at'])) ?>
                            </small>

                            <p class="mb-1 fw-semibold text-info">
                                Status: <?= ucfirst(str_replace('_',' ',$report['status'])) ?>
                            </p>

                            <small class="text-muted d-block">
                                <?= !empty($report['admin_remarks']) 
                                    ? htmlspecialchars($report['admin_remarks']) 
                                    : 'No official response yet. Please check back later.' 
                                ?>
                            </small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
