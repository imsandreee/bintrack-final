<?php
require '../../auth/config.php'; // Supabase setup
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['fullName']) || empty($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

$id = $data['id'];
$fullName = $data['fullName'];
$role = $data['role'];
$contact = $data['contact'] ?? '';
$address = $data['address'] ?? '';

try {
    // Update the profile
    $result = supabase_update('profiles', ['full_name' => $fullName, 'role' => $role, 'contact_number' => $contact, 'address' => $address], "?id=eq.$id");
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
