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

    
            <!-- 7. Collections Page -->
            <div id="collections" class="page-content" style="display:block;">
                <h1 class="mb-4">Collection Logs</h1>
                 <div class="d-flex justify-content-between align-items-center mb-3">
                    <div></div>
                    <div class="input-group w-auto">
                        <input type="text" class="form-control" placeholder="Search collections...">
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="card p-4 shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Bin Code</th>
                                    <th>Collector</th>
                                    <th>Weight Collected (kg)</th>
                                    <th>Collected At</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>B-101</td>
                                    <td>John Doe</td>
                                    <td>45.2</td>
                                    <td>2024-10-25 09:15</td>
                                    <td>Full, minor spillage noted.</td>
                                </tr>
                                <tr>
                                    <td>B-500</td>
                                    <td>Jane Smith</td>
                                    <td>88.9</td>
                                    <td>2024-10-25 08:40</td>
                                    <td>Routine collection.</td>
                                </tr>
                                <tr>
                                    <td>B-103</td>
                                    <td>John Doe</td>
                                    <td>32.0</td>
                                    <td>2024-10-25 08:20</td>
                                    <td>Half full, route deviation.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /Collections Page -->
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

    const res = await fetch('fetch_route.php?id=' + id);
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

    const res = await fetch('delete_route.php', {
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

    const url = id ? 'update_route.php' : 'save_route.php';

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
