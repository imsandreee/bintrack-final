<?php
session_start();

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user = $_SESSION['user'];
?>

<?php
include '../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bin_id = $_POST['bin_id'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_SESSION['user']['id'] ?? '';

    if (!$bin_id || !$issue_type || !$description || !$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['photoEvidence']) && $_FILES['photoEvidence']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/reports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileTmp = $_FILES['photoEvidence']['tmp_name'];
        $fileName = uniqid('report_') . '_' . basename($_FILES['photoEvidence']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $filePath)) {
            $image_url = 'uploads/reports/' . $fileName; // Relative path for front-end
        }
    }

    $postData = [
        'user_id' => $user_id,
        'bin_id' => $bin_id,
        'issue_type' => $issue_type,
        'description' => $description,
        'image_url' => $image_url,
        'status' => 'pending'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SUPABASE_URL . '/rest/v1/citizen_reports');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['status' => 'error', 'message' => $error]);
    } else {
        echo json_encode(['status' => 'success', 'data' => json_decode($response, true)]);
    }
    exit;
}
?>
