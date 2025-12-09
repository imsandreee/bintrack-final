<?php
// collector/routes.php

// --- Configuration Setup (Standardized Header) ---
require_once '../auth/config.php';
// IMPORTANT: Assumes config.php now contains the SUPABASE_URL and SUPABASE_KEY definitions
// and/or that a separate utility file containing the 'supabase_fetch' function is included.

session_start();

// --- Supabase Fetch Function (Included here for context/completeness) ---
// Note: In a production environment, this function should be in an included utility file.
// This definition is required to make the rest of the script executable.
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
$assigned_routes = supabase_fetch(
    "route_assignments",
    "?collector_id=eq.$collector_id&select=route_id,assigned_at,collection_routes(id,route_name)"
);


if ($assigned_routes) {
    foreach ($assigned_routes as $assignment) {
        $route_id   = $assignment['route_id'];
        $route_name = htmlspecialchars($assignment['collection_routes']['route_name']);

        // 2️⃣ Fetch total bins for this route
        // Select all bins assigned to the route, getting only the bin_id
        $bins_in_route = supabase_fetch("route_bins", "?route_id=eq.$route_id&select=bin_id");
        $total_bins = count($bins_in_route);
        $bin_ids = array_column($bins_in_route, 'bin_id');

        // 3️⃣ Fetch completed bins (collection logs) by this collector
        $completed_count = 0;
        if (!empty($bin_ids)) {
            // Check logs for any bin in the list, collected by the current collector
            $completed_logs = supabase_fetch(
                "collection_logs",
                // Select only the bin_id, grouped by bin_id, to avoid counting duplicate collections of the same bin
                // Note: The IN operator syntax in the URL is critical here.
                "?collector_id=eq.$collector_id&bin_id=in.(" . implode(",", $bin_ids) . ")&select=bin_id&limit=" . $total_bins
            );
            // The logic assumes a bin is 'completed' if any log exists for it within this route context.
            // A more rigorous check might require filtering logs by assignment date.
            $completed_count = count(array_unique(array_column($completed_logs, 'bin_id')));
        } 

        // 4️⃣ Compute progress
        $progress = ($total_bins > 0) ? round(($completed_count / $total_bins) * 100) : 0;

        // 5️⃣ Determine status badge
        $status_badge = '';
        if ($total_bins === 0) {
            $status_badge = "<span class='badge bg-secondary'>No Bins</span>";
        } elseif ($completed_count == 0) {
            $status_badge = "<span class='badge bg-danger'>Not Started</span>";
        } elseif ($completed_count < $total_bins) {
            $status_badge = "<span class='badge bg-warning text-dark'>In Progress</span>";
        } else { // $completed_count === $total_bins
            $status_badge = "<span class='badge bg-success'>Completed</span>";
        }

        $routes_data[] = [
            'id' => $route_id,
            'name' => $route_name,
            'total_bins' => $total_bins,
            'completed' => $completed_count,
            'progress' => $progress,
            'status_badge' => $status_badge
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
                                    <div class="progress-bar <?= ($route['progress'] == 100) ? 'bg-success' : 'bg-primary' ?>" role="progressbar" 
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