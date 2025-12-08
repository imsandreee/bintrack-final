<?php
require_once '../../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
    exit;
}

$route_id     = $_POST['route_id'] ?? '';
$route_name   = trim($_POST['route_name'] ?? '');
$collector_id = $_POST['collector_id'] ?? '';
$bin_ids      = $_POST['bin_ids'] ?? [];

if (!$route_id || !$route_name || empty($bin_ids)) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing required fields']);
    exit;
}

//
// 1️⃣ Update route name
//
supabase_update(
    "collection_routes",
    ["route_name" => $route_name],
    "?id=eq.$route_id"
);

//
// 2️⃣ Remove old bin assignments
//
supabase_delete("route_bins", "?route_id=eq.$route_id");

//
// 3️⃣ Insert new bin assignments
//
foreach ($bin_ids as $bin_id) {
    supabase_insert("route_bins", [
        "route_id" => $route_id,
        "bin_id"   => $bin_id
    ]);
}

//
// 4️⃣ Update collector assignment properly
//    (Remove old assignment → Insert new one)
//
supabase_delete("route_assignments", "?route_id=eq.$route_id");

$assign = supabase_insert("route_assignments", [
    "route_id"     => $route_id,
    "collector_id" => $collector_id
]);

if (!isset($assign[0]['id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Failed to assign collector']);
    exit;
}

//
// 5️⃣ Done
//
echo json_encode(['status' => 'success', 'msg' => 'Route updated successfully']);
exit;
