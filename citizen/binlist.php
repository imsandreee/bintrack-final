<?php
session_start();

// Only allow citizen
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user = $_SESSION['user'];
include '../auth/config.php';

// Function to fetch bins (with optional search & sort)
function fetch_bins($search = '', $sort = '') {
    $query = "?select=*";

    // Add search filter
    if ($search) {
        $search = urlencode($search);
        $query .= "&or=(bin_code.ilike.%$search%,location_name.ilike.%$search%)";
    }

    // Fetch bins from Supabase
    $bins = supabase_fetch("bins", $query);

    if (!is_array($bins)) return [];

    // Get latest sensor readings
    $binHeight = 30;
    foreach ($bins as &$bin) {
        $readings = supabase_fetch("sensor_readings", "?select=ultrasonic_distance_cm,load_cell_weight_kg,gps_lat,gps_lng&bin_id=eq." . $bin['id'] . "&order=timestamp.desc&limit=1");
        $latest = (is_array($readings) && count($readings) > 0) ? $readings[0] : null;

        $ultrasonic = $latest['ultrasonic_distance_cm'] ?? $binHeight;
        $weight = $latest['load_cell_weight_kg'] ?? 0;
        $gps = ($latest['gps_lat'] ?? $bin['latitude']) . ", " . ($latest['gps_lng'] ?? $bin['longitude']);
        $fill = 100 - round(min(max($ultrasonic, 0), $binHeight) / $binHeight * 100);

        if ($latest === null) {
            $status = "Sensor Error"; $badgeClass = "badge-error"; $progressClass = "bg-secondary";
        } elseif ($fill >= 100) {
            $status = "Overflow"; $badgeClass = "badge-overflow"; $progressClass = "bg-danger";
        } elseif ($fill >= 75) {
            $status = "Nearly Full"; $badgeClass = "badge-nearly-full"; $progressClass = "bg-warning";
        } else {
            $status = "Normal"; $badgeClass = "badge-normal"; $progressClass = "bg-success";
        }

        $bin['latest'] = [
            'fill' => $fill,
            'weight' => $weight,
            'gps' => $gps,
            'status' => $status,
            'badgeClass' => $badgeClass,
            'progressClass' => $progressClass
        ];
    }

    // Sorting
    if ($sort === 'fill_desc') {
        usort($bins, fn($a, $b) => $b['latest']['fill'] <=> $a['latest']['fill']);
    } elseif ($sort === 'status_alerts') {
        $priority = ['Overflow' => 1, 'Nearly Full' => 2, 'Sensor Error' => 3, 'Normal' => 4];
        usort($bins, fn($a, $b) => ($priority[$a['latest']['status']] ?? 5) <=> ($priority[$b['latest']['status']] ?? 5));
    }

    return $bins;
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? '';
    header('Content-Type: application/json');
    echo json_encode(fetch_bins($search, $sort));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BinTrack Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>
<?php include '../includes/citizen/navbar.php'; ?>

<div class="container-xl py-4">
<section id="binListPage" class="page-content">
    <h1 class="mb-4 text-dark-green fw-bold">Smart Bin Status List</h1>

    <div class="custom-card p-4">

        <div class="row mb-3 align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Bin Code, Location...">
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <select class="form-select" id="sortSelect">
                    <option value="">Sort by Distance</option>
                    <option value="fill_desc">Sort by Fill Level (High)</option>
                    <option value="status_alerts">Sort by Status (Alerts First)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button id="refreshBtn" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="binsTable">
                <thead class="bg-light-green">
                    <tr>
                        <th>Bin Code</th>
                        <th>Location</th>
                        <th>Fill Level</th>
                        <th>Weight</th>
                        <th class="d-none d-lg-table-cell">GPS</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="binsTbody">
                    <!-- Rows will be populated dynamically -->
                </tbody>
            </table>
        </div>

    </div>
</section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fetch bins and populate table
async function loadBins() {
    const search = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortSelect').value;
    const res = await fetch(`?action=fetch&search=${encodeURIComponent(search)}&sort=${sort}`);
    const bins = await res.json();

    const tbody = document.getElementById('binsTbody');
    tbody.innerHTML = '';

    bins.forEach(bin => {
        const latest = bin.latest;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="fw-bold">${bin.bin_code}</td>
            <td>${bin.location_name}</td>
            <td>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar progress-bar-fill ${latest.progressClass}" role="progressbar" style="width: ${latest.fill}%" aria-valuenow="${latest.fill}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">${latest.fill}% Full</small>
            </td>
            <td>${latest.weight > 0 ? latest.weight + ' kg' : 'N/A'}</td>
            <td class="d-none d-lg-table-cell">${latest.gps}</td>
            <td><span class="badge ${latest.badgeClass}">${latest.status}</span></td>
            <td>
                <a href="bindetail.php?bin_code=${encodeURIComponent(bin.bin_code)}" class="btn btn-sm btn-outline-secondary">View Details</a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Event listeners
document.getElementById('refreshBtn').addEventListener('click', loadBins);
document.getElementById('searchInput').addEventListener('input', loadBins);
document.getElementById('sortSelect').addEventListener('change', loadBins);

// Load initially
loadBins();
</script>
</body>
</html>
