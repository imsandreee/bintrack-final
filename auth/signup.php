<?php
session_start();
require 'config.php';

// Get POST data
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'citizen';

if (!$full_name || !$email || !$password) {
    // Set an error message and redirect
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

// -------------------
// Step 1: Create user in Supabase Auth
// -------------------
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SUPABASE_URL . '/auth/v1/admin/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'password' => $password,
    'email_confirm' => true
]));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpcode != 200 && $httpcode != 201) {
    // Set an error message and redirect
    $error_msg = $data['msg'] ?? $data['message'] ?? 'Unknown error';
    $_SESSION['message'] = "Signup failed: " . $error_msg;
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

$user_id = $data['id'] ?? null;
if (!$user_id) {
    // Set an error message and redirect
    $_SESSION['message'] = "Signup failed: Could not get user ID.";
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

// -------------------
// Step 2: Insert into profiles table
// -------------------
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, SUPABASE_URL . '/rest/v1/profiles');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
    'Prefer: resolution=merge-duplicates,return=representation' // UPSERT
]);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
    'id' => $user_id,
    'full_name' => $full_name,
    'role' => $role,
    'email_address' => $email
]));

$profile_resp = curl_exec($ch2);
$profile_http = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

$profile_data = json_decode($profile_resp, true);

if ($profile_http != 201 && $profile_http != 200) {
    // Set an error message and redirect
    $error_msg = $profile_data['msg'] ?? $profile_data['message'] ?? 'Unknown error';
    $_SESSION['message'] = "Profile insertion failed: " . $error_msg;
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

// -------------------
// Step 3: Success message and redirect
// -------------------
$_SESSION['message'] = "Signup successful! You can now login.";
$_SESSION['message_type'] = 'success';
header("Location: index.php");
exit;
?>