<?php
// list_bins.php
require '../../auth/config.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

$select = 'select=id,bin_code,location_name,latitude,longitude,status,installation_date,last_communication';
$path = 'bins?' . $select . '&order=installation_date.desc';

if ($q) {
    // simple ilike filter on code or location (supabase supports ilike)
    $qesc = rawurlencode('%' . $q . '%');
    $path = "bins?select=id,bin_code,location_name,latitude,longitude,status,installation_date,last_communication&or=(bin_code.ilike.*$qesc*,location_name.ilike.*$qesc*)&order=installation_date.desc";
}

$url = SUPABASE_URL . '/rest/v1/' . $path;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Accept: application/json'
]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
if ($err) { http_response_code(500); echo json_encode(['message'=>$err]); exit; }
echo $res;
