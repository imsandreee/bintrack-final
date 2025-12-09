<?php
session_start();

// Only allow citizen role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

// FIX: Load configuration and Supabase API functions
require_once '../auth/config.php'; 
require_once '../auth/supabase_utils.php'; 

$user = $_SESSION['user'];
// CRITICAL FIX: Assuming 'area_id' is fetched from the user's profile upon login,
// or defaulted to a value representing their assigned area (e.g., a city/barangay UUID).
$user_area_id = $user['area_id'] ?? '00000000-0000-0000-0000-000000000000'; 
$user_id = $user['id']; // Get the user's UUID from the session

// Define a constant for fill level calculation. This should match the bin specs.
$MAX_BIN_HEIGHT_CM = 100; // Assuming 100cm total height for calculation

// --- ACTUAL DATA FETCHING (REPLACING SIMULATED DATA) ---

// 1. Total, Almost Full, and Critical Alerts Counts
// NOTE: These queries assume the 'bins' table has an 'area_id' column for filtering.

// Total Bins nearby
$total_bins_data = supabase_raw_sql("SELECT COUNT(*) as count FROM bins WHERE area_id = ?", [$user_area_id]);
$total_bins = $total_bins_data['count'] ?? 0;

// Almost Full Alerts (Alert type 'nearly_full' and unresolved)
$almost_full_data = supabase_raw_sql("
    SELECT COUNT(*) as count 
    FROM bin_alerts 
    WHERE alert_type = 'nearly_full' 
      AND resolved = FALSE 
      AND bin_id IN (SELECT id FROM bins WHERE area_id = ?)
", [$user_area_id]);
$almost_full_count = $almost_full_data['count'] ?? 0;

// Critical Alerts (Alert types 'full', 'sensor_error', 'overload' and unresolved)
$critical_alerts_data = supabase_raw_sql("
    SELECT COUNT(*) as count 
    FROM bin_alerts 
    WHERE alert_type IN ('full', 'sensor_error', 'overload') 
      AND resolved = FALSE 
      AND bin_id IN (SELECT id FROM bins WHERE area_id = ?)
", [$user_area_id]);
$critical_alerts_count = $critical_alerts_data['count'] ?? 0;


// 2. Nearest Bin Status
// A. Find the nearest bin (simplification: fetch the bin with the most recent communication in the area)
// In a real app, this would use a complex geo-query (Haversine formula) based on user location.
$nearest_bin_data_raw = supabase_fetch_one(
    "bins", 
    ['area_id' => $user_area_id], // Filter by user's area
    'last_communication DESC' // Order by most recent communication time
);
$nearest_bin_uuid = $nearest_bin_data_raw['id'] ?? null;

// Fallback if no bin is found in the area
if (!$nearest_bin_uuid) {
    // Set fallback variables to prevent errors in the HTML
    $nearest_bin = [
        'id' => 'N/A',
        'location' => 'No Bins Found Nearby',
        'fill_level' => 0, 
        'weight_kg' => 0.0,
        'last_collected_at' => 'Never',
        'lat' => 14.5995, // Default Manila coordinates
        'lng' => 120.9842,
    ];
    // Set $fill_level to 0 for the progress bar logic below
    $fill_level = 0; 
} else {
    // B. Fetch Latest Sensor Reading for the nearest bin
    $latest_reading = supabase_fetch_one(
        "sensor_readings", 
        ['bin_id' => $nearest_bin_uuid], 
        'timestamp DESC' // Get the latest reading
    );

    // C. Fetch Last Collection Log
    $last_collection = supabase_fetch_one(
        "collection_logs", 
        ['bin_id' => $nearest_bin_uuid], 
        'collected_at DESC'
    );
    
    // --- FILL LEVEL CALCULATION ---
    $distance_cm = $latest_reading['ultrasonic_distance_cm'] ?? $MAX_BIN_HEIGHT_CM; 
    
    // Fill Level % = 100 - ((Distance from sensor / Max Height) * 100)
    $fill_level = round(100 - ($distance_cm / $MAX_BIN_HEIGHT_CM) * 100) ?? 0; 
    // Ensure fill level is capped between 0 and 100
    $fill_level = max(0, min(100, $fill_level));

    $weight_kg = $latest_reading['load_cell_weight_kg'] ?? 0.0;

    $nearest_bin = [
        'id' => $nearest_bin_data_raw['bin_code'],
        'uuid' => $nearest_bin_uuid,
        'location' => $nearest_bin_data_raw['location_name'],
        'fill_level' => $fill_level, 
        'weight_kg' => $weight_kg,
        // Use time ago helper on the collection log time, or communication time if no log
        'last_collected_at' => supabase_time_ago($last_collection['collected_at'] ?? $nearest_bin_data_raw['last_communication'] ?? null),
        'lat' => $nearest_bin_data_raw['latitude'],
        'lng' => $nearest_bin_data_raw['longitude'],
    ];
}


// 3. Next Collection Schedule
// Finds the next route that includes any bin from the user's area.
// This requires fetching all bins in the area first, then checking routes against that list.

// Get all bin IDs in the user's area (requires a `supabase_fetch_all` function in utils)
// $area_bin_ids_raw = supabase_fetch_all("SELECT id FROM bins WHERE area_id = ?", [$user_area_id]);
// $area_bin_ids = array_column($area_bin_ids_raw, 'id'); 
// The query below simulates the check for the next scheduled route that affects this area:

// For a complex array comparison query (e.g., PostgreSQL `bin_ids @> ARRAY[...]::uuid[]`), 
// we use supabase_raw_sql and filter by status and order by creation time.
$next_collection_data = supabase_raw_sql("
    SELECT route_name, estimated_time, status 
    FROM collection_route 
    WHERE status = 'pending' 
    ORDER BY created_at ASC 
    LIMIT 1
");

$next_collection = [
    'route_name' => $next_collection_data['route_name'] ?? 'No Route Scheduled',
    // Use estimated_time + current time to get a future timestamp, then format
    'time' => supabase_format_time(
        isset($next_collection_data['estimated_time']) 
        ? date('Y-m-d H:i:s', strtotime("+" . $next_collection_data['estimated_time'])) 
        : null
    ) ?? 'TBD', 
    'status' => $next_collection_data['status'] ?? 'Scheduled'
];

// 4. Last Reported Status
// Get the status of the user's most recent report
$last_report_data = supabase_fetch_one(
    "citizen_reports", 
    ['user_id' => $user_id], 
    'created_at DESC' // Order by created_at descending
);
$last_reported = ucwords(str_replace('_', ' ', $last_report_data['status'] ?? 'None Reported'));

// --- END ACTUAL DATA FETCHING ---

// Determine progress bar color based on fill level
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
    const NEAREST_BIN_LAT = <?= $nearest_bin['lat'] ?>;
    const NEAREST_BIN_LNG = <?= $nearest_bin['lng'] ?>;
    const NEAREST_BIN_ID = '<?= $nearest_bin['id'] ?>';
    const NEAREST_BIN_FILL = <?= $fill_level ?>;

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
            .bindPopup(`<b>Bin #${NEAREST_BIN_ID}</b><br>Fill Level: ${NEAREST_BIN_FILL}%`)
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