<?php
// create_bin.php
require '../../auth/config.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { http_response_code(400); echo json_encode(['message'=>'Invalid JSON']); exit; }

// Minimal validation
$required = ['bin_code','location_name','latitude','longitude'];
foreach ($required as $f) {
    if (!isset($data[$f]) || $data[$f] === '') {
        http_response_code(400); echo json_encode(['message'=>"Missing $f"]); exit;
    }
}

// Build payload
$payload = [
    'bin_code' => $data['bin_code'],
    'location_name' => $data['location_name'],
    'latitude' => (float)$data['latitude'],
    'longitude' => (float)$data['longitude'],
    'status' => $data['status'] ?? 'active'
];

$url = SUPABASE_URL . '/rest/v1/bins';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
    'Prefer: return=representation' // return created row
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$res = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($err) { http_response_code(500); echo json_encode(['message'=>$err]); exit; }
if ($httpcode >=200 && $httpcode <300) {
    echo $res; // Supabase returns created record(s)
} else {
    http_response_code($httpcode);
    echo $res;
}
