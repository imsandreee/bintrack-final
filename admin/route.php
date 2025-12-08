<?php
require_once '../auth/config.php';

/* GET DATA */
$collectors = supabase_fetch("profiles?select=id,full_name&role=eq.collector");
$bins = supabase_fetch("bins?select=id,bin_code,location_name,status");

$routes = supabase_fetch("collection_routes?select=id,route_name,created_at,route_bins(count)");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BinTrack - Collection Routes</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

<h1 class="mb-4">Collection Routes</h1>

<!-- ADD BUTTON -->
<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#routeModal" onclick="resetModal()">
    <i class="bi bi-plus-circle"></i> Add New Route
</button>

<div class="card shadow-sm p-3 border-0">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
    <th>Route Name</th>
    <th>Bins</th>
    <th>Created</th>
    <th width="120">Actions</th>
</tr>
</thead>

<tbody>
<?php if($routes): ?>
<?php foreach($routes as $route): 
    $bin_count = $route['route_bins'][0]['count'] ?? 0;
?>
<tr>
    <td><?= htmlspecialchars($route['route_name']) ?></td>
    <td><?= $bin_count ?></td>
    <td><?= date('M d, Y h:i A', strtotime($route['created_at'])) ?></td>
    <td>
        <button 
            class="btn btn-sm btn-primary editRouteBtn" 
            data-id="<?= $route['id'] ?>" 
            data-bs-toggle="modal" 
            data-bs-target="#routeModal"
        >
            <i class="bi bi-pencil"></i>
        </button>

        <button 
            class="btn btn-sm btn-danger deleteRouteBtn" 
            data-id="<?= $route['id'] ?>"
        >
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>
</div>

<!-- ✅ ROUTE MODAL -->
<div class="modal fade" id="routeModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<form id="routeForm">

    <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Create Route</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">

        <input type="hidden" name="route_id" id="route_id">

        <div class="mb-3">
            <label class="form-label">Route Name</label>
            <input type="text" name="route_name" id="routeName" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Assigned Collector</label>
            <select name="collector_id" id="routeCollector" class="form-select" required>
                <option value="">-- Select Collector --</option>
                <?php if($collectors): foreach($collectors as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                <?php endforeach; endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Assign Bins</label>
            <select name="bin_ids[]" id="assignedBins" class="form-select" multiple size="6" required>
                <?php if($bins): foreach($bins as $bin): ?>
                    <option value="<?= $bin['id'] ?>">
                        <?= htmlspecialchars($bin['bin_code']) ?> - <?= htmlspecialchars($bin['location_name']) ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
            <small class="text-muted">Hold CTRL to select multiple</small>
        </div>

    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Save
        </button>
    </div>

</form>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

// Reset for "Add"
function resetModal(){
    document.getElementById('modalTitle').innerText = "Create Route";
    document.getElementById('routeForm').reset();
    document.getElementById('route_id').value = '';
}

// EDIT
document.querySelectorAll('.editRouteBtn').forEach(btn => {
btn.addEventListener('click', async function(){
    const id = this.dataset.id;
    document.getElementById('modalTitle').innerText = "Edit Route";

    const res = await fetch('route/fetch_route.php?id=' + id);
    const data = await res.json();

    document.getElementById('route_id').value = data.route.id;
    document.getElementById('routeName').value = data.route.route_name;
    document.getElementById('routeCollector').value = data.route.collector_id;

    const assigned = data.bins.map(b => b.bin_id);

    document.querySelectorAll('#assignedBins option').forEach(option => {
        option.selected = assigned.includes(option.value);
    });
});
});


// DELETE
document.querySelectorAll('.deleteRouteBtn').forEach(btn => {
btn.addEventListener('click', async function(){
    if(!confirm('Delete this route and its assignments?')) return;

    const res = await fetch('route/delete_route.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + this.dataset.id
    });

    const msg = await res.text();

    if(msg === "success") {
        location.reload();
    } else {
        alert(msg);
    }
});
});


// SAVE (ADD / UPDATE)
document.getElementById("routeForm").addEventListener("submit", async function(e){
    e.preventDefault();

    const formData = new FormData(this);
    const id = document.getElementById("route_id").value;

    const url = id ? 'route/update_route.php' : 'route/save_route.php';

    const res = await fetch(url, {
        method: "POST",
        body: formData
    });

    const msg = await res.text();

    if(msg === "success"){
        location.reload();
    }
    else{
        alert(msg);
    }
});
</script>
</body>
</html>
