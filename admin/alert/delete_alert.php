<?php
require '../../auth/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

$ch = curl_init(SUPABASE_URL . "/rest/v1/bin_alerts?id=eq.$id");
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => "DELETE",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=minimal'
    ]
]);

curl_exec($ch);
curl_close($ch);

echo json_encode(['success' => true]);
