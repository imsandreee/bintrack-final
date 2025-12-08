<?php
require '../auth/config.php';

// Total Users
$users = supabase_fetch('profiles', '?select=id');
$total_users = count($users);

// Total Bins
$bins = supabase_fetch('bins', '?select=id');
$total_bins = count($bins);

// Active Alerts
$active_alerts = supabase_fetch('bin_alerts', '?select=id&resolved=eq.false');
$total_alerts = count($active_alerts);

// Collected Today (sum weight_collected_kg)
$today = date('Y-m-d');
$collected_today = supabase_fetch('collection_logs', "?select=weight_collected_kg&collected_at=gte.$today");
$total_collected = 0;
foreach ($collected_today as $log) {
    $total_collected += floatval($log['weight_collected_kg']);
}

// --- Waste Collected Trends (Monthly) ---
$currentYear = date('Y');
$monthlyData = array_fill(1, 12, 0); // Initialize Jan-Dec with 0

$collection_logs = supabase_fetch('collection_logs', "?select=collected_at,weight_collected_kg");
foreach ($collection_logs as $log) {
    $date = new DateTime($log['collected_at']);
    if ($date->format('Y') == $currentYear) {
        $month = (int)$date->format('n'); // 1-12
        $monthlyData[$month] += floatval($log['weight_collected_kg']);
    }
}

// --- Bins by Status ---
$bins = supabase_fetch('bins', '?select=status');
$binStatusCounts = ['active'=>0, 'inactive'=>0, 'maintenance'=>0];
foreach ($bins as $bin) {
    $status = $bin['status'];
    if(isset($binStatusCounts[$status])){
        $binStatusCounts[$status]++;
    }
}

// --- Recent Alerts (Latest 5) ---
$alerts = supabase_fetch('bin_alerts', '?select=id,bin_id,alert_type,message,created_at,resolved&order=created_at.desc&limit=5');
// Convert bin_id to bin_code
foreach ($alerts as &$alert) {
    $bin = supabase_fetch('bins', "?select=bin_code&id=eq.".$alert['bin_id']);
    $alert['bin_code'] = $bin[0]['bin_code'] ?? 'N/A';
}
unset($alert);

// --- Recent Collections (Latest 5) ---
$collections = supabase_fetch('collection_logs', '?select=collected_at,weight_collected_kg,bin_id,collector_id&order=collected_at.desc&limit=5');
foreach ($collections as &$c) {
    $bin = supabase_fetch('bins', "?select=bin_code&id=eq.".$c['bin_id']);
    $collector = supabase_fetch('profiles', "?select=full_name&id=eq.".$c['collector_id']);
    $c['bin_code'] = $bin[0]['bin_code'] ?? 'N/A';
    $c['collector_name'] = $collector[0]['full_name'] ?? 'N/A';
}
unset($c);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha2d1-Zcm7NMoRofWdD7R+A8S7iU0T7YV+vU8sWJ2hU0K7mP8g0T3Z0P8G0Y9z9E7VwL+8vQ"
        crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex" id="wrapper">

     <?php
    include '../includes/admin/sidebar.php'; // Includes the content of header.php
    ?>

    
    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper" class="w-100">

            <?php
        include '../includes/admin/topnavbar.php'; // Includes the content of header.php
        ?>


        <!-- Main Content Area (Pages) -->
        <div class="container-fluid py-4 main-content">

            <!-- 1. Dashboard Page -->
            <div id="dashboard" class="page-content">
                <h1 class="mb-4">Dashboard Overview</h1>

                <!-- Stats Cards -->
                <div class="row g-4 mb-5">
    <!-- Total Users -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people-fill text-success display-6 me-3"></i>
                                <div>
                                    <p class="text-uppercase fw-bold text-muted mb-1">Total Users</p>
                                    <h2 class="mb-0"><?= $total_users ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Bins -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-trash-fill text-success display-6 me-3"></i>
                                <div>
                                    <p class="text-uppercase fw-bold text-muted mb-1">Total Bins</p>
                                    <h2 class="mb-0"><?= $total_bins ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Alerts -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill text-danger display-6 me-3"></i>
                                <div>
                                    <p class="text-uppercase fw-bold text-muted mb-1">Active Alerts</p>
                                    <h2 class="mb-0"><?= $total_alerts ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collected Today -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-handbag-fill text-info display-6 me-3"></i>
                                <div>
                                    <p class="text-uppercase fw-bold text-muted mb-1">Collected Today</p>
                                    <h2 class="mb-0"><?= number_format($total_collected, 2) ?> kg</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Charts / Graphs -->
                <div class="row g-4 mb-5">
                    <div class="col-lg-8">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <h5 class="card-title">Waste Collected Trends (Monthly)</h5>
                            <canvas id="wasteTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card p-4 shadow-sm h-100 border-0">
                            <h5 class="card-title">Bins by Status</h5>
                            <canvas id="binsStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Tables -->
                <div class="row g-4">
    <!-- Recent Alerts -->
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm h-100 border-0">
            <h5 class="card-title">Recent Alerts</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Bin</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td><?= htmlspecialchars($alert['bin_code']) ?></td>
                            <td>
                                <?php 
                                    $color = match($alert['alert_type']) {
                                        'full', 'nearly_full', 'overload' => 'bg-danger',
                                        'offline', 'sensor_error' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $color ?>"><?= ucfirst(str_replace('_',' ',$alert['alert_type'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($alert['message']) ?></td>
                            <td><?= date('M d, H:i', strtotime($alert['created_at'])) ?></td>
                            <td>
                                <?php if($alert['resolved']): ?>
                                    <span class="badge bg-success">Resolved</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unresolved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Collections -->
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm h-100 border-0">
            <h5 class="card-title">Recent Collections</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Collector</th>
                            <th>Bin</th>
                            <th>Weight (kg)</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collections as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['collector_name']) ?></td>
                            <td><?= htmlspecialchars($log['bin_code']) ?></td>
                            <td><?= number_format($log['weight_collected_kg'], 1) ?></td>
                            <td><?= date('H:i A', strtotime($log['collected_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


            
<script>
    // --- Waste Collected Trends ---
    const wasteTrendCtx = document.getElementById('wasteTrendChart').getContext('2d');
    const wasteTrendChart = new Chart(wasteTrendCtx, {
        type: 'line',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Waste Collected (kg)',
                data: <?= json_encode(array_values($monthlyData)) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // --- Bins by Status ---
    const binsStatusCtx = document.getElementById('binsStatusChart').getContext('2d');
    const binsStatusChart = new Chart(binsStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active','Inactive','Maintenance'],
            datasets: [{
                label: 'Bins Status',
                data: <?= json_encode(array_values($binStatusCounts)) ?>,
                backgroundColor: ['#198754','#6c757d','#ffc107'],
                borderColor: ['#198754','#6c757d','#ffc107'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

            <!-- /Dashboard Page -->

           
          



<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Leaflet JS for Map functionality -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha2d1-s6uq4b2x2dE0Q2pL+B6gV+CgM6y8T1xL5ZgX8qT5z8Qz9T4P7z7Z7z7W7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G+E0w=="
    crossorigin=""></script>



</body>
</html>