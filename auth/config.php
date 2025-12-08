<?php
define('SUPABASE_URL', 'https://drogypndtmqhpohoedzl.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRyb2d5cG5kdG1xaHBvaG9lZHpsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2NDIwOTc4NiwiZXhwIjoyMDc5Nzg1Nzg2fQ.7dOEhqDnQQwzc6uwwQPhKkY7hbfdqLow-h6rYtqKnQA'); // service key for admin actions


function supabase_fetch($table, $query = '') {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $table . $query);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    return json_decode($res, true);
}

function supabase_update($table, $data, $query) {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $table . $query);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    return json_decode($res, true);
}

function supabase_delete($table, $query) {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $table . $query);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    return json_decode($res, true);
}

function supabase_insert($table, $data) {
    $url = SUPABASE_URL . "/rest/v1/" . $table;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        die(curl_error($ch));
    }

    curl_close($ch);
    return json_decode($response, true);
}

?>
