<?php
// delete_bin.php
require '../../auth/config.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['id'])) { http_response_code(400); echo json_encode(['message'=>'Missing id']); exit; }

$id = $data['id'];
// caution: this will delete bin and cascade delete sensors & readings per your schema ON DELETE CASCADE
$url = SUPABASE_URL . '/rest/v1/bins?id=eq.' . urlencode($id);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Prefer: return=representation'
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($err) { http_response_code(500); echo json_encode(['message'=>$err]); exit; }
if ($httpcode >=200 && $httpcode <300) echo $res;
else { http_response_code($httpcode); echo $res; }
