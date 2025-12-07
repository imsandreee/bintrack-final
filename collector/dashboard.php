<?php
session_start();

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'collector') {
    header("Location: index.html");
    exit;
}

$user = $_SESSION['user'];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light px-4">
    <a class="navbar-brand text-success fw-bold" href="#">BinTrack</a>
    <div class="ms-auto d-flex align-items-center">
        <span class="me-3">Hello, <?= htmlspecialchars($_SESSION['full_name']); ?> (<?= htmlspecialchars($_SESSION['role']); ?>)</span>
        <a href="../auth/logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</nav>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Collector Interface</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts (Poppins for modern, clean look) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/collector.css">
        
</head>
<body>

        <?php
include '../includes/collector/navbar.php';
?>


            <div class="container-fluid p-0">

                <!-- ======================================================= -->
                <!-- 1. Collector Dashboard Page -->
                <!-- ======================================================= -->
                <section id="dashboardPage" class="page-content active-page">
                    <h1 class="mb-4 fw-bold">Dashboard Overview</h1>

                    <div class="alert bg-white custom-card p-4 mb-5 border-start border-4 border-green d-flex align-items-center">
                        <i class="bi bi-truck-flatbed fs-2 text-green me-3"></i>
                        <div>
                            <h4 class="mb-1 fw-bold">Welcome back, John Doe!</h4>
                            <p class="mb-0 text-muted">Ready to hit your routes? Here's your mission for the day.</p>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row g-4 mb-5">
                        <div class="col-sm-6 col-lg-3">
                            <div class="custom-card p-4 bg-white text-center">
                                <i class="bi bi-pin-map-fill fs-3 text-green mb-2"></i>
                                <p class="text-muted small mb-0 text-uppercase">Total Bins Assigned</p>
                                <h3 class="fw-bold mb-0">85</h3>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="custom-card p-4 bg-white text-center alert-card">
                                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger mb-2"></i>
                                <p class="text-muted small mb-0 text-uppercase">Overflow / Critical Alerts</p>
                                <h3 class="fw-bold text-danger mb-0">5</h3>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="custom-card p-4 bg-white text-center alert-card low">
                                <i class="bi bi-hourglass-split fs-3 text-warning mb-2"></i>
                                <p class="text-muted small mb-0 text-uppercase">Bins Nearly Full (75%+)</p>
                                <h3 class="fw-bold text-warning mb-0">12</h3>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="custom-card p-4 bg-white text-center">
                                <i class="bi bi-check-circle-fill fs-3 text-green mb-2"></i>
                                <p class="text-muted small mb-0 text-uppercase">Completed Collections Today</p>
                                <h3 class="fw-bold mb-0">32</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Map -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="custom-card p-4 h-100 bg-white">
                                <h5 class="fw-bold mb-3">Quick Actions</h5>
                                <div class="d-grid gap-3">
                                    <button class="btn btn-primary btn-lg" onclick="switchPage('routes', 'R002')">
                                        <i class="bi bi-play-circle-fill me-2"></i> Start Route R002 (40 Bins)
                                    </button>
                                    <button class="btn btn-outline-primary btn-lg" onclick="switchPage('alerts')">
                                        <i class="bi bi-bell-fill me-2"></i> View 5 Urgent Alerts
                                    </button>
                                    <button class="btn btn-outline-secondary btn-lg" onclick="switchPage('logs', 'add')">
                                        <i class="bi bi-plus-circle-fill me-2"></i> Manually Log Collection
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="custom-card p-4 h-100 bg-white">
                                <h5 class="fw-bold mb-3">Assigned Route Map (R002)</h5>
                                <div class="ratio ratio-16x9 bg-light-green border rounded-3 p-3 text-center d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0">
                                        [Map Placeholder]<br>
                                        Displaying Route R002 path and active bin locations.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                


            </div> <!-- End Container Fluid -->
        </main>
    </div> <!-- End App Container -->


    <!-- Custom Alert Modal Structure (replaces alert()) -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-card">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-green" id="customAlertModalLabel"><i class="bi bi-truck-flatbed me-2"></i> BinTrack Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-0">
            <p id="alertModalBody" class="lead"></p>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
      <script src="../assets/js/collector.js"></script>

</body>
</html>