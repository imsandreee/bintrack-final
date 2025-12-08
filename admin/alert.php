
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BinTrack - Sensors Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="d-flex" id="wrapper">
    <?php include '../includes/admin/sidebar.php'; ?>
    <div id="page-content-wrapper" class="w-100">
        <?php include '../includes/admin/topnavbar.php'; ?>

        <div class="page-content p-4">
            
            <!-- 5. Alerts Page -->
            <div id="alerts" class="page-content" style="display:block;">
                <h1 class="mb-4">Bin Alerts</h1>
                 <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn btn-outline-success <?= (!isset($_GET['filter']) || $_GET['filter']=='all')?'active':'' ?>">All</a>
                        <a href="?filter=unresolved" class="btn btn-outline-warning <?= ($_GET['filter'] ?? '')=='unresolved'?'active':'' ?>">Unresolved</a>
                        <a href="?filter=resolved" class="btn btn-outline-secondary <?= ($_GET['filter'] ?? '')=='resolved'?'active':'' ?>">Resolved</a>
                    </div>

                    <div class="input-group w-auto">
                        <input type="text" class="form-control" placeholder="Search alerts...">
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="card p-4 shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Bin</th>
                                    <th>Alert Type</th>
                                    <th>Message</th>
                                    <th>Created At</th>
                                    <th>Resolved</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
require '../auth/config.php';

// Filter by resolved status
$filter = $_GET['filter'] ?? 'all';
$query = 'bin_alerts?select=id,alert_type,message,created_at,resolved,bins(bin_code)';

if ($filter === 'unresolved') {
    $query .= '&resolved=eq.false';
} elseif ($filter === 'resolved') {
    $query .= '&resolved=eq.true';
}

$ch = curl_init(SUPABASE_URL . '/rest/v1/' . $query . '&order=created_at.desc');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$alerts = json_decode($response, true);

if (empty($alerts)) {
    echo "<tr><td colspan='6' class='text-center text-muted'>No alerts found</td></tr>";
} else {
    foreach ($alerts as $a) {
        $badge = match ($a['alert_type']) {
            'full' => 'danger',
            'nearly_full' => 'warning',
            'offline' => 'secondary',
            'sensor_error' => 'warning',
            'overload' => 'danger',
            default => 'primary'
        };

        $resolvedBadge = $a['resolved']
            ? "<span class='badge bg-success'>Yes</span>"
            : "<span class='badge bg-warning text-dark'>No</span>";

        echo "<tr>
            <td>{$a['bins']['bin_code']}</td>
            <td><span class='badge bg-{$badge}'>{$a['alert_type']}</span></td>
            <td>{$a['message']}</td>
            <td>".date("Y-m-d H:i", strtotime($a['created_at']))."</td>
            <td>{$resolvedBadge}</td>
            <td>";

        if (!$a['resolved']) {
            echo "
                <button class='btn btn-sm btn-success me-2' onclick='resolveAlert({$a['id']})'>
                    <i class='bi bi-check-lg'></i>
                </button>";
        } else {
            echo "
                <button class='btn btn-sm btn-secondary me-2 disabled'>
                    <i class='bi bi-check-lg'></i>
                </button>";
        }

        echo "
                <button class='btn btn-sm btn-danger' onclick='deleteAlert({$a['id']})'>
                    <i class='bi bi-trash'></i>
                </button>
            </td>
        </tr>";
    }
}
?>
</tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /Alerts Page -->
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function resolveAlert(id) {
    if(!confirm("Mark this alert as resolved?")) return;

    fetch("alert/resolve_alert.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({id})
    }).then(res => res.json()).then(data => {
        if(data.success) location.reload();
        else alert("Error resolving alert");
    });
}

function deleteAlert(id) {
    if(!confirm("Delete this alert permanently?")) return;

    fetch("alert/delete_alert.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({id})
    }).then(res => res.json()).then(data => {
        if(data.success) location.reload();
        else alert("Error deleting alert");
    });
}
</script>

</body>
</html>
