<?php
require_once '../../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
    exit;
}

$route_id = $_POST['id'] ?? '';

if (!$route_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing route ID']);
    exit;
}

try {
    // 1️⃣ Delete collector assignments for this route
    supabase_delete("route_assignments", "?route_id=eq.$route_id");

    // 2️⃣ Delete all bin assignments
    supabase_delete("route_bins", "?route_id=eq.$route_id");

    // 3️⃣ Delete the route itself
    supabase_delete("collection_routes", "?id=eq.$route_id");

    echo json_encode(['status' => 'success', 'msg' => 'Route deleted successfully']);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Failed to delete route', 'error' => $e->getMessage()]);
    exit;
}
