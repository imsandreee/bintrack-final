<?php
// collector/collector_actions.php
// Handles AJAX POST requests for bin collection, reporting issues, and manual logging.

// Ensure paths are correct for your structure
// This file MUST include a working definition for both supabase_fetch() AND supabase_insert().
require_once '../auth/config.php';

session_start();
header('Content-Type: application/json');

// --- 1. Authorization Check ---
$collector_id = $_SESSION['user']['id'] ?? null;
if (!$collector_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    http_response_code(401);
    exit;
}

// --- 2. Input Validation ---
$action = $_POST['action'] ?? '';
$bin_id = $_POST['bin_id'] ?? null; // Can be null for manual_log if 'MANUAL' is chosen

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Missing action parameter.']);
    http_response_code(400);
    exit;
}

// Validate Bin ID required for 'collect' and 'report'
if (($action === 'collect' || $action === 'report') && !$bin_id) {
    echo json_encode(['success' => false, 'message' => 'Missing Bin ID for the specified action.']);
    http_response_code(400);
    exit;
}

$response = ['success' => false, 'message' => 'Unknown error occurred.'];

// --- 3. Process Action ---
try {
    if ($action === 'collect') {
        // --- Action: Simple Collect Bin 🚛 ---
        
        $data = [
            'bin_id' => (string)$bin_id, 
            'collector_id' => (string)$collector_id,
            'collected_at' => date('Y-m-d H:i:s'), // Current timestamp
            'weight_collected_kg' => 0, // Assume 0 or default if no weight is passed in the initial simple collect
            'remarks' => 'Simple collection completed.'
        ];

        // Insert log into the collection_logs table
        $result = supabase_insert('collection_logs', $data);

        if (!empty($result) && isset($result[0]['id'])) {
            $response = ['success' => true, 'message' => 'Collection logged successfully.'];
        } elseif (isset($result['error'])) {
            $response = ['success' => false, 'message' => 'Database Error: ' . $result['error']];
        } else {
            $response = ['success' => false, 'message' => 'Failed to log collection in database. (Check Bin/Collector IDs)'];
        }

    } elseif ($action === 'report') {
        // --- Action: Report Issue 🚩 ---

        // NOTE: If you pass specific issue details from the report form, pull them here.
        $issue_details = $_POST['issue_details'] ?? 'Reported manually by collector.';

        $data = [
            'bin_id' => (string)$bin_id,
            'alert_type' => 'Reported Issue',
            'description' => $issue_details . ' (Collector ID: ' . $collector_id . ')',
            'created_at' => date('Y-m-d H:i:s'),
            'resolved' => false
        ];
        
        $result = supabase_insert('bin_alerts', $data);

        if (!empty($result) && isset($result[0]['id'])) {
            $response = ['success' => true, 'message' => 'Issue reported successfully.'];
        } elseif (isset($result['error'])) {
            $response = ['success' => false, 'message' => 'Database Error: ' . $result['error']];
        } else {
            $response = ['success' => false, 'message' => 'Failed to log report in database. (Check Bin ID)'];
        }

    } elseif ($action === 'manual_log') {
        // --- Action: Manual Log 📝 (New) ---

        $collected_at = $_POST['collected_at'] ?? date('Y-m-d H:i:s');
        $weight_collected_kg = $_POST['weight_collected_kg'] ?? 0;
        $remarks = $_POST['remarks'] ?? null;
        
        // --- Specific Manual Log Validation ---
        if (!$bin_id && empty($remarks)) {
             $response = ['success' => false, 'message' => 'Bin ID or detailed remarks (for MANUAL entry) are required.'];
             http_response_code(400);
             goto end_script;
        }

        // If the bin ID is the string 'MANUAL' (from the dropdown), set $bin_id to null for DB insertion
        $db_bin_id = ($bin_id === 'MANUAL' || !$bin_id) ? null : (string)$bin_id;
        
        $data = [
            'bin_id' => $db_bin_id,
            'collector_id' => (string)$collector_id,
            'collected_at' => date('Y-m-d H:i:s', strtotime($collected_at)),
            'weight_collected_kg' => (float)$weight_collected_kg,
            'remarks' => $remarks
        ];

        // Insert log into the collection_logs table
        $result = supabase_insert('collection_logs', $data);

        if (!empty($result) && isset($result[0]['id'])) {
            $response = ['success' => true, 'message' => 'Manual collection log saved successfully!'];
        } elseif (isset($result['error'])) {
            $response = ['success' => false, 'message' => 'Database Error: ' . $result['error']];
        } else {
            $response = ['success' => false, 'message' => 'Failed to save manual log to database.'];
        }

    } else {
        $response = ['success' => false, 'message' => 'Invalid action specified.'];
        http_response_code(400);
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    http_response_code(500);
}

end_script:
echo json_encode($response);
exit;