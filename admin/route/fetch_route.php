<?php
require_once '../../auth/config.php';

if (!isset($_GET['id'])) exit;

$route_id = $_GET['id'];

$route = supabase_fetch("collection_routes?id=eq.$route_id");
$route_bins = supabase_fetch("route_bins?route_id=eq.$route_id");


echo json_encode([
    "route" => $route[0] ?? null,
    "bins"  => $route_bins ?? []
]);
