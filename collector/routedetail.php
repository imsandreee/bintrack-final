<?php
// collector/routedetail.php

// Ensure paths are correct for your structure
require_once '../auth/config.php';
// Assumes supabase_fetch() is included via config.php or a dedicated utility file
// For this script to work, you MUST have the supabase_fetch() function available.

session_start();

// --- Collector and Route Authorization Check ---
$collector_id = $_SESSION['user']['id'] ?? '';
$route_id = $_GET['route_id'] ?? '';

if (!$collector_id || !$route_id) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// 1. Fetch Route Name
$route_details = supabase_fetch(
    "collection_routes",
    "?id=eq.$route_id&select=route_name"
);
$route_name = $route_details[0]['route_name'] ?? 'Route Not Found';


// 2. Get bins assigned to this route
$route_bins = supabase_fetch(
    "route_bins",
    "?route_id=eq.$route_id&select=bin_id,bins(id,bin_code,location_name,latitude,longitude)"
);

$bins_data = [];

foreach ($route_bins as $rb) {

    $binId = $rb['bin_id'];
    $info = $rb['bins'];
    
    $lat_lng_string = $info['latitude'] . ',' . $info['longitude'];

    // Latest reading
    $readings = supabase_fetch(
        "sensor_readings",
        "?bin_id=eq.$binId&order=timestamp.desc&limit=1"
    );
    $reading = $readings ? $readings[0] : null;

    // Alerts
    $alerts = supabase_fetch(
        "bin_alerts",
        "?bin_id=eq.$binId&resolved=eq.false"
    );

    // Check if already collected
    // NOTE: This logic should ideally check if collected TODAY, but we keep the original logic for now.
    $today_log = supabase_fetch(
        "collection_logs",
        "?bin_id=eq.$binId&collector_id=eq.$collector_id&order=collected_at.desc&limit=1"
    );
    
    $is_collected = !empty($today_log);
    
    $fill_level_cm = $reading['ultrasonic_distance_cm'] ?? null;
    // Assuming bin height is 100cm for simplicity in this calculation (100 - distance)
    $fill_percent = $fill_level_cm !== null ? max(0, 100 - $fill_level_cm) : null; 
    $fill_color = '';
    if ($fill_percent !== null) {
        $fill_color = $fill_percent >= 90 ? 'bg-danger'
                      : ($fill_percent >= 70 ? 'bg-warning' : 'bg-success');
    }

    $bins_data[] = [
        "id" => $info['id'],
        "code" => $info['bin_code'],
        "location_name" => $info['location_name'], // Display name
        "location_coords" => $lat_lng_string,     // Coordinates for map
        "latitude" => $info['latitude'],
        "longitude" => $info['longitude'],
        "fill_percent" => $fill_percent,
        "fill_color" => $fill_color,
        "weight" => $reading['load_cell_weight_kg'] ?? null,
        "alerts" => $alerts,
        "is_collected" => $is_collected
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Collector Route: <?= htmlspecialchars($route_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/collector.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-o9N1j8Y+1l3flK8lR5Dd4tGgROk87oZg6M12B9H+0hM=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-o9N1j8Y+1l3flK8lR5Dd4tGgROk87oZg6M12B9H+0hM=" crossorigin=""></script>
        
    <style>
        /* Custom styles to ensure map and list scroll independently if needed */
        #binListContainer {
            max-height: 700px; /* Adjust height as needed */
            overflow-y: auto;
        }
        #routeMap {
            height: 700px; /* Fixed height for a good map view */
            width: 100%;
        }
        .bin-list-card {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .bin-list-card:hover {
            background-color: #f8f9fa;
        }
        .bin-collected {
            border-left: 5px solid #198754; /* Green border for collected */
        }
    </style>
</head>
<body>

    <?php include '../includes/collector/navbar.php'; ?>

    <div class="container-fluid p-0">

        <section id="routeDetailsPage" class="page-content">
            <h1 class="mb-4 fw-bold">Route Details: <span id="routeIdDisplay"><?= htmlspecialchars($route_name) ?></span></h1>
            <input type="hidden" id="routeId" value="<?= htmlspecialchars($route_id) ?>">

            <?php 
                $pending_count = count(array_filter($bins_data, fn($b) => !$b['is_collected']));
            ?>
            <h5 class="fw-bold mb-3">
                <i class="bi bi-pin-map me-2"></i> Route Bins Overview 
                <span class="badge bg-primary ms-2">Total Bins: <?= count($bins_data) ?></span>
                <span class="badge bg-warning text-dark">Pending: <span id="pendingCount"><?= $pending_count ?></span></span>
            </h5>
            
            <div class="row g-4 mb-4">
                
                <div class="col-lg-7">
                    <div class="custom-card p-2 bg-white shadow-sm">
                        <div id="routeMap" class="border rounded-3"></div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="custom-card p-0 bg-white shadow-sm" id="binListContainer">
                        <ul class="list-group list-group-flush" id="routeBinList">
                            <?php if (empty($bins_data)): ?>
                                <li class="list-group-item text-center text-muted py-5">
                                    <i class="bi bi-slash-circle fs-3 mb-2"></i>
                                    <p class="m-0">No bins assigned to this route.</p>
                                </li>
                            <?php endif; ?>

                            <?php foreach ($bins_data as $b): ?>
                                <?php 
                                    $fill_level_text = $b['fill_percent'] !== null ? round($b['fill_percent'])."%" : "N/A";
                                    $fill_bar_style = $b['fill_percent'] !== null ? "width: {$b['fill_percent']}%;" : "width: 0;";
                                    $alert_count = count($b['alerts']);
                                    // Corrected Google Maps Navigation URL format
                                    $map_nav_url = 'https://www.google.com/maps/dir/?api=1&destination=' . $b['latitude'] . ',' . $b['longitude'];
                                ?>
                                <li class="list-group-item bin-list-card <?= $b['is_collected'] ? 'bin-collected' : '' ?>" 
                                    data-bin-id="<?= $b['id'] ?>"
                                    data-lat="<?= $b['latitude'] ?>"
                                    data-lng="<?= $b['longitude'] ?>"
                                    onclick="zoomToBin(this)">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1 fw-bold"><?= $b['code'] ?></h6>
                                        <div class="bin-status-cell">
                                            <?php if ($b['is_collected']): ?>
                                                <span class="badge bg-success status-badge"><i class="bi bi-check-lg"></i> Collected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark status-badge">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($b['location_name']) ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-nowrap me-3">Fill: <?= $fill_level_text ?></small>
                                        <div class="progress flex-grow-1 me-3" style="height: 6px;">
                                            <div class="progress-bar <?= $b['fill_color'] ?>" role="progressbar" 
                                                style="<?= $fill_bar_style ?>" aria-valuenow="<?= $b['fill_percent'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-nowrap me-3">Weight: <?= $b['weight'] !== null ? round($b['weight'], 1)." kg" : "N/A" ?></small>
                                        
                                        <?php if ($alert_count > 0): ?>
                                            <span class="badge bg-danger ms-1" title="<?= $alert_count ?> unresolved alert(s)"><i class="bi bi-exclamation-triangle-fill"></i> <?= $alert_count ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="bin-action-cell mt-2 d-flex justify-content-end">
                                        <a href="<?= htmlspecialchars($map_nav_url) ?>" 
                                            target="_blank" class="btn btn-sm btn-outline-info me-2">
                                            <i class="bi bi-geo-alt"></i> Navigate
                                        </a>

                                        <?php if (!$b['is_collected']): ?>
                                            <button class="btn btn-sm btn-primary btn-collect me-2"
                                                             onclick="collectBin('<?= $b['id'] ?>', this); event.stopPropagation();">
                                                <i class="bi bi-check-lg"></i> Collected
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-report"
                                                             onclick="reportIssue('<?= $b['id'] ?>'); event.stopPropagation();">
                                                <i class="bi bi-flag"></i> Report
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" disabled><i class="bi bi-check-all"></i> Done</button>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            </div>
        </section>

        <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-card">
              <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-success" id="customAlertModalLabel"><i class="bi bi-truck-flatbed me-2"></i> BinTrack Message</h5>
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

    </div> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Helper function to show the custom modal
    function showCustomAlert(title, message) {
        document.getElementById('customAlertModalLabel').innerText = title;
        document.getElementById('alertModalBody').innerHTML = message;
        const modal = new bootstrap.Modal(document.getElementById('customAlertModal'));
        modal.show();
    }
    
    // COLLECT BIN ACTION (Updated to use relative path and update UI instantly)
    function collectBin(binId, buttonElement) {
        // Find the parent list item (li)
        const listItem = buttonElement.closest('li');
        buttonElement.disabled = true;
        
        fetch("collector_actions.php", { 
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=collect&bin_id=${binId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // UI Update Success
                listItem.classList.add('bin-collected');

                // Update Status Badge
                const statusCell = listItem.querySelector('.bin-status-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success status-badge"><i class="bi bi-check-lg"></i> Collected</span>';
                }

                // Replace Action Buttons
                const actionCell = listItem.querySelector('.bin-action-cell');
                if (actionCell) {
                    actionCell.innerHTML = '<button class="btn btn-sm btn-success" disabled><i class="bi bi-check-all"></i> Done</button>';
                }
                
                // Update Pending Count
                const pendingElement = document.getElementById('pendingCount');
                let currentPending = parseInt(pendingElement.innerText);
                if (!isNaN(currentPending) && currentPending > 0) {
                    pendingElement.innerText = currentPending - 1;
                }
                
                showCustomAlert("Collection Success", "Bin " + binId + " marked as collected!");

            } else {
                buttonElement.disabled = false;
                showCustomAlert("Collection Error", data.message || "Error marking bin as collected.");
            }
        })
        .catch(error => {
            buttonElement.disabled = false;
            showCustomAlert("Network Error", "Could not connect to the server.");
            console.error('Error:', error);
        });
    }

    // REPORT ISSUE ACTION (Remains same)
    function reportIssue(binId) {
        if (!confirm("Are you sure you want to report an issue for this bin (e.g., damage)?")) {
            return;
        }

        fetch("collector_actions.php", { 
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `action=report&bin_id=${binId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showCustomAlert("Report Sent", "Issue reported successfully. Management has been notified.");
            } else {
                showCustomAlert("Report Error", data.message || "Error reporting issue.");
            }
        })
        .catch(error => {
            showCustomAlert("Network Error", "Could not connect to the server to report issue.");
            console.error('Error:', error);
        });
    }
    
    // Function to zoom the map when a list item is clicked
    let map = null; // Declare map globally for zoomToBin
    function zoomToBin(listItem) {
        if (!map) return;
        const lat = parseFloat(listItem.dataset.lat);
        const lng = parseFloat(listItem.dataset.lng);

        if (!isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], 17); // Zoom level 17 is usually good for street level
        }
    }
    </script>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapElement = document.getElementById('routeMap');
        
        if (!mapElement) return;

        // Initialize Leaflet map (assign to global map variable)
        map = L.map('routeMap'); 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const bins = <?php echo json_encode($bins_data); ?>;
        const markers = [];

        bins.forEach(bin => {
            if (!bin.location_coords) return; 
            const coords = bin.location_coords.split(',');
            
            if (coords.length !== 2 || isNaN(Number(coords[0])) || isNaN(Number(coords[1]))) return;
            
            const [lat, lng] = coords.map(Number); 
            
            // Map marker color logic
            let color = '#007bff'; // Blue for Pending
            if (bin.is_collected) {
                color = '#28a745'; // Green for Collected
            } else if (bin.alerts.length) {
                color = '#dc3545'; // Red for ALERT
            }
            
            const statusText = bin.is_collected ? 'Collected' : (bin.alerts.length ? 'ALERT' : 'Pending');
            // Check for built-in round function availability (optional, use Math.round if needed)
            const round = window.round || Math.round; 
            const fillPercent = bin.fill_percent !== null ? round(bin.fill_percent) + '%' : 'N/A';
            const weightText = bin.weight !== null ? round(bin.weight, 1) + ' kg' : 'N/A';
            // Corrected Google Maps Navigation URL format
            const mapNavUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;


            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                color: color,
                fillColor: color,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(`
                <div class="fw-bold">${bin.code} (${bin.location_name})</div>
                <div>Fill Level: ${fillPercent}</div>
                <div>Weight: ${weightText}</div>
                <div>Status: <span style="color: ${color};">${statusText}</span></div>
                <div>Alerts: ${bin.alerts.length || '-'}</div>
                <a href="${mapNavUrl}" target="_blank" class="btn btn-sm btn-outline-info mt-2">Navigate (Google Maps)</a>
            `);

            markers.push(marker);
        });

        // Fit map bounds to markers
        if (markers.length) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.2));
        } else {
            // Set default view if no markers
            map.setView([14.5995, 120.9842], 12); // Default to Manila center
        }
        
        // CRITICAL FIX: Invalidate map size after layout settles - INCREASED DELAY
        // The white screen issue is almost always resolved by ensuring the map container 
        // has its correct dimensions when Leaflet initializes and/or calls invalidateSize().
        setTimeout(function() {
            map.invalidateSize();
        }, **1000**); // Increased from 500ms to 1000ms for robustness
    });
    </script>
</body>
</html>