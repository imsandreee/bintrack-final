<?php
// D:\xammp\htdocs\project\collector\collector_actions.php

// Ensure paths are correct for your structure
require_once '../auth/config.php';
session_start();
header('Content-Type: application/json');

// Helper function for sending consistent JSON responses
function send_response($success, $message = '', $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// --- 1. Authorization and Initial Validation ---
$collector_id = $_SESSION['user']['id'] ?? '';
$action = $_POST['action'] ?? '';

if (!$collector_id) {
    send_response(false, "Unauthorized access. Collector ID missing.");
}

if (!in_array($action, ['collect', 'report', 'update_route_status'])) {
    send_response(false, "Invalid action specified.");
}

// --- Supabase API Utility Functions (MUST be defined for the script to work) ---

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
// --- END Supabase API Utility Functions ---


/**
 * Helper function to handle the logic for completing a route,
 * including marking pending bins as 'skipped'.
 * NOTE: This function is called recursively from 'collect' if all bins are done.
 *
 * @param int $route_id
 * @param int $collector_id
 * @param string $current_time
 * @return array
 */
function handle_route_completion($route_id, $collector_id, $current_time) {
    // 1. Update the route status
    $update_payload = ['status' => 'completed', 'completed_at' => $current_time];
    $query_filter = "?id=eq.$route_id&driver=eq.$collector_id&select=bin_ids";

    $result = supabase_api_call('PATCH', 'collection_route', $update_payload, $query_filter);
    
    $message = "Route status updated to **Completed**.";
    
    if (!$result['success'] || empty($result['data'])) {
        return [
            'success' => false, 
            'message' => $result['message'] ?? "Route not found or unauthorized to update."
        ];
    }

    // 2. Handle Completion Logic (Skip Pending Bins)
    $route_data = $result['data'][0];
    $all_bin_ids = $route_data['bin_ids'] ?? [];
    $skip_success_count = 0;
            
    if (!empty($all_bin_ids)) {
        $bin_ids_list = implode(',', $all_bin_ids);

        // Fetch logs for bins on this route, for this collector
        // We only care about logs that happened *during* this route (based on $route_id)
        $collected_query = "?bin_id=in.($bin_ids_list)&collector_id=eq.$collector_id&route_id=eq.$route_id&select=bin_id,status";
        $collected_logs = supabase_fetch('collection_logs', $collected_query);

        // Identify bins that have already been collected or skipped
        $collected_or_skipped_bin_ids = array_column(array_filter($collected_logs, function($log) {
            return isset($log['status']) && in_array($log['status'], ['collected', 'skipped']);
        }), 'bin_id');

        // The difference is the set of bins that need to be marked as skipped
        $skipped_bin_ids = array_diff($all_bin_ids, $collected_or_skipped_bin_ids);
        
        if (!empty($skipped_bin_ids)) {
            foreach ($skipped_bin_ids as $binId) {
                $skip_payload = [
                    'bin_id' => $binId,
                    'collector_id' => $collector_id,
                    'route_id' => $route_id, 
                    'collected_at' => $current_time, 
                    'status' => 'skipped'
                ];
                $skip_result = supabase_api_call('POST', 'collection_logs', $skip_payload);
                if ($skip_result['success']) {
                    $skip_success_count++;
                } else {
                    error_log("Failed to log skip for bin $binId: " . ($skip_result['message'] ?? 'Unknown error'));
                }
            }
        }
        
        if ($skip_success_count > 0) {
            $message .= " **($skip_success_count bins marked as skipped)**.";
        }
    }
    
    return ['success' => true, 'message' => $message];
}

// --- 2. Action Handlers ---

switch ($action) {
    
    case 'collect':
        $bin_id = $_POST['bin_id'] ?? '';
        $route_id = $_POST['route_id'] ?? ''; 
        
        // Optional fields
        $weight_collected = $_POST['weight_collected_kg'] ?? null;
        $remarks = $_POST['remarks'] ?? null;
        $current_time = date('Y-m-d H:i:sP');
        
        if (!$bin_id || !$route_id) {
            send_response(false, "Bin ID and Route ID are required for collection.");
        }

        // --- Data Type Casting ---
        $route_id = (int)$route_id; 

        // Payload for the collection_logs table
        $payload = [
            'bin_id' => $bin_id,
            'collector_id' => $collector_id,
            'route_id' => $route_id, 
            'collected_at' => $current_time,
            'status' => 'collected',
        ];

        if ($weight_collected !== null) {
            $payload['weight_collected_kg'] = (float)$weight_collected;
        }
        if ($remarks !== null) {
            $payload['remarks'] = $remarks;
        }

        $result = supabase_api_call('POST', 'collection_logs', $payload);

        if ($result['success']) {
            $response_message = "Bin collection logged successfully.";

            // --- NEW LOGIC: Check for Automatic Completion ---
            
            // 1. Fetch the route details (especially the list of bins)
            $route_query = "?id=eq.$route_id&driver=eq.$collector_id&select=bin_ids,status";
            $route_check_result = supabase_fetch('collection_route', $route_query);
            
            if (!empty($route_check_result) && $route_check_result[0]['status'] === 'active') {
                $route_info = $route_check_result[0];
                $all_bin_ids = $route_info['bin_ids'] ?? [];
                $total_bins = count($all_bin_ids);

                if ($total_bins > 0) {
                    // 2. Count collection/skipped logs for this route/collector
                    $log_count_query = "?route_id=eq.$route_id&collector_id=eq.$collector_id&status=in.(collected,skipped)&count=exact";
                    $log_result = supabase_api_call('GET', 'collection_logs', [], $log_count_query);
                    
                    if ($log_result['success'] && isset($log_result['data'][0]['count'])) {
                        $logs_logged = $log_result['data'][0]['count'];
                        
                        // 3. Compare counts
                        if ($logs_logged >= $total_bins) {
                            // Trigger the completion handler
                            $completion_result = handle_route_completion($route_id, $collector_id, $current_time);

                            if ($completion_result['success']) {
                                $response_message = "Collection logged. **Route automatically completed!**";
                                send_response(true, $response_message, ['route_status' => 'completed']);
                            } else {
                                $response_message .= " **(Failed to auto-complete route: " . $completion_result['message'] . ")**";
                                send_response(true, $response_message, ['route_status' => 'active']);
                            }
                        }
                    } else {
                        error_log("Failed to fetch collection log count for auto-completion check.");
                    }
                }
            }
            // --- END NEW LOGIC ---

            send_response(true, $response_message, ['route_status' => 'active']);
        } else {
            send_response(false, $result['message']);
        }
        break;

    case 'report':
        $bin_id = $_POST['bin_id'] ?? '';
        $issue = $_POST['issue'] ?? '';
        $route_id = $_POST['route_id'] ?? ''; 
        
        if (!$bin_id || !$issue || !$route_id) {
            send_response(false, "Bin ID, Route ID, and issue description are required.");
        }

        // Payload for the bin_alerts table
        $payload = [
            'bin_id' => $bin_id,
            // Assuming bin_alerts supports collector_id and route_id for context
            'collector_id' => $collector_id,
            'route_id' => (int)$route_id, 
            'alert_type' => 'manual_report',
            'message' => $issue,
            'created_at' => date('Y-m-d H:i:sP'),
            'status' => 'unresolved'
        ];

        $result = supabase_api_call('POST', 'bin_alerts', $payload);

        if ($result['success']) {
            send_response(true, "Issue successfully reported.");
        } else {
            send_response(false, $result['message']);
        }
        break;

    case 'update_route_status':
        $route_id = $_POST['route_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (!$route_id || !in_array($status, ['active', 'completed'])) {
            send_response(false, "Route ID or invalid status is required.");
        }

        // --- Data Type Casting ---
        $route_id = (int)$route_id; 
        $current_time = date('Y-m-d H:i:sP');
        
        if ($status === 'completed') {
            // Use the dedicated handler for completion (which includes skipping)
            $completion_result = handle_route_completion($route_id, $collector_id, $current_time);
            
            if ($completion_result['success']) {
                send_response(true, $completion_result['message']);
            } else {
                send_response(false, $completion_result['message']);
            }
        } else if ($status === 'active') {
            // Logic for starting a route
            $payload = ['status' => 'active', 'started_at' => $current_time, 'completed_at' => null];
            $query_filter = "?id=eq.$route_id&driver=eq.$collector_id";

            $result = supabase_api_call('PATCH', 'collection_route', $payload, $query_filter);

            if ($result['success'] && !empty($result['data'])) {
                send_response(true, "Route status updated to **Active**.");
            } else if ($result['success'] && empty($result['data'])) {
                send_response(false, "Route not found or you are not authorized to start this route.");
            } else {
                send_response(false, $result['message']);
            }
        }
        break;

    default:
        send_response(false, "Action not supported.");
        break;
}

?>