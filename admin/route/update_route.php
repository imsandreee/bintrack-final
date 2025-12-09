<?php
// D:\xammp\htdocs\project\admin\route\update_route.php

require_once '../../auth/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
    http_response_code(405);
    echo "Invalid request method or missing data.";
    exit;
}

$user_jwt = $_SESSION['access_token'] ?? SUPABASE_KEY;

$route_id = $_POST['route_id'] ?? null;
$route_name = trim($_POST['route_name'] ?? '');
$driver_id = $_POST['driver'] ?? null;
$bin_ids = $_POST['bin_ids'] ?? [];
$route_status = $_POST['status'] ?? 'pending';

if (empty($route_id) || empty($route_name) || empty($driver_id)) {
    http_response_code(400);
    echo "Error: Route ID, name, and Collector must be provided.";
    exit;
}

// 1. Prepare data for Supabase
$data = [
    'route_name' => $route_name,
    'driver' => $driver_id,
    'bin_ids' => $bin_ids,
    'total_bins' => count($bin_ids),
    'status' => $route_status,
    'updated_at' => date('Y-m-d\TH:i:s\Z') // Optional: update timestamp
];

$json_data = json_encode($data);

// 2. Execute PATCH request
// We use a query parameter to specify the row to update (eq. equals)
$query_params = "?id=eq.$route_id";

// The `supabase_action` function is modified to take data_json in POST data
$_POST['data_json'] = $json_data; 

$result = supabase_action(
    'PATCH', 
    'collection_route', 
    $query_params, 
    $user_jwt
);

// 3. Handle response
if (isset($result['error'])) {
    http_response_code(500);
    echo "Error updating route: " . $result['error'];
} else {
    // Success code 204 (No Content) is mapped to 'success' in supabase_action
    echo "success"; 
}
?>