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

// --- REQUIRED: Add this function to your global utility/config file (e.g., config.php) ---
if (!function_exists('supabase_action')) {
    function supabase_action($method, $table, $query_or_body, $user_jwt = null) {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY')) return ['error' => 'Supabase configuration missing.'];

        // Determine URL and headers
        $url = SUPABASE_URL . '/rest/v1/' . $table;
        $headers = [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . ($user_jwt ?? SUPABASE_KEY),
            'Accept: application/json'
        ];
        
        $ch = curl_init();

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_or_body);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } elseif ($method === 'PATCH' || $method === 'DELETE') {
            // $query_or_body holds the WHERE clause for DELETE/PATCH
            $url .= $query_or_body; 
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            if ($method === 'PATCH') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST['data_json'] ?? '{}'); // Use passed JSON body
                $headers[] = 'Content-Type: application/json';
            }
            // For both, add the Prefer header to return minimal response
            $headers[] = 'Prefer: return=minimal';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            return ['error' => 'Invalid method specified for supabase_action.'];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400 || $http_code === 0) {
            $error_details = json_decode($resp, true)['message'] ?? $resp;
            return ['error' => "API Error ($http_code): " . ($error_details ? htmlspecialchars($error_details) : 'Unknown error')];
        }
        // Success codes for CUD are typically 201 (POST) or 204 (PATCH/DELETE)
        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true];
        }
        
        return json_decode($resp, true);
    }
}

?>
