<?php
define('SUPABASE_URL', 'https://drogypndtmqhpohoedzl.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRyb2d5cG5kdG1xaHBvaG9lZHpsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2NDIwOTc4NiwiZXhwIjoyMDc5Nzg1Nzg2fQ.7dOEhqDnQQwzc6uwwQPhKkY7hbfdqLow-h6rYtqKnQA'); // service key for admin actions


function supabase_fetch($table, $params = "") {
    $url = SUPABASE_URL . "/rest/v1/" . $table . $params;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: " . SUPABASE_KEY,
            "Authorization: Bearer " . SUPABASE_KEY,
            "Content-Type: application/json",
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
