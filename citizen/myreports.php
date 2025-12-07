<?php
include '../auth/config.php';
session_start();

// Ensure user is logged in and is a citizen
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user_id = $_SESSION['user']['id'];
$user = $_SESSION['user']; // <-- Add this so navbar can access $user

// Fetch all reports submitted by this user
$reports = supabase_fetch("citizen_reports", "?select=id,bin_id,issue_type,status,created_at&user_id=eq.$user_id&order=created_at.desc");

// Fetch bin info for each report
$bins_data = [];
if (is_array($reports)) {
    foreach ($reports as $r) {
        if (!isset($bins_data[$r['bin_id']])) {
            $bin = supabase_fetch("bins", "?select=bin_code,location_name&id=eq." . $r['bin_id']);
            $bins_data[$r['bin_id']] = (is_array($bin) && count($bin) > 0) ? $bin[0] : ['bin_code' => 'N/A', 'location_name' => 'Unknown'];
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - BinTrack Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>
<?php include '../includes/citizen/navbar.php'; ?>

<div class="container-xl py-4">
    <section id="myReportsPage" class="page-content">
        <h1 class="mb-4 text-dark-green fw-bold">My Submitted Reports</h1>

        <div class="custom-card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light-green">
                        <tr>
                            <th>ID</th>
                            <th>Bin Location</th>
                            <th>Issue Type</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (is_array($reports) && count($reports) > 0): ?>
                            <?php foreach ($reports as $r): 
                                $bin = $bins_data[$r['bin_id']];
                                $status_class = match ($r['status']) {
                                    'pending' => 'bg-secondary',
                                    'in_progress' => 'bg-warning text-dark',
                                    'resolved' => 'bg-success',
                                    'invalid' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            ?>
                                <tr>
                                    <td class="fw-bold text-primary">#R-<?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($bin['bin_code']) ?> - <?= htmlspecialchars($bin['location_name']) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $r['issue_type'])) ?></td>
                                    <td><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span></td>
                                    <td>
                                        <a href="reportdetail.php?report_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">You have not submitted any reports yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
