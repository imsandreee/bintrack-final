<?php
require '../../auth/config.php';
header('Content-Type: application/json');

$in = json_decode(file_get_contents('php://input'), true);
if(!$in || empty($in['id'])) exit;

$id = $in['id'];

$url = SUPABASE_URL."/rest/v1/sensors?id=eq.$id";

$ch = curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'DELETE',
    CURLOPT_HTTPHEADER => [
        'apikey: '.SUPABASE_KEY,
        'Authorization: Bearer '.SUPABASE_KEY
    ]
]);
curl_exec($ch);
curl_close($ch);

echo json_encode(['success'=>true]);
