<?php
// D:\xammp\htdocs\project\admin\route\fetch_route.php

require_once '../../auth/config.php';
session_start();

// Ensure supabase_fetch function is defined (must be in config.php or defined earlier)
if (!function_exists('supabase_fetch')) {
    http_response_code(500);
    echo json_encode(['error' => 'Supabase fetch function missing.']);
    exit;
}

header('Content-Type: application/json');

$route_id = $_GET['id'] ?? null;
if (empty($route_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing route ID.']);
    exit;
}

// 1. Fetch the single route row
// We only need the primary route details (including driver ID and bin_ids array)
$query = "?id=eq.$route_id&select=*";
$result = supabase_fetch("collection_route", $query);

if (!is_array($result) || isset($result['error']) || empty($result)) {
    // Error handling, ensuring we catch both API errors and empty results
    $error = $result['error'] ?? 'Route not found or database error.';
    http_response_code(404);
    echo json_encode(['error' => $error]);
    exit;
}

// 2. Extract and format the single route object
$route_data = $result[0];

// Prepare the final response structure
$response = [
    'route' => $route_data,
    // The previous design expected 'assignment' and 'bins' as separate arrays.
    // We map the new schema to the old names for compatibility with the frontend JS logic.
    'assignment' => ['driver' => $route_data['driver']],
    // The frontend JS for edit mode needs a list of bin IDs.
    // We provide the raw bin_ids array.
    'bins' => array_map(function($bin_id) {
        return ['bin_id' => $bin_id];
    }, $route_data['bin_ids'] ?? [])
];

echo json_encode($response);
?>