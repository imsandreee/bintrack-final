<?php
session_start();

// Only allow citizen role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

// FIX: Load configuration and Supabase API functions
// require_once '../auth/config.php'; 
// require_once '../auth/supabase_utils.php'; 

$user = $_SESSION['user'];
// Assume user profile contains a location/area ID to filter bins
$user_area_id = $user['area_id'] ?? 'default_area_uuid'; 


// --- SIMULATED DATA FETCHING ---
// Replace this block with actual supabase_fetch() calls later.
$local_stats = [
    'total_bins' => 12,
    'almost_full_count' => 3,
    'critical_alerts_count' => 1,
    'nearest_bin' => [
        'id' => 'BIN-007',
        'location' => '123 Oak Street, City Park Entrance',
        'fill_level' => 78, // %
        'weight_kg' => 8.2,
        'last_collected_at' => '2 days ago'
    ],
    'next_collection' => [
        'route_name' => 'Main Street Route',
        'time' => 'Tomorrow, 9:00 AM',
        'status' => 'Scheduled'
    ],
    'last_reported' => 'In Progress' // Status of the citizen's last report
];
// --- END SIMULATED DATA ---


$total_bins = $local_stats['total_bins'];
$almost_full_count = $local_stats['almost_full_count'];
$critical_alerts_count = $local_stats['critical_alerts_count'];
$nearest_bin = $local_stats['nearest_bin'];
$next_collection = $local_stats['next_collection'];
$last_reported = $local_stats['last_reported'];

// Determine progress bar color based on fill level
$fill_level = $nearest_bin['fill_level'];
$progress_color = 'bg-success';
$fill_status_badge = 'bg-success';
$fill_status_text = 'Low';

if ($fill_level >= 85) {
    $progress_color = 'bg-danger';
    $fill_status_badge = 'bg-danger';
    $fill_status_text = 'Full/Critical';
} elseif ($fill_level >= 70) {
    $progress_color = 'bg-warning';
    $fill_status_badge = 'bg-warning text-dark';
    $fill_status_text = 'Nearly Full';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

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
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Total Bins Nearby</h5>
                                <p class="h3 fw-bold text-dark-green mb-0"><?= $total_bins ?></p>
                            </div>
                            <div class="icon"><i class="bi bi-pin-map"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card" style="border-left-color: #ffc107;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Almost Full</h5>
                                <p class="h3 fw-bold text-warning mb-0"><?= $almost_full_count ?></p>
                            </div>
                            <div class="icon" style="color: #ffc107;"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card" style="border-left-color: #dc3545;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Bins with Alerts</h5>
                                <p class="h3 fw-bold text-danger mb-0"><?= $critical_alerts_count ?></p>
                            </div>
                            <div class="icon" style="color: #dc3545;"><i class="bi bi-bell"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="indicator-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-muted mb-0 small text-uppercase">Nearest Bin Status</h5>
                                <p class="h3 fw-bold text-green mb-0"><?= $nearest_bin['fill_level'] ?>% Full</p>
                            </div>
                            <div class="icon"><i class="bi bi-check2-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="custom-card p-4">
                        <h5 class="fw-bold mb-3">Nearest Smart Bin Status (Bin ID: #<?= $nearest_bin['id'] ?>)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div id="nearestBinMap" class="ratio ratio-16x9 rounded-3 bg-light-green border text-center d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0" id="mapMessage">
                                        Loading map...
                                    </p>
                                </div>
                                <small class="text-muted mt-2 d-block">Location: <?= htmlspecialchars($nearest_bin['location']) ?></small>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-semibold mb-1">Fill Level: <span class="badge <?= $fill_status_badge ?>"><?= $fill_level ?>% (<?= $fill_status_text ?>)</span></p>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div 
                                        class="progress-bar progress-bar-fill <?= $progress_color ?>" 
                                        role="progressbar" 
                                        style="width: <?= $fill_level ?>%" 
                                        aria-valuenow="<?= $fill_level ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                                
                                <p class="fw-semibold mb-1">Weight: <span class="text-dark"><?= $nearest_bin['weight_kg'] ?> kg</span></p>
                                <p class="fw-semibold mb-1">Last Collection: <span class="text-muted"><?= $nearest_bin['last_collected_at'] ?></span></p>

                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-primary" onclick="loadPage('binList')">
                                        <i class="bi bi-binoculars me-2"></i> View All Bins
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="loadPage('reportForm')">
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
                                    <small class="text-muted"><?= $next_collection['route_name'] ?></small>
                                </div>
                                <span class="badge bg-info text-dark"><?= $next_collection['time'] ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="me-auto">
                                    <p class="mb-0 fw-semibold">Your Last Report</p>
                                    <small class="text-muted">Status</small>
                                </div>
                                <span class="badge bg-primary"><?= $last_reported ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="me-auto">
                                    <p class="mb-0 fw-semibold">Service Change Alert</p>
                                    <small class="text-muted">Holiday delay notice</small>
                                </div>
                                <span class="badge bg-danger">NEW</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20n6a4s3cfXZ6B5sdgss5A60Fh78w5895E4D1tI/pC7o="
        crossorigin=""></script>
        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Expose nearest bin coordinates for Leaflet
    // Since we're using simulated data, let's use a simulated location in Manila
    const NEAREST_BIN_LAT = 14.6190;
    const NEAREST_BIN_LNG = 121.0180;
    const NEAREST_BIN_ID = '<?= $nearest_bin['id'] ?>';

    /**
     * Initializes the Leaflet map for the nearest bin.
     */
    function initializeMap() {
        const mapContainer = document.getElementById('nearestBinMap');
        mapContainer.innerHTML = ''; // Clear the placeholder message
        
        let map = L.map('nearestBinMap', {
            zoomControl: false // Hide default zoom controls for a small dashboard map
        }).setView([NEAREST_BIN_LAT, NEAREST_BIN_LNG], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add a marker for the nearest bin
        L.marker([NEAREST_BIN_LAT, NEAREST_BIN_LNG])
            .bindPopup(`<b>Bin #${NEAREST_BIN_ID}</b><br>Fill Level: ${<?= $fill_level ?>}%`)
            .addTo(map);

        // Crucial for map rendering in small containers/after page load
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }

    /**
     * Placeholder function for navigation buttons (View All Bins, Report Issue).
     * In a full application, this would switch content areas or redirect.
     */
    function loadPage(pageKey) {
        if (pageKey === 'reportForm') {
            alert("Action: Directing to the Report Issue Form (Reported Bin ID: " + NEAREST_BIN_ID + ")");
        } else if (pageKey === 'binList') {
            alert("Action: Directing to the full Bin List page for your area.");
        }
    }
    
    document.addEventListener('DOMContentLoaded', initializeMap);
    </script>
</body>
</html>