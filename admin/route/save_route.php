<?php
// D:\xammp\htdocs\project\admin\route\save_route.php

require_once '../../auth/config.php';
session_start();

// Ensure this is an AJAX POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
    http_response_code(405);
    echo "Invalid request method or missing data.";
    exit;
}

// Collector's JWT is needed for RLS authentication
$user_jwt = $_SESSION['access_token'] ?? SUPABASE_KEY;

$route_name = trim($_POST['route_name'] ?? '');
$driver_id = $_POST['driver'] ?? null;
$bin_ids = $_POST['bin_ids'] ?? []; // Array of UUIDs
$route_status = $_POST['status'] ?? 'pending';

if (empty($route_name) || empty($driver_id)) {
    http_response_code(400);
    echo "Error: Route name and Collector must be provided.";
    exit;
}

// 1. Prepare data for Supabase
$data = [
    'route_name' => $route_name,
    'driver' => $driver_id,
    'bin_ids' => $bin_ids, // Supabase/PostgREST can handle array input directly
    'total_bins' => count($bin_ids),
    'status' => $route_status
];

$json_data = json_encode($data);

// 2. Execute POST request
$result = supabase_action(
    'POST', 
    'collection_route', 
    $json_data, 
    $user_jwt
);

// 3. Handle response
if (isset($result['error'])) {
    http_response_code(500);
    // Display error message from the database/API
    echo "Error saving route: " . $result['error'];
} else {
    // Success code 201 is mapped to 'success' in supabase_action
    echo "success"; 
}
?>