<?php
// D:\xammp\htdocs\project\collector\routedetail.php

// Ensure paths are correct for your structure
require_once '../auth/config.php';
session_start();

// --- Collector and Route Authorization Check ---
$collector_id = $_SESSION['user']['id'] ?? '';
$route_id = $_GET['route_id'] ?? '';

if (!$collector_id || !$route_id) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}
if (!function_exists('supabase_fetch')) {
    die("Error: Supabase fetch function is not defined. Check config.php.");
}

// 1. Fetch Route Details (including status, waypoints, bin_ids, and STARTED_AT)
$route_details_result = supabase_fetch(
    "collection_route",
    "?id=eq.$route_id&select=route_name,bin_ids,status,waypoints,distance,estimated_time,started_at"
);

// ⭐ Safely determine the route data and handle failure
$route_data = [];
if (is_array($route_details_result) && !empty($route_details_result)) {
    $route_data = $route_details_result[0];
}

// Check if route data was found before proceeding
if (empty($route_data)) {
    http_response_code(404);
    echo "Route ID $route_id not found or unauthorized.";
    exit;
}
// ⭐ End Fix

$route_name = $route_data['route_name'] ?? 'Route Not Found';
$bin_ids_array = $route_data['bin_ids'] ?? [];
$route_status = $route_data['status'] ?? 'pending';
$route_distance = $route_data['distance'] ?? null;
$route_time = $route_data['estimated_time'] ?? null;
$route_started_at = $route_data['started_at'] ?? null; // CAPTURED START TIME

// Initialize bins data array
$bins_data = [];

// --- CONSTANTS/UTILITIES ---
// Set these values based on your physical bin measurements:
define('DISTANCE_WHEN_EMPTY', 30.0); // Ultrasonic reading (cm) when the bin is empty (0% fill)
define('DISTANCE_WHEN_FULL', 5.0); // Ultrasonic reading (cm) when the bin is considered full (100% fill)
define('USABLE_RANGE', DISTANCE_WHEN_EMPTY - DISTANCE_WHEN_FULL);

function calculate_fill_percent($distance_cm) {
    // 1. Handle non-numeric or invalid input
    if ($distance_cm === null || USABLE_RANGE <= 0) return null;

    // 2. Handle 0% and 100% boundaries
    if ($distance_cm >= DISTANCE_WHEN_EMPTY) return 0; // Empty or sensor error reading high
    if ($distance_cm <= DISTANCE_WHEN_FULL) return 100; // Full or sensor error reading low

    // 3. Calculate the distance filled (how much the current reading is below the 'empty' level)
    $distance_filled = DISTANCE_WHEN_EMPTY - $distance_cm;
    
    // 4. Calculate Fill %: (Distance Filled / Total Usable Range) * 100
    $fill = ($distance_filled / USABLE_RANGE) * 100;
    
    // Clamp and round the result
    return max(0, min(100, round($fill)));
}

// --- BIN DATA FETCHING ---
if (!empty($bin_ids_array)) {
    $bin_ids_list = implode(',', $bin_ids_array);
    $bin_query = "?id=in.($bin_ids_list)&select=id,bin_code,location_name,latitude,longitude";

    $bins_list_result = supabase_fetch(
        "bins",
        $bin_query
    );

    foreach ($bins_list_result as $bin_info) {

        $binId = $bin_info['id'];

        // --- FETCH LATEST SENSOR READING ---
        $latest_reading_query = "?bin_id=eq.$binId&order=timestamp.desc&limit=1&select=ultrasonic_distance_cm,load_cell_weight_kg";
        $reading_result = supabase_fetch("sensor_readings", $latest_reading_query);
        
        // ⭐ FIX APPLIED HERE: Safely assign $latest_reading
        $latest_reading = (is_array($reading_result) && !empty($reading_result)) 
            ? $reading_result[0] 
            : ['ultrasonic_distance_cm' => null, 'load_cell_weight_kg' => null];
        // ⭐ END FIX
        
        // --- CALCULATE FILL LEVEL AND COLOR ---
        // These now safely use the $latest_reading array guaranteed above
        $distance_cm = $latest_reading['ultrasonic_distance_cm'] ?? null;
        $weight_kg = $latest_reading['load_cell_weight_kg'] ?? null;
        
        $fill_percent = calculate_fill_percent($distance_cm);
        
        $fill_color = 'bg-secondary';
        if ($fill_percent !== null) {
            if ($fill_percent >= 90) {
                $fill_color = 'bg-danger';
            } elseif ($fill_percent >= 70) {
                $fill_color = 'bg-warning';
            } else {
                $fill_color = 'bg-success';
            }
        }
        
        // Assume alerts are fetched separately or set to empty array for now
        $alerts = []; // Fetch active alerts here if needed

        // --- CHECK COLLECTION STATUS (IMPROVED LOGIC) ---
        $is_collected = false;
        
        $collected_query = "?bin_id=eq.$binId&collector_id=eq.$collector_id&order=collected_at.desc&limit=1";
        
        // IMPROVEMENT: Filter collection logs by route start time if the route is active
        if ($route_status === 'active' && !empty($route_started_at)) {
             // Look for logs collected after the route started
             $route_start_filter = $route_started_at; 
             $collected_query = "?bin_id=eq.$binId&collector_id=eq.$collector_id&collected_at=gte.$route_start_filter&order=collected_at.desc&limit=1";
        }

        $latest_log = supabase_fetch("collection_logs", $collected_query);
        
        if (!empty($latest_log)) {
            $log_status = $latest_log[0]['status'] ?? 'collected'; 
            
            if ($log_status !== 'skipped') {
                $collected_timestamp = strtotime($latest_log[0]['collected_at']);
                
                if ($route_status === 'pending') {
                    if (date('Y-m-d', $collected_timestamp) === date('Y-m-d')) {
                         $is_collected = true;
                    }
                } else {
                    $is_collected = true;
                }
            }
        }
        
        $bins_data[] = [
            "id" => $bin_info['id'],
            "code" => $bin_info['bin_code'],
            "location_name" => $bin_info['location_name'],
            // Removed latitude/longitude since the map is gone
            "fill_percent" => $fill_percent,
            "fill_color" => $fill_color,
            "weight" => $weight_kg, 
            "alerts" => $alerts,
            "is_collected" => $is_collected
        ];
    }
}
$pending_count = count(array_filter($bins_data, fn($b) => !$b['is_collected']));
$all_collected = $pending_count === 0 && count($bins_data) > 0;
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
        
    <style>
        /* Adjust layout since the map column is removed */
        #binListContainer { max-height: 800px; overflow-y: auto; }
        .bin-list-card { cursor: pointer; transition: background-color 0.2s; }
        .bin-list-card:hover { background-color: #f8f9fa; }
        .bin-collected { border-left: 5px solid #198754; }
        .status-badge-container .badge { font-size: 0.9em; padding: 0.5em 0.75em; }
    </style>
</head>
<body>

    <?php include '../includes/collector/navbar.php'; ?>

    <div class="container p-4"> <section id="routeDetailsPage" class="page-content">
            <h1 class="mb-4 fw-bold">Route: <span id="routeIdDisplay"><?= htmlspecialchars($route_name) ?></span></h1>
            <input type="hidden" id="routeId" value="<?= htmlspecialchars($route_id) ?>">
            <input type="hidden" id="initialStatus" value="<?= htmlspecialchars($route_status) ?>">

            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded shadow-sm">
                <div class="summary-info">
                    <span class="badge bg-primary me-3">Total Bins: <?= count($bins_data) ?></span>
                    <span id="pendingBadge" class="badge bg-warning text-dark me-3">Pending: <span id="pendingCount"><?= $pending_count ?></span></span>
                    <span id="statusDisplay" class="badge bg-secondary me-3">Status: <?= ucfirst(htmlspecialchars($route_status)) ?></span>
                    <?php if ($route_distance !== null): ?>
                        <span class="badge bg-info text-dark me-3">Distance: <?= round($route_distance, 1) ?> km</span>
                    <?php endif; ?>
                    <?php if ($route_time !== null): ?>
                        <span class="badge bg-info text-dark me-3">Est. Time: <?= htmlspecialchars($route_time) ?></span>
                    <?php endif; ?>
                </div>

                <div class="route-actions">
                    <button id="startRouteBtn" class="btn btn-success me-2" 
                        onclick="updateRouteStatus('active', this)" 
                        <?= $route_status !== 'pending' ? 'disabled' : '' ?>>
                        <i class="bi bi-play-circle"></i> Start Route
                    </button>
                    <button id="completeRouteBtn" class="btn btn-danger" 
                        onclick="updateRouteStatus('completed', this)" 
                        <?= $route_status !== 'active' || !$all_collected ? 'disabled' : '' ?>>
                        <i class="bi bi-stop-circle"></i> Complete Route
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-4">
                
                <div class="col-12">
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
                                ?>
                                <li class="list-group-item bin-list-card <?= $b['is_collected'] ? 'bin-collected' : '' ?>" 
                                    data-bin-id="<?= $b['id'] ?>"
                                    data-is-collected="<?= $b['is_collected'] ? 'true' : 'false' ?>">
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
                                        <?php if (!$b['is_collected']): ?>
                                            <button class="btn btn-sm btn-primary btn-collect me-2"
                                                            onclick="collectBin('<?= $b['id'] ?>', this);"
                                                            <?= $route_status !== 'active' ? 'disabled' : '' ?>>
                                                <i class="bi bi-check-lg"></i> Collected
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-report"
                                                            onclick="reportIssue('<?= $b['id'] ?>');"
                                                            <?= $route_status !== 'active' ? 'disabled' : '' ?>>
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
    const ROUTE_ID = document.getElementById('routeId').value;
    const INITIAL_STATUS = document.getElementById('initialStatus').value;
    
    // --- UTILITY FUNCTIONS ---
    function showCustomAlert(title, message) {
        document.getElementById('customAlertModalLabel').innerText = title;
        document.getElementById('alertModalBody').innerHTML = message;
        const modal = new bootstrap.Modal(document.getElementById('customAlertModal'));
        modal.show();
    }
    
    function updatePendingCount(change) {
        const pendingElement = document.getElementById('pendingCount');
        let currentPending = parseInt(pendingElement.innerText);
        if (!isNaN(currentPending)) {
            const newCount = Math.max(0, currentPending + change);
            pendingElement.innerText = newCount;
            checkCompletionStatus(newCount);
        }
    }
    
    // updateMapMarker function removed

    function updateListButtons(listItem, isCollected) {
        const actionCell = listItem.querySelector('.bin-action-cell');
        
        // 1. Update List Item Class
        if (isCollected) {
            listItem.classList.add('bin-collected');
            listItem.dataset.isCollected = 'true';
        } else {
             listItem.classList.remove('bin-collected');
             listItem.dataset.isCollected = 'false';
        }

        // 2. Update Status Badge
        const statusCell = listItem.querySelector('.bin-status-cell');
        statusCell.innerHTML = isCollected 
            ? '<span class="badge bg-success status-badge"><i class="bi bi-check-lg"></i> Collected</span>'
            : '<span class="badge bg-warning text-dark status-badge">Pending</span>';

        // 3. Update Action Buttons
        actionCell.innerHTML = ''; // Clear action cell

        if (isCollected) {
            actionCell.innerHTML += '<button class="btn btn-sm btn-success" disabled><i class="bi bi-check-all"></i> Done</button>';
        } else if (document.getElementById('initialStatus').value === 'active') {
             actionCell.innerHTML += `
                <button class="btn btn-sm btn-primary btn-collect me-2" onclick="collectBin('${listItem.dataset.binId}', this);">
                    <i class="bi bi-check-lg"></i> Collected
                </button>
                <button class="btn btn-sm btn-outline-danger btn-report" onclick="reportIssue('${listItem.dataset.binId}');">
                    <i class="bi bi-flag"></i> Report
                </button>
             `;
        }
    }

    function updateRouteUI(newStatus) {
        const statusDisplay = document.getElementById('statusDisplay');
        const startBtn = document.getElementById('startRouteBtn');
        const completeBtn = document.getElementById('completeRouteBtn');
        const collectBtns = document.querySelectorAll('.btn-collect, .btn-report');
        const pendingCount = parseInt(document.getElementById('pendingCount').innerText);
        
        // Update status badge
        statusDisplay.innerText = 'Status: ' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        statusDisplay.className = `badge ${newStatus === 'active' ? 'bg-success' : (newStatus === 'completed' ? 'bg-dark' : 'bg-secondary')} me-3`;
        
        // Update route buttons
        startBtn.disabled = newStatus !== 'pending';
        completeBtn.disabled = newStatus !== 'active' || pendingCount > 0;
        
        // Enable/Disable bin action buttons
        collectBtns.forEach(btn => {
            const listItem = btn.closest('li');
            if (listItem && listItem.dataset.isCollected === 'false') {
                 btn.disabled = newStatus !== 'active';
            } else if (listItem && listItem.dataset.isCollected === 'true' && newStatus === 'active') {
                updateListButtons(listItem, true); 
            }
        });

        // Set the current status for logic checks
        document.getElementById('initialStatus').value = newStatus;
    }
    
    function checkCompletionStatus(newPendingCount) {
        const completeBtn = document.getElementById('completeRouteBtn');
        const currentStatus = document.getElementById('initialStatus').value;
        const totalBins = <?= count($bins_data) ?>;
        
        if (totalBins > 0 && newPendingCount === 0 && currentStatus === 'active') {
             completeBtn.disabled = false;
             showCustomAlert("Route Ready! 🎉", "All bins collected! You can now complete the route.");
        } else {
             completeBtn.disabled = true;
        }
    }

    // --- AJAX ACTIONS ---

    // 1. COLLECT BIN
    function collectBin(binId, buttonElement) {
        const listItem = buttonElement.closest('li');
        buttonElement.disabled = true;
        
        // Ensure ROUTE_ID is included in the payload
        const payload = `action=collect&bin_id=${binId}&collector_id=${<?= json_encode($collector_id) ?>}&route_id=${ROUTE_ID}`;

        fetch("collector_actions.php", { 
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: payload
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateListButtons(listItem, true);
                updatePendingCount(-1);
                // updateMapMarker(binId, 'collected'); // Removed map call
                showCustomAlert("Collection Success ✅", "Bin **" + binId + "** marked as collected!");
            } else {
                buttonElement.disabled = false;
                showCustomAlert("Collection Error ❌", data.message || "Error marking bin as collected.");
            }
        })
        .catch(error => {
            buttonElement.disabled = false;
            showCustomAlert("Network Error 🌐", "Could not connect to the server.");
            console.error('Error:', error);
        });
    }

    // 2. REPORT ISSUE
    function reportIssue(binId) {
        const issue = prompt("Please enter a brief description of the issue for Bin " + binId + ":");
        if (!issue) return;

        // Ensure ROUTE_ID is included in the payload
        const payload = `action=report&bin_id=${binId}&issue=${encodeURIComponent(issue)}&route_id=${ROUTE_ID}`;

        fetch("collector_actions.php", { 
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: payload
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // updateMapMarker(binId, 'alert'); // Removed map call
                showCustomAlert("Report Sent 📢", `Issue reported for Bin **${binId}**. Management has been notified.`);
            } else {
                showCustomAlert("Report Error ❌", data.message || "Error reporting issue.");
            }
        })
        .catch(error => {
            showCustomAlert("Network Error 🌐", "Could not connect to the server to report issue.");
            console.error('Error:', error);
        });
    }

    // 3. UPDATE ROUTE STATUS (Start/Complete)
    function updateRouteStatus(newStatus, buttonElement) {
        if (newStatus === 'completed') {
            if (!confirm("Are you sure you want to COMPLETE this route? All pending bins will be marked as skipped.")) {
                return;
            }
        }
        
        buttonElement.disabled = true;

        fetch("collector_actions.php", { 
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `action=update_route_status&route_id=${ROUTE_ID}&status=${newStatus}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateRouteUI(newStatus);
                showCustomAlert("Route Status Updated 🚦", data.message);
                if (newStatus === 'completed') {
                    // Force disable all bin buttons when completed
                    document.querySelectorAll('.btn-collect, .btn-report').forEach(btn => btn.disabled = true);
                }
            } else {
                 showCustomAlert("Route Update Error ❌", data.message || "Error updating route status.");
            }
        })
        .catch(error => {
            showCustomAlert("Network Error 🌐", "Could not connect to the server.");
            console.error('Error:', error);
        })
        .finally(() => {
            const pendingCount = parseInt(document.getElementById('pendingCount').innerText);
            checkCompletionStatus(pendingCount);
            if (document.getElementById('initialStatus').value !== newStatus) {
                 buttonElement.disabled = false; 
            }
        });
    }

    // Leaflet map initialization section (map, binMarkers, zoomToBin) removed

    document.addEventListener('DOMContentLoaded', function() {
        // Map related checks and initializations removed
        
        // Initial check for completion button state
        checkCompletionStatus(parseInt(document.getElementById('pendingCount').innerText));
    });
    </script>
</body>
</html>