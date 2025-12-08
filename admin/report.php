<?php
require '../auth/config.php';

// Fetch reports with joined user and bin info
$reports = supabase_fetch(
    'citizen_reports',
    '?select=id,issue_type,description,image_url,status,created_at,admin_remarks,profiles(full_name),bins(bin_code)'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $id     = $_POST['report_id'];
    $status = $_POST['status'];
    $custom = trim($_POST['custom_remarks'] ?? '');

    // Default remarks based on status
    $remarks = match ($status) {
        'pending'     => 'Report has been received and is awaiting review.',
        'in_progress' => 'Report is currently being investigated by the team.',
        'resolved'    => 'Issue has been addressed and marked as resolved.',
        'invalid'     => 'Report was reviewed and found to be invalid.',
        default       => ''
    };

    // Override if admin typed custom remarks
    if (!empty($custom)) {
        $remarks = $custom;
    }

    supabase_update('citizen_reports', [
        'status'        => $status,
        'admin_remarks' => $remarks,
        'updated_at'    => date('c')
    ], "?id=eq.$id");

    header("Location: report.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    supabase_delete('citizen_reports', "?id=eq." . $_GET['delete']);
    header("Location: report.php");
    exit;
}

$custom = trim($_POST['custom_remarks'] ?? '');

if (!empty($custom)) {
    $remarks = $custom;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Citizen Reports</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex">
<?php include '../includes/admin/sidebar.php'; ?>

<div class="w-100 p-4">
<h1 class="mb-4">Citizen Reports</h1>

<div class="card p-4 shadow-sm border-0">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
    <th>User</th>
    <th>Bin</th>
    <th>Issue</th>
    <th>Description</th>
    <th>Image</th>
    <th>Remarks</th>
    <th>Status</th>
    <th>Created</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach ($reports as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['profiles']['full_name'] ?? 'Anonymous') ?></td>
    <td><?= htmlspecialchars($r['bins']['bin_code'] ?? 'N/A') ?></td>
    <td>
        <span class="badge bg-danger">
            <?= ucfirst($r['issue_type']) ?>
        </span>
    </td>
    <td><?= htmlspecialchars($r['description']) ?></td>

    <td>
        <?php if (!empty($r['image_url'])): ?>
            <a href="../<?= $r['image_url'] ?>" target="_blank">View</a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
<td><?= htmlspecialchars($r['admin_remarks'] ?? '—') ?></td>

    <td>
        <?php
        $status = $r['status'];
        $badge =
            $status == 'pending' ? 'bg-warning text-dark' :
            ($status == 'resolved' ? 'bg-success' :
            ($status == 'in_progress' ? 'bg-info' : 'bg-secondary'));
        ?>
        <span class="badge <?= $badge ?>">
            <?= ucfirst(str_replace('_', ' ', $status)) ?>
        </span>
    </td>

    <td><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>

    <td>
        <button
            class="btn btn-sm btn-info editBtn"
            data-id="<?= $r['id'] ?>"
            data-status="<?= $r['status'] ?>"
            data-bs-toggle="modal"
            data-bs-target="#reportModal">
            <i class="bi bi-pencil"></i>
        </button>

        <a href="?delete=<?= $r['id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this report?')">
            <i class="bi bi-trash"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</div>
</div>
</div>

<!-- UPDATE MODAL -->
<div class="modal fade" id="reportModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">

<div class="modal-header">
    <h5 class="modal-title">Update Report Status</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="report_id" id="report_id">

    <label class="form-label">Status</label>
    <select class="form-select" name="status" id="report_status">
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
        <option value="invalid">Invalid</option>
    </select>
    <div class="mb-3">
    <label class="form-label">Remarks</label>
    <textarea class="form-control" name="custom_remarks" rows="3"
        placeholder="Override the auto message..."></textarea>
</div>

</div>

<div class="modal-footer">
    <button type="submit" name="update_report" class="btn btn-success">
        Update
    </button>
</div>

</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('report_id').value = btn.dataset.id;
        document.getElementById('report_status').value = btn.dataset.status;
    });
});
</script>

</body>
</html>
