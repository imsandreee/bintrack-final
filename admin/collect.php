<?php
// admin/collection_logs.php

require_once '../auth/config.php';
session_start();

// --- Admin Authorization Check ---
// Assuming Admin role is checked elsewhere or is the implied audience for this file.
// We must ensure SUPABASE_KEY is available via config.php.

// --- Supabase Fetch Function (Re-defined for modularity and secure access) ---
// This robust definition is kept here in case '../auth/config.php' doesn't provide it,
// ensuring the script runs. Note: Admin requests often use the general SUPABASE_KEY.

if (!function_exists('supabase_fetch')) {
    function supabase_fetch($table, $query = '', $method = 'GET', $data = []) {
        // Use the API key as the fallback token for Admin general data queries
        $user_jwt = $_SESSION['access_token'] ?? (defined('SUPABASE_KEY') ? SUPABASE_KEY : null);

        if (!defined('SUPABASE_URL') || !$user_jwt) {
             return ['error' => 'Supabase configuration or key missing.'];
        }
        
        $url = SUPABASE_URL . '/rest/v1/' . $table . $query;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . $user_jwt,
            'Accept: application/json'
        ];
        if ($method === 'POST' || $method === 'PATCH') {
            $headers[] = 'Content-Type: application/json';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'cURL Error: ' . curl_error($ch);
            curl_close($ch);
            return ['error' => $error];
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($resp, true);

        if ($http_code >= 400) {
            $api_error_message = is_array($data) && isset($data['message']) ? $data['message'] : 'No details available.';
            return ['error' => "Supabase API Error ($http_code): $api_error_message"];
        }

        return is_array($data) ? $data : [];
    }
}

// --- 1. Fetch ALL Collection Logs (Admin Scope) ---
// The SELECT statement is expanded to get the collector's name and bin code,
// as required by the Admin HTML table structure.
$query = "?select=*,bins(bin_code),profiles(full_name)&order=collected_at.desc";

// Fetch data using the robust function
$collection_logs = supabase_fetch("collection_logs", $query);

// --- 2. Fetch Supporting Data (Needed for other parts of the original Admin page) ---
// Note: These now use the robust supabase_fetch definition.
$collectors_result = supabase_fetch("profiles?select=id,full_name&role=eq.collector");
$bins_result = supabase_fetch("bins?select=id,bin_code,location_name,status");
$routes_result = supabase_fetch("collection_route?select=id,route_name,created_at,route_bins(count)"); // Changed from collection_routes to match common table naming

// Extract actual data or set defaults/errors
$collectors = is_array($collectors_result) && !isset($collectors_result['error']) ? $collectors_result : [];
$bins = is_array($bins_result) && !isset($bins_result['error']) ? $bins_result : [];
$routes = is_array($routes_result) && !isset($routes_result['error']) ? $routes_result : [];

// Check for fetching error on the main log data
$error_message = '';
if (!is_array($collection_logs) || isset($collection_logs['error'])) {
    $error_message = $collection_logs['error'] ?? 'Could not retrieve collection logs.';
    $collection_logs = [];
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BinTrack - Collection Logs</title> 
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<div class="d-flex" id="wrapper">
<?php include '../includes/admin/sidebar.php'; ?>

<div id="page-content-wrapper" class="w-100">
<?php include '../includes/admin/topnavbar.php'; ?>

<div class="page-content p-4">
    
    <div id="collections" class="page-content" style="display:block;">
        <h1 class="mb-4">Collection Logs</h1>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div></div>
            <div class="input-group w-auto">
                <input type="text" class="form-control" placeholder="Search collections...">
                <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="card p-4 shadow-sm border-0">
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Database Error:</strong> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Bin Code</th>
                            <th>Collector</th>
                            <th>Weight Collected (kg)</th>
                            <th>Collected At</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (is_array($collection_logs) && !empty($collection_logs)): ?>
                            <?php foreach ($collection_logs as $log): 
                                // Data from Supabase is fetched as an array when using json_decode(..., true)
                                $bin_code = $log['bins']['bin_code'] ?? 'N/A';
                                $collector_name = $log['profiles']['full_name'] ?? 'N/A';
                                $weight = $log['weight_collected_kg'] ?? 0;
                                $collected_at = $log['collected_at'];
                                $remarks = $log['remarks'] ?? '-';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bin_code); ?></td>
                                    <td><?php echo htmlspecialchars($collector_name); ?></td>
                                    <td><?php echo htmlspecialchars(round($weight, 2)); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($collected_at))); ?></td>
                                    <td><?php echo htmlspecialchars($remarks); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No collection logs found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

// Reset for "Add"
function resetModal(){
    document.getElementById('modalTitle').innerText = "Create Route";
    document.getElementById('routeForm').reset();
    document.getElementById('route_id').value = '';
}

// EDIT
document.querySelectorAll('.editRouteBtn').forEach(btn => {
btn.addEventListener('click', async function(){
    const id = this.dataset.id;
    document.getElementById('modalTitle').innerText = "Edit Route";

    // Assuming fetch_route.php uses a similar robust fetch function
    const res = await fetch('fetch_route.php?id=' + id);
    const data = await res.json();

    document.getElementById('route_id').value = data.route.id;
    document.getElementById('routeName').value = data.route.route_name;
    document.getElementById('routeCollector').value = data.route.collector_id;

    const assigned = data.bins.map(b => b.bin_id);

    document.querySelectorAll('#assignedBins option').forEach(option => {
        option.selected = assigned.includes(option.value);
    });
});
});


// DELETE
document.querySelectorAll('.deleteRouteBtn').forEach(btn => {
btn.addEventListener('click', async function(){
    if(!confirm('Delete this route and its assignments?')) return;

    const res = await fetch('delete_route.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + this.dataset.id
    });

    const msg = await res.text();

    if(msg === "success") {
        location.reload();
    } else {
        alert(msg);
    }
});
});


// SAVE (ADD / UPDATE)
document.getElementById("routeForm").addEventListener("submit", async function(e){
    e.preventDefault();

    const formData = new FormData(this);
    const id = document.getElementById("route_id").value;

    const url = id ? 'update_route.php' : 'save_route.php';

    const res = await fetch(url, {
        method: "POST",
        body: formData
    });

    const msg = await res.text();

    if(msg === "success"){
        location.reload();
    }
    else{
        alert(msg);
    }
});
</script>
</body>
</html>