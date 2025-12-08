<?php
require_once '../../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
    exit;
}

// Get POST data
$route_name   = trim($_POST['route_name'] ?? '');
$collector_id = $_POST['collector_id'] ?? '';
$bin_ids      = $_POST['bin_ids'] ?? [];

if (!$route_name || !$collector_id || empty($bin_ids)) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing required fields']);
    exit;
}

//
// 1️⃣ Check for duplicate route name
//
$check = supabase_fetch("collection_routes", "?route_name=eq." . urlencode($route_name));
if ($check && count($check) > 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Route already exists']);
    exit;
}

//
// 2️⃣ Create new route
//
$new_route = supabase_insert("collection_routes", [
    "route_name" => $route_name
]);

if (!isset($new_route[0]['id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Failed to create route']);
    exit;
}

$route_id = $new_route[0]['id'];

//
// 3️⃣ Assign bins to route_bins
//
foreach ($bin_ids as $bin_id) {
    supabase_insert("route_bins", [
        "route_id" => $route_id,
        "bin_id"   => $bin_id
    ]);
}

//
// 4️⃣ Assign collector to route (THIS IS THE CORRECT WAY)
//
$assign = supabase_insert("route_assignments", [
    "route_id"      => $route_id,
    "collector_id"  => $collector_id
]);

if (!isset($assign[0]['id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Collector assignment failed']);
    exit;
}

//
// 5️⃣ Done
//
echo json_encode(['status' => 'success', 'msg' => 'Route created & assigned successfully']);
exit;
