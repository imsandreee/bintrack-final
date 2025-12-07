<?php
require 'config.php';

// Get POST data
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'citizen';

if (!$full_name || !$email || !$password) {
    die("All fields are required.");
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

// Decode the response
$data = json_decode($response, true);

// Debugging: show Supabase response
// Uncomment if you need to see the full response
// var_dump($httpcode, $data);

if ($httpcode != 200 && $httpcode != 201) {
    die("Signup failed: " . ($data['msg'] ?? $data['message'] ?? 'Unknown error'));
}

// User ID returned by Supabase
$user_id = $data['id'] ?? null;
if (!$user_id) {
    die("Signup failed: Could not get user ID");
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
    'Prefer: return=representation'
]);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
    'id' => $user_id,
    'full_name' => $full_name,
    'role' => $role,
    'email_address' => $email
]));

// Add header for upsert
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
    'Prefer: resolution=merge-duplicates,return=representation'  // <-- UPSERT
]);


$profile_resp = curl_exec($ch2);
$profile_http = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

// Decode profile response
$profile_data = json_decode($profile_resp, true);

if ($profile_http != 201 && $profile_http != 200) {
    die("Profile insertion failed: " . ($profile_data['msg'] ?? $profile_data['message'] ?? 'Unknown error'));
}

echo "Signup successful! You can <a href='index.html'>login now</a>.";
?>
