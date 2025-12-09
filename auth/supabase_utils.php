<?php
// Ensure this file is included *after* config.php where SUPABASE_KEY and SUPABASE_URL are defined.

if (!function_exists('supabase_api_call')) {
    function supabase_api_call($method, $table, $payload = [], $query = '') {
        if (!defined('SUPABASE_KEY') || !defined('SUPABASE_URL')) {
            error_log("Supabase constants not defined.");
            return ['success' => false, 'message' => 'Supabase API configuration missing.'];
        }
        
        $headers = [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
            'Prefer: return=representation', 
        ];

        $url = SUPABASE_URL . '/rest/v1/' . $table . $query;
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($payload) && in_array($method, ['POST', 'PATCH'])) {
            $json_payload = json_encode($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        }
        
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $is_success = ($http_code >= 200 && $http_code < 300);
        $response_data = json_decode($resp, true);

        if (!$is_success) {
            $error_detail = $response_data['details'] ?? $response_data['message'] ?? 'No detail available.';
            $error_message = "API call failed with HTTP code $http_code: " . $error_detail;
            
            error_log("Supabase API $method Error ($http_code) on $table: " . $resp);
            
            return [
                'success' => false, 
                'message' => $error_message, 
                'data' => $response_data
            ];
        }

        return ['success' => true, 'data' => $response_data];
    }
}

if (!function_exists('supabase_fetch')) {
    function supabase_fetch($table, $query = '') {
        $result = supabase_api_call('GET', $table, [], $query);
        return $result['success'] ? $result['data'] : [];
    }
}