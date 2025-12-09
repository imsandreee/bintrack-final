<?php
session_start();
require 'config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    // Set an error message and redirect
    $_SESSION['message'] = "Email and password are required.";
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

// -------------------
// Step 1: Login user via Supabase
// -------------------
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SUPABASE_URL . '/auth/v1/token?grant_type=password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'password' => $password
]));
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['user']['id'])) {
    // Set an error message and redirect
    $error_msg = $data['error_description'] ?? 'Invalid credentials';
    $_SESSION['message'] = "Login failed: " . $error_msg;
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

$user_id = $data['user']['id'];
$access_token = $data['access_token'];

// -------------------
// Step 2: Fetch profile by user_id
// -------------------
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, SUPABASE_URL . '/rest/v1/profiles?select=*&id=eq.' . $user_id);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . $access_token
]);
$profile_resp = curl_exec($ch2);
curl_close($ch2);

$profiles = json_decode($profile_resp, true);
$profile = $profiles[0] ?? null;

if (!$profile) {
    // Set an error message and redirect
    $_SESSION['message'] = "Login failed: Profile not found for this user.";
    $_SESSION['message_type'] = 'danger';
    header("Location: index.php");
    exit;
}

// -------------------
// Step 3: Store session data
// -------------------
$_SESSION['user'] = [
    'id' => $profile['id'],
    'full_name' => $profile['full_name'],
    'role' => $profile['role'],
    // NOTE: 'email_address' is preferred for the profile table in the original code
    'email_address' => $profile['email_address'] 
];

// -------------------
// Step 4: Redirect based on role
// -------------------
// No need for a session message on success, as they are redirected away from index.php
switch ($profile['role']) {
    case 'admin':
        header("Location: ../admin/dashboard.php");
        exit;
    case 'collector':
        header("Location: ../collector/dashboard.php");
        exit;
    case 'citizen':
    default:
        header("Location: ../citizen/dashboard.php");
        exit;
}
?>