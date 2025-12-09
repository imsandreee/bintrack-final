<?php
// collector/collection_logs.php

require_once '../auth/config.php';
session_start();

// --- Collector Authorization Check ---
$collector_id = $_SESSION['user']['id'] ?? '';
$user_jwt = $_SESSION['access_token'] ?? null; // Assuming JWT is stored here
if (!$collector_id) {
    header('Location: /login.php'); 
    exit;
}

// --- Supabase Fetch Function (Updated for Secure JWT Access) ---
if (!function_exists('supabase_fetch')) {
    function supabase_fetch($table, $query = '') {
        // Retrieve key and token inside the function scope if they aren't global
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY')) return ['error' => 'Supabase configuration missing.'];

        // Use the user's JWT for the Bearer token for RLS to work. Fallback to API key if JWT is missing.
        $user_jwt = $_SESSION['access_token'] ?? SUPABASE_KEY;
        
        $url = SUPABASE_URL . '/rest/v1/' . $table . $query;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . $user_jwt, // **CRITICAL FIX: Use $user_jwt here**
            'Accept: application/json'
        ]);
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
            // Include message from Supabase response if available (e.g., RLS error)
            $api_error_message = is_array($data) && isset($data['message']) ? $data['message'] : 'No details available.';
            return ['error' => "Supabase API Error ($http_code): $api_error_message"];
        }

        return is_array($data) ? $data : [];
    }
}

// --- 1. Define Timeframe (Last 7 Days) ---
// Using Z for UTC timezone suffix as required by PostgREST timestamp filtering
$seven_days_ago = date('Y-m-d\TH:i:s\Z', strtotime('-7 days')); 

// --- 2. Build safe query ---
$query_params = [
    // RLS will typically enforce this filter, but sending it prevents unnecessary data transfer.
    'collector_id' => "eq.$collector_id", 
    'collected_at' => "gte.$seven_days_ago",
    'select'       => 'id,collected_at,weight_collected_kg,remarks,bins(bin_code,location_name)',
    'order'        => 'collected_at.desc'
];

$query = '?' . http_build_query($query_params);

// --- 3. Fetch Collection Logs ---
$logs_data = supabase_fetch("collection_logs", $query);

// --- 4. Handle errors ---
if (!is_array($logs_data) || isset($logs_data['error'])) {
    $error_message = $logs_data['error'] ?? 'Could not retrieve logs from the database. (Check RLS/Network)';
    $logs_data = [];
} else {
    $error_message = '';
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
</head>
<body>

<?php include '../includes/collector/navbar.php'; ?>

<div class="container-fluid p-0">
<section id="logsPage" class="page-content">
<h1 class="mb-4 fw-bold">Collection Logs (Last 7 Days)</h1>

<div class="custom-card p-4 bg-white shadow-sm">
    

    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Database Error:</strong> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light-green">
                <tr>
                    <th>Log ID</th>
                    <th>Bin Code / Location</th>
                    <th>Date & Time</th>
                    <th>Weight Collected</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs_data)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-info-circle-fill me-2"></i> No collection logs found for the last 7 days.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs_data as $log):
                        $bin_info = $log['bins'] ?? ['bin_code' => 'N/A', 'location_name' => 'Unknown Location'];
                        $log_id_display = "L-" . str_pad($log['id'], 5, '0', STR_PAD_LEFT);
                    ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($log_id_display) ?></td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($bin_info['bin_code']) ?></span>
                                <small class="d-block text-muted"><?= htmlspecialchars($bin_info['location_name']) ?></small>
                            </td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($log['collected_at']))) ?></td>
                            <td><?= htmlspecialchars(round($log['weight_collected_kg'] ?? 0, 2)) ?> kg</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td><?= htmlspecialchars($log['remarks'] ?? 'No remarks.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/collector.js"></script>
</body>
</html>