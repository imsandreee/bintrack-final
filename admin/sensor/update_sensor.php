<?php
require '../../auth/config.php';
header('Content-Type: application/json');

$in = json_decode(file_get_contents('php://input'), true);
if(!$in) exit;

$id = $in['id'] ?? null;
$column = $in['column'] ?? null;
$value = $in['value'] ?? null;

$allowed = ['ultrasonic_enabled','load_cell_enabled','gps_enabled'];
if(!in_array($column,$allowed)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid column']);
    exit;
}

$url = SUPABASE_URL."/rest/v1/sensors?id=eq.$id";

$payload = json_encode([$column => $value]);

$ch = curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'apikey: '.SUPABASE_KEY,
        'Authorization: Bearer '.SUPABASE_KEY,
        'Content-Type: application/json'
    ]
]);
$res = curl_exec($ch);
curl_close($ch);

echo json_encode(['success'=>true]);
