<?php
require '../../auth/config.php'; // Should contain SUPABASE_URL & SUPABASE_SERVICE_KEY
header('Content-Type: application/json');

// Get input JSON
$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

try {
    // Delete user from Supabase Auth (requires service role key)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SUPABASE_URL . "/auth/v1/admin/users/$userId");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . SUPABASE_SERVICE_KEY,
        "Authorization: Bearer " . SUPABASE_SERVICE_KEY
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 204) {
        // Successfully deleted from Auth, profiles row will be deleted automatically via ON DELETE CASCADE
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete user from Supabase Auth',
            'response' => $response
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
