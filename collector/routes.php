<?php
require_once '../auth/config.php';
session_start();

// Logged-in collector
$collector_id = $_SESSION['user']['id'] ?? '';
if (!$collector_id) {
    echo "Unauthorized";
    exit;
}

// 1️⃣ Fetch all route assignments for this collector
$assigned_routes = supabase_fetch(
    "route_assignments",
    "?collector_id=eq.$collector_id&select=route_id,assigned_at,collection_routes(id,route_name)"
);

// Initialize arrays
$routes_data = [];

if ($assigned_routes) {
    foreach ($assigned_routes as $assignment) {
        $route_id   = $assignment['route_id'];
        $route_name = $assignment['collection_routes']['route_name'];

        // 2️⃣ Fetch total bins for this route
        $bins = supabase_fetch("route_bins", "?route_id=eq.$route_id&select=bin_id");
        $total_bins = count($bins);
        $bin_ids = array_column($bins, 'bin_id');

        // 3️⃣ Fetch completed bins by this collector
        if (!empty($bin_ids)) {
            $completed_logs = supabase_fetch(
                "collection_logs",
                "?collector_id=eq.$collector_id&bin_id=in.(" . implode(",", $bin_ids) . ")"
            );
            $completed_count = count($completed_logs);
        } else {
            $completed_count = 0;
        }

        // 4️⃣ Compute progress
        $progress = ($total_bins > 0) ? ($completed_count / $total_bins) * 100 : 0;

        // 5️⃣ Determine status
        if ($completed_count == 0) {
            $status_badge = "<span class='badge bg-danger'>Not Started</span>";
        } elseif ($completed_count < $total_bins) {
            $status_badge = "<span class='badge bg-warning text-dark'>In Progress</span>";
        } else {
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
<h1 class="mb-4 fw-bold">Assigned Collection Routes</h1>

<div class="custom-card p-4 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Your Active Routes</h5>
        <button class="btn btn-outline-secondary" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light-green">
                <tr>
                    <th>Route Name</th>
                    <th>Total Bins</th>
                    <th>Completed</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes_data)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No assigned routes found.</td></tr>
                <?php else: ?>
                    <?php foreach ($routes_data as $route): ?>
                        <tr onclick="goToRouteDetails('<?= $route['id'] ?>')">
                            <td class="fw-bold"><?= htmlspecialchars($route['name']) ?></td>
                            <td><?= $route['total_bins'] ?></td>
                            <td><?= $route['completed'] ?> / <?= $route['total_bins'] ?></td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar progress-bar-green" role="progressbar" 
                                         style="width: <?= $route['progress'] ?>%" 
                                         aria-valuenow="<?= $route['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </td>
                            <td><?= $route['status_badge'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="goToRouteDetails('<?= $route['id'] ?>')">
                                    View Route
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
    window.location.href = "routedetail.php?route_id=" + routeId;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/collector.js"></script>
</body>
</html>
