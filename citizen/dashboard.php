<?php
session_start();

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user = $_SESSION['user'];
?>



<!-- HTML content for admin dashboard goes here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Dashboard</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>

    <?php
include '../includes/citizen/navbar.php';
?>


    <div class="container-xl py-4">
        <section id="dashboardPage" class="page-content active-page">
            <h1 class="mb-4 text-dark-green fw-bold">Citizen Dashboard</h1>
            
            <div class="alert bg-white custom-card p-4 mb-5 border-start border-4 border-green d-flex align-items-center justify-content-between">
                <div>
<h4 class="mb-1 fw-bold">
    Welcome back, <?= htmlspecialchars($user['full_name'] ?? 'Citizen') ?>! 👋
</h4>
                    <p class="mb-0 text-muted">A quick overview of waste collection in your neighborhood.</p>
                </div>
                <button class="btn btn-outline-secondary btn-sm d-none d-md-block">
                    <i class="bi bi-gear me-1"></i> Settings
                </button>
            </div>

            <div class="row g-4 mb-5">
                <!-- Total Bins -->
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Total Bins Nearby</h5>
                                <p class="h3 fw-bold text-dark-green mb-0">12</p>
                            </div>
                            <div class="icon"><i class="bi bi-pin-map"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Almost Full Bins -->
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card" style="border-left-color: #ffc107;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Almost Full</h5>
                                <p class="h3 fw-bold text-warning mb-0">3</p>
                            </div>
                            <div class="icon" style="color: #ffc107;"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Bins with Alerts -->
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card" style="border-left-color: #dc3545;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Bins with Alerts</h5>
                                <p class="h3 fw-bold text-danger mb-0">1</p>
                            </div>
                            <div class="icon" style="color: #dc3545;"><i class="bi bi-bell"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Recently Collected -->
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Recently Collected</h5>
                                <p class="h3 fw-bold text-green mb-0">Bin #005</p>
                            </div>
                            <div class="icon"><i class="bi bi-check2-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map and Actions Section -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="custom-card p-4">
                        <h5 class="fw-bold mb-3">Nearest Smart Bin Status (Bin ID: #007)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="ratio ratio-16x9 rounded-3 bg-light-green border p-3 text-center d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0">
                                        [Image of Map with GPS pin]<br>
                                        Placeholder: Map showing bin at 123 Oak St.
                                    </p>
                                </div>
                                <small class="text-muted mt-2 d-block">Location: 123 Oak Street, City Park Entrance</small>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-semibold mb-1">Fill Level: <span class="badge bg-warning badge-nearly-full">78% (Nearly Full)</span></p>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar progress-bar-fill bg-warning" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                
                                <p class="fw-semibold mb-1">Weight: <span class="text-dark">8.2 kg</span></p>
                                <p class="fw-semibold mb-1">Last Collection: <span class="text-muted">2 days ago</span></p>

                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-primary" onclick="switchPage('binList')">
                                        <i class="bi bi-binoculars me-2"></i> View All Bins
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="switchPage('reportForm')">
                                        <i class="bi bi-megaphone me-2"></i> Report an Issue Here
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="custom-card p-4 h-100">
                        <h5 class="fw-bold mb-3">Collection Schedule Updates</h5>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="me-auto">
                                    <p class="mb-0 fw-semibold">Next Scheduled Pickup</p>
                                    <small class="text-muted">Main Street Route</small>
                                </div>
                                <span class="badge bg-info text-dark">Tomorrow, 9:00 AM</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="me-auto">
                                    <p class="mb-0 fw-semibold">Service Change Alert</p>
                                    <small class="text-muted">Holiday delay notice</small>
                                </div>
                                <span class="badge bg-danger">NEW</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <p class="mb-0 fw-semibold me-auto">Your Last Report</p>
                                <span class="badge bg-primary">In Progress</span>
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
