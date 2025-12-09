<?php
session_start();

// Only allow collector role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'collector') {
    header("Location: index.html");
    exit;
}

// 1. Load configuration (SUPABASE_URL, SUPABASE_KEY)
require_once '../auth/config.php'; 

// 2. Load Supabase API functions
// FIX: Ensure this file contains the definitions for supabase_api_call() and supabase_fetch()
require_once '../auth/supabase_utils.php'; 

$user = $_SESSION['user'];
$collector_id = $user['id']; // Get the collector's UUID

// Initialize variables with safe defaults
$urgent_alerts_count = 0;
$nearly_full_bins_count = 0;
$completed_collections_today = 0;
$total_assigned_bins_on_route = 0;

// Default initial route variables
$initial_route = null;
// Use placeholder IDs for demonstration if no route is found, or an empty string
$initial_route_id = ''; 
$initial_route_name = 'No Route Selected';
$initial_route_status = '';

// --- 1. Fetch ALL Collector's Assigned Routes (Pending/Active) ---
// Order by status (active first) and then by creation date
$routes_query = "?driver=eq.$collector_id&status=in.(pending,active)&order=status.desc,created_at.desc&select=id,route_name,total_bins,status";
$assigned_routes = supabase_fetch('collection_route', $routes_query);

if (!empty($assigned_routes)) {
    // Set the first route as the initial route for display
    $initial_route = $assigned_routes[0];
    $initial_route_id = $initial_route['id'];
    $initial_route_name = htmlspecialchars($initial_route['route_name']);
    $initial_route_status = $initial_route['status'];
    $total_assigned_bins_on_route = $initial_route['total_bins'];
}

// --- 2. Fetch Bin Alerts (Urgent/Critical) ---
$alerts_query = "?alert_type=in.(full,overload,sensor_error)&resolved=eq.false&count=exact";
$alerts_result = supabase_api_call('GET', 'bin_alerts', [], $alerts_query);
if ($alerts_result['success'] && isset($alerts_result['data'][0]['count'])) {
    $urgent_alerts_count = $alerts_result['data'][0]['count'];
}

// --- 3. Fetch Nearly Full Bins ---
$nearly_full_query = "?alert_type=eq.nearly_full&resolved=eq.false&count=exact";
$nearly_full_result = supabase_api_call('GET', 'bin_alerts', [], $nearly_full_query);
if ($nearly_full_result['success'] && isset($nearly_full_result['data'][0]['count'])) {
    $nearly_full_bins_count = $nearly_full_result['data'][0]['count'];
}

// --- 4. Fetch Completed Collections Today (Personal Stats) ---
$today = date('Y-m-d');
$logs_query = "?collector_id=eq.$collector_id&collected_at=gte.$today&count=exact";
$logs_result = supabase_api_call('GET', 'collection_logs', [], $logs_query);
if ($logs_result['success'] && isset($logs_result['data'][0]['count'])) {
    $completed_collections_today = $logs_result['data'][0]['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Collector Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/collector.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""/>
</head>
<body>

    <?php include '../includes/collector/navbar.php'; ?>

    <main class="content-wrapper p-4">
        <div class="container-fluid p-0">

            <section id="dashboardPage" class="page-content active-page">
                <h1 class="mb-4 fw-bold">Dashboard Overview</h1>

                <div class="alert bg-white custom-card p-4 mb-5 border-start border-4 border-green d-flex align-items-center">
                    <i class="bi bi-truck-flatbed fs-2 text-green me-3"></i>
                    <div>
                        <h4 class="mb-1 fw-bold">
                            Welcome back, <?= htmlspecialchars($user['full_name'] ?? 'Collector') ?>!
                        </h4>
                        <p class="mb-0 text-muted">
                            Ready to hit your routes? Here's your mission for the day.
                        </p>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-sm-6 col-lg-3">
                        <div class="custom-card p-4 bg-white text-center">
                            <i class="bi bi-pin-map-fill fs-3 text-green mb-2"></i>
                            <p class="text-muted small mb-0 text-uppercase">Bins in Selected Route</p>
                            <h3 class="fw-bold mb-0" id="selectedRouteBinCount"><?= $total_assigned_bins_on_route ?></h3>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="custom-card p-4 bg-white text-center alert-card">
                            <i class="bi bi-exclamation-triangle-fill fs-3 text-danger mb-2"></i>
                            <p class="text-muted small mb-0 text-uppercase">Overflow / Critical Alerts</p>
                            <h3 class="fw-bold text-danger mb-0"><?= $urgent_alerts_count ?></h3>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="custom-card p-4 bg-white text-center alert-card low">
                            <i class="bi bi-hourglass-split fs-3 text-warning mb-2"></i>
                            <p class="text-muted small mb-0 text-uppercase">Bins Nearly Full (Warning)</p>
                            <h3 class="fw-bold text-warning mb-0"><?= $nearly_full_bins_count ?></h3>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="custom-card p-4 bg-white text-center">
                            <i class="bi bi-check-circle-fill fs-3 text-green mb-2"></i>
                            <p class="text-muted small mb-0 text-uppercase">Completed Collections Today</p>
                            <h3 class="fw-bold mb-0"><?= $completed_collections_today ?></h3>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="custom-card p-4 h-100 bg-white">
                            <h5 class="fw-bold mb-3">Quick Actions</h5>
                            
                            <div class="mb-4">
                                <label for="routeSelector" class="form-label fw-bold">Select Assigned Route</label>
                                <select 
                                    id="routeSelector" 
                                    class="form-select form-select-lg" 
                                    onchange="updateRouteDisplay(this.value)"
                                    <?= empty($assigned_routes) ? 'disabled' : '' ?>
                                >
                                    <?php if (empty($assigned_routes)): ?>
                                        <option value="" disabled selected>No active or pending routes assigned</option>
                                    <?php else: ?>
                                        <?php foreach ($assigned_routes as $route): 
                                            $status_label = ($route['status'] == 'active') ? ' (Active)' : ' (Pending)';
                                            $selected = ($route['id'] == $initial_route['id']) ? 'selected' : '';
                                        ?>
                                            <option 
                                                value="<?= $route['id'] ?>" 
                                                data-name="<?= htmlspecialchars($route['route_name']) ?>"
                                                data-bins="<?= $route['total_bins'] ?>"
                                                data-status="<?= $route['status'] ?>"
                                                <?= $selected ?>
                                            >
                                                <?= htmlspecialchars($route['route_name']) ?> - <?= $route['total_bins'] ?> Bins <?= $status_label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-3">
                                <button 
                                    id="startRouteButton"
                                    class="btn btn-lg <?= ($initial_route_status == 'active') ? 'btn-warning' : 'btn-primary' ?> 
                                    <?= empty($assigned_routes) ? 'disabled btn-secondary' : '' ?>"
                                    onclick="startRoute()"
                                    data-route-id="<?= $initial_route_id ?>"
                                >
                                    <i class="bi bi-play-circle-fill me-2"></i> 
                                    <span id="routeButtonText">
                                        <?php 
                                            if ($initial_route) {
                                                if ($initial_route_status == 'active') {
                                                    echo "Continue Route $initial_route_name ($total_assigned_bins_on_route Bins)";
                                                } else {
                                                    echo "Start Route $initial_route_name ($total_assigned_bins_on_route Bins)";
                                                }
                                            } else {
                                                echo "No Active Route Assigned";
                                            }
                                        ?>
                                    </span>
                                </button>
                                
                                <button class="btn btn-outline-primary btn-lg" onclick="switchPage('alerts')">
                                    <i class="bi bi-bell-fill me-2"></i> View <?= $urgent_alerts_count ?> Urgent Alerts
                                </button>
                                <button class="btn btn-outline-secondary btn-lg" onclick="switchPage('logs', 'add')">
                                    <i class="bi bi-plus-circle-fill me-2"></i> Manually Log Collection
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="custom-card p-4 h-100 bg-white">
                            <h5 class="fw-bold mb-3">Assigned Route Map 
                                <span id="routeMapHeader">
                                    <?php if ($initial_route) echo '('. $initial_route_name.')'; ?>
                                </span>
                            </h5>
                            <div 
                                id="mapPlaceholder" 
                                style="height: 400px; width: 100%; border-radius: 0.5rem;"
                                class="bg-light-green border rounded-3 p-3 text-center 
                                    <?php if (!$initial_route) echo 'd-flex align-items-center justify-content-center'; ?>"
                            >
                                <p class="text-muted mb-0" id="routeMapMessage">
                                    <?php if ($initial_route) { ?>
                                        Loading map for Route <?= $initial_route_name ?>...
                                    <?php } else { ?>
                                        No active route map available. Select a route to view.
                                    <?php } ?>
                                </p>
                            </div>
                            </div>
                    </div>
                </div>
            </section>

        </div> 
    </main>
</div> 

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20n6a4s3cfXZ6B5sdgss5A60Fh78w5895E4D1tI/pC7o="
    crossorigin=""></script>

<script src="../assets/js/collector.js"></script>

<script>
// Expose initial route ID for the JS file to use (if needed)
const INITIAL_ROUTE_ID = '<?= $initial_route_id ?>';
</script>

<script>
    // Global map variable
    let map = null;

    // Default center for the map (Manila, Philippines)
    const DEFAULT_CENTER = [14.5995, 120.9842]; 
    const DEFAULT_ZOOM = 13;

    /**
     * Initializes the Leaflet map in the 'mapPlaceholder' div.
     */
    function initializeMap() {
        const mapContainer = document.getElementById('mapPlaceholder');
        
        // Only initialize map if there's an initial route ID. Otherwise, keep the message placeholder.
        if (!INITIAL_ROUTE_ID) {
            mapContainer.innerHTML = '<p class="text-muted mb-0" id="routeMapMessage">No active route map available. Select a route to view.</p>';
            mapContainer.classList.add('d-flex', 'align-items-center', 'justify-content-center');
            return;
        }

        // Remove placeholder class and content before initializing Leaflet
        mapContainer.classList.remove('d-flex', 'align-items-center', 'justify-content-center');
        mapContainer.innerHTML = '';
        
        map = L.map('mapPlaceholder').setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Load the initial route's data immediately
        loadRouteDataAndMarkers(INITIAL_ROUTE_ID);
    }

    /**
     * Clears existing map overlays (markers, etc.) except for the base layer.
     */
    function clearMapOverlays() {
        if (!map) return;
        map.eachLayer(function (layer) {
            // Check if the layer is not the tile layer (base map)
            if (layer.options.attribution !== '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors') {
                map.removeLayer(layer);
            }
        });
    }

    /**
     * Fetches and displays bin markers for the given routeId.
     * NOTE: This uses SIMULATED data. In production, replace the 'if/else' block
     * with an AJAX/fetch call to your Supabase API to get actual bin coordinates for the route.
     */
    function loadRouteDataAndMarkers(routeId) {
        if (!map) return;
        
        clearMapOverlays();

        // --- SIMULATED DATA START ---
        // Replace this block with your actual data fetching logic from the 'bins' and 'collection_route' tables.
        let binsData = [];
        
        // Example UUIDs: Use the first few characters of a UUID for easy simulation
        if (routeId.startsWith('4')) { 
             binsData = [
                { lat: 14.5900, lng: 120.9800, name: 'Bin - North Pier' },
                { lat: 14.6100, lng: 120.9650, name: 'Bin - West Avenue' },
                { lat: 14.6050, lng: 120.9750, name: 'Bin - Market' }
            ];
        } else if (routeId.startsWith('3')) {
             binsData = [
                { lat: 14.5800, lng: 120.9700, name: 'Bin - City Hall' },
                { lat: 14.5850, lng: 120.9900, name: 'Bin - Park Entrance' }
            ];
        } else if (routeId.startsWith('2')) {
             binsData = [
                { lat: 14.6200, lng: 120.9950, name: 'Bin - Industrial Zone' }
            ];
        } 
        // --- SIMULATED DATA END ---

        let markerGroup = L.featureGroup();
        
        if (binsData.length > 0) {
            binsData.forEach(bin => {
                const marker = L.marker([bin.lat, bin.lng]).bindPopup(`<b>${bin.name}</b>`);
                markerGroup.addLayer(marker);
            });

            markerGroup.addTo(map);
            // Fit map view to contain all markers
            map.fitBounds(markerGroup.getBounds().pad(0.1));
        } else {
            // If route has no bins (or data fetch failed), center on default
            map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }
    }

    /**
     * Updates the dashboard display (button, map, and card) when a new route is selected.
     * @param {string} routeId - The UUID of the selected route.
     */
    function updateRouteDisplay(routeId) {
        const selector = document.getElementById('routeSelector');
        const selectedOption = selector.options[selector.selectedIndex];
        
        if (!selectedOption || !routeId) return;

        const routeName = selectedOption.dataset.name;
        const binCount = selectedOption.dataset.bins;
        const status = selectedOption.dataset.status;

        const button = document.getElementById('startRouteButton');
        const buttonTextSpan = document.getElementById('routeButtonText');

        // 1. Update Route Button
        let newButtonText;
        let newButtonClass;
        
        if (status === 'active') {
            newButtonText = `Continue Route ${routeName} (${binCount} Bins)`;
            newButtonClass = 'btn btn-lg btn-warning';
        } else { // pending
            newButtonText = `Start Route ${routeName} (${binCount} Bins)`;
            newButtonClass = 'btn btn-lg btn-primary';
        }

        buttonTextSpan.textContent = newButtonText;
        button.className = newButtonClass;
        button.dataset.routeId = routeId; 
        button.disabled = false;

        // 2. Update Info Card
        document.getElementById('selectedRouteBinCount').textContent = binCount;

        // 3. Update Map and Header
        document.getElementById('routeMapHeader').textContent = `(${routeName})`;
        
        loadRouteDataAndMarkers(routeId);
        
        // This is crucial for Leaflet when the map div dimensions change
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 100);
    }

    /**
     * Handles the click event for the "Start/Continue Route" button.
     * It uses the `switchPage` function (assumed to be in collector.js).
     */
    function startRoute() {
        const button = document.getElementById('startRouteButton');
        const routeId = button.dataset.routeId;
        
        if (routeId) {
            if (typeof switchPage === 'function') {
                switchPage('routes', routeId);
            } else {
                // Fallback using the custom alert modal
                showCustomAlert(`Route Action Failed: switchPage() function is not defined in collector.js.`);
            }
        }
    }
    
    // Call initializeMap when the page loads
    document.addEventListener('DOMContentLoaded', initializeMap);

    // Placeholder for a basic custom alert if collector.js doesn't provide one
    function showCustomAlert(message) {
        document.getElementById('alertModalBody').textContent = message;
        const alertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
        alertModal.show();
    }
</script>

</body>
</html>