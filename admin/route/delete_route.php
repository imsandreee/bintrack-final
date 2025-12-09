<?php
// D:\xammp\htdocs\project\admin\route\delete_route.php

require_once '../../auth/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(405);
    echo "Invalid request method or missing ID.";
    exit;
}

$user_jwt = $_SESSION['access_token'] ?? SUPABASE_KEY;
$route_id = $_POST['id'];

// 1. Execute DELETE request
// Use a query parameter to specify the row to delete
$query_params = "?id=eq.$route_id";

$result = supabase_action(
    'DELETE', 
    'collection_route', 
    $query_params, 
    $user_jwt
);

// 2. Handle response
if (isset($result['error'])) {
    http_response_code(500);
    echo "Error deleting route: " . $result['error'];
} else {
    // Success code 204 (No Content) is mapped to 'success' in supabase_action
    echo "success";
}
?>