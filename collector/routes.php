<?php
// collector/routes.php

// --- Configuration Setup (Standardized Header) ---
require_once '../auth/config.php';
// IMPORTANT: Assumes config.php now contains the SUPABASE_URL and SUPABASE_KEY definitions
// and/or that a separate utility file containing the 'supabase_fetch' function is included.

session_start();

// --- Supabase Fetch Function (Included here for context/completeness) ---
if (!function_exists('supabase_fetch')) {
    function supabase_fetch($table, $query = '') {
        // Assuming $SUPABASE_URL and $SUPABASE_KEY are defined in config.php
        $url = SUPABASE_URL . '/rest/v1/' . $table . $query;
        $ch = curl_init($url);
        
        // Safety check for configuration
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY')) {
            error_log("Supabase configuration missing.");
            return [];
        }

        // Set up cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Accept: application/json'
        ]);
        
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for HTTP errors before decoding
        if ($http_code >= 400) {
            error_log("Supabase API Error ($http_code) fetching $table: " . $resp);
            return [];
        }

        $data = json_decode($resp, true);
        return is_array($data) ? $data : [];
    }
}
// --- END Supabase Fetch Function ---


// Logged-in collector authorization check
$collector_id = $_SESSION['user']['id'] ?? '';
if (!$collector_id) {
    // Send 401 response and exit for unauthorized access
    http_response_code(401);
    echo "Unauthorized access. Please log in.";
    exit;
}

// Initialize arrays
$routes_data = [];

// 1️⃣ Fetch all route assignments for this collector
// UPDATED Query: Target collection_route, adding 'status'
$assigned_routes = supabase_fetch(
    "collection_route",
    "?driver=eq.$collector_id&select=id,route_name,bin_ids,status" // Added 'status'
);


if ($assigned_routes) {
    foreach ($assigned_routes as $route_details) {
        // Route info is now directly in the result
        $route_id   = $route_details['id'];
        $route_name = htmlspecialchars($route_details['route_name']);
        $route_status_db = $route_details['status']; // New status field
        
        // Bin IDs are retrieved as an array directly from the 'bin_ids' column
        $bin_ids = $route_details['bin_ids'] ?? [];
        
        // Handle potential string representation of array if not decoded properly by PHP/Supabase interaction.
        if (is_string($bin_ids)) {
            // This regex strips braces and quotes, then splits by comma
            $bin_ids = array_map(fn($v) => trim($v, '" '), explode(',', trim($bin_ids, '{}')));
            $bin_ids = array_filter($bin_ids, fn($v) => !empty($v)); // Remove empty elements
        }
        
        $total_bins = count($bin_ids);
        
        // 3️⃣ Fetch completed bins (collection logs) by this collector
        $completed_count = 0;
        if (!empty($bin_ids)) {
            // Supabase IN operator requires UUIDs to be surrounded by quotes in the URL query string.
            $quoted_bin_ids = array_map(fn($id) => '"' . $id . '"', $bin_ids);
            $bin_id_in_query = implode(",", $quoted_bin_ids);

            $completed_logs = supabase_fetch(
                "collection_logs",
                // bin_id=in.("uuid1","uuid2",...)
                "?collector_id=eq.$collector_id&bin_id=in.(" . $bin_id_in_query . ")&select=bin_id&limit=" . $total_bins
            );
            // We count the number of *unique* bins logged
            $completed_count = count(array_unique(array_column($completed_logs, 'bin_id')));
        } 

        // 4️⃣ Compute progress
        $progress = ($total_bins > 0) ? round(($completed_count / $total_bins) * 100) : 0;

        // 5️⃣ Determine status badge - UPDATED to check DB status first
        $status_badge = '';
        $badge_class = '';
        
        // Use DB status for explicit states (Completed, Cancelled, Pending)
        switch ($route_status_db) {
            case 'completed':
                $badge_class = 'bg-success';
                $status_text = 'Completed';
                break;
            case 'cancelled':
                $badge_class = 'bg-secondary';
                $status_text = 'Cancelled';
                break;
            case 'pending':
                $badge_class = 'bg-info text-dark';
                $status_text = 'Pending';
                break;
            case 'active':
            default:
                // For 'active' or default, use the progress-based logic
                if ($total_bins === 0) {
                    $badge_class = 'bg-secondary';
                    $status_text = 'No Bins';
                } elseif ($completed_count == 0) {
                    $badge_class = 'bg-danger';
                    $status_text = 'Not Started';
                } elseif ($completed_count < $total_bins) {
                    $badge_class = 'bg-warning text-dark';
                    $status_text = 'In Progress';
                } else { // Progress is 100% but DB status is 'active'
                    $badge_class = 'bg-success';
                    $status_text = 'Completed (Unsynced)';
                }
                break;
        }

        $status_badge = "<span class='badge {$badge_class}'>{$status_text}</span>";

        $routes_data[] = [
            'id' => $route_id,
            'name' => $route_name,
            'total_bins' => $total_bins,
            'completed' => $completed_count,
            'progress' => $progress,
            'status_badge' => $status_badge,
            'route_status_db' => $route_status_db // Include for potential future use
        ];
    }
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/collector.css">
</head>
<body>

<?php include '../includes/collector/navbar.php'; ?>

<div class="container-fluid p-0">
<section id="routesPage" class="page-content">
<h1 class="mb-4 fw-bold">Assigned Collection Routes <i class="bi bi-truck-flatbed"></i></h1>

<div class="custom-card p-4 bg-white shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Your Active Routes</h5>
        <button class="btn btn-outline-secondary" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
    
    <hr>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Route Name</th>
                    <th>Total Bins</th>
                    <th>Completed</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th width="100">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes_data)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No assigned routes found. Contact administrator.</td></tr>
                <?php else: ?>
                    <?php foreach ($routes_data as $route): ?>
                        <tr onclick="goToRouteDetails('<?= $route['id'] ?>')" style="cursor: pointer;">
                            <td class="fw-bold"><?= $route['name'] ?></td>
                            <td><?= $route['total_bins'] ?></td>
                            <td><?= $route['completed'] ?> / <?= $route['total_bins'] ?></td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar 
                                        <?php 
                                            // Determine progress bar color based on progress AND DB status
                                            if ($route['route_status_db'] === 'completed' || $route['progress'] == 100) {
                                                echo 'bg-success';
                                            } elseif ($route['route_status_db'] === 'cancelled') {
                                                echo 'bg-secondary';
                                            } elseif ($route['route_status_db'] === 'pending' || $route['completed'] == 0) {
                                                echo 'bg-danger';
                                            } else {
                                                echo 'bg-primary';
                                            }
                                        ?>" 
                                        role="progressbar" 
                                        style="width: <?= $route['progress'] ?>%" 
                                        aria-valuenow="<?= $route['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </td>
                            <td><?= $route['status_badge'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="goToRouteDetails('<?= $route['id'] ?>'); event.stopPropagation();">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</section>
</div>

<script>
function goToRouteDetails(routeId) {
    // Navigates to the detailed route view
    window.location.href = "routedetail.php?route_id=" + routeId;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/collector.js"></script>
</body>
</html>