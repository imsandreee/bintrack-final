<?php
// update_bin.php
require '../../auth/config.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['id'])) { http_response_code(400); echo json_encode(['message'=>'Missing id']); exit; }
$id = $data['id'];

$payload = [];
foreach (['bin_code','location_name','latitude','longitude','status'] as $f) {
    if (isset($data[$f])) $payload[$f] = $data[$f];
}
if (empty($payload)) { http_response_code(400); echo json_encode(['message'=>'Nothing to update']); exit; }

$url = SUPABASE_URL . '/rest/v1/bins?id=eq.' . urlencode($id);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
    'Prefer: return=representation'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$res = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($err) { http_response_code(500); echo json_encode(['message'=>$err]); exit; }
if ($httpcode >=200 && $httpcode <300) echo $res;
else { http_response_code($httpcode); echo $res; }
