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

// Get bin_code from URL query
$bin_code = $_GET['bin_code'] ?? '';
if (!$bin_code) {
    die("Bin code not specified.");
}

// Fetch bin info by bin_code
$bin_data = supabase_fetch("bins", "?select=*&bin_code=eq.$bin_code");
if (!is_array($bin_data) || count($bin_data) === 0) {
    die("Bin not found.");
}
$bin = $bin_data[0];
$bin_id = $bin['id'];

// Fetch latest sensor reading
$sensor_data = supabase_fetch("sensor_readings", "?select=ultrasonic_distance_cm,load_cell_weight_kg,gps_lat,gps_lng,timestamp&bin_id=eq.$bin_id&order=timestamp.desc&limit=1");
$sensor = (is_array($sensor_data) && count($sensor_data) > 0) ? $sensor_data[0] : null;

// Calculate fill level (0–100%) based on ultrasonic distance (bin height = 30 cm)
$binHeight = 30.0; // cm
$distance = $sensor['ultrasonic_distance_cm'] ?? null;
$fillPercent = ($distance !== null) ? max(0, min(100, round((1 - ($distance / $binHeight)) * 100))) : null;

// Weight
$weight = $sensor['load_cell_weight_kg'] ?? null;

// Status badge based on fill level
if ($fillPercent === null) {
    $status = "Sensor Error";
    $badgeClass = "badge-error";
} elseif ($fillPercent >= 100) {
    $status = "Overflow";
    $badgeClass = "badge-overflow";
} elseif ($fillPercent >= 75) {
    $status = "Nearly Full";
    $badgeClass = "badge-nearly-full";
} else {
    $status = "Normal";
    $badgeClass = "badge-normal";
}

// GPS
$gps = ($sensor['gps_lat'] ?? $bin['latitude']) . ", " . ($sensor['gps_lng'] ?? $bin['longitude']);

// Last reading timestamp
$lastReading = $sensor['timestamp'] ?? "N/A";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bin Details - <?= htmlspecialchars($bin['bin_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/citizen.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

</head>
<body>
<?php include '../includes/citizen/navbar.php'; ?>

<div class="container-xl py-4">
    <section id="binDetailsPage" class="page-content">
        <h1 class="mb-4 text-dark-green fw-bold">Smart Bin Details <span id="binIdDisplay"><?= htmlspecialchars($bin['bin_code']) ?></span></h1>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="custom-card p-4 h-100">
                    <h5 class="fw-bold mb-3">Real-Time Sensor Data</h5>
                    <div class="row g-3">
                        <!-- Fill Level -->
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 text-center bg-light-green">
                                <i class="bi bi-trash-fill text-green fs-3"></i>
                                <p class="mb-0 text-muted small">Fill Level (Ultrasonic)</p>
                                <h4 class="fw-bold text-dark" id="fillLevelDisplay">
                                    <?= $fillPercent !== null ? $fillPercent . "%" : "N/A" ?>
                                </h4>
                                <div class="progress mt-2" style="height: 15px;">
                                    <div class="progress-bar progress-bar-fill <?= $badgeClass ?>"
                                         role="progressbar"
                                         style="width: <?= $fillPercent !== null ? $fillPercent : 0 ?>%"
                                         aria-valuenow="<?= $fillPercent ?? 0 ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Weight -->
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 text-center bg-light-green">
                                <i class="bi bi-box-seam text-green fs-3"></i>
                                <p class="mb-0 text-muted small">Current Waste Weight (Load Cell)</p>
                                <h4 class="fw-bold text-dark" id="weightDisplay">
                                    <?= $weight !== null ? $weight . " kg / " . $bin['status'] : "N/A / 10 kg" ?>
                                </h4>
                                <p class="mb-0"><span class="badge bg-secondary">Capacity: 10 kg</span></p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3">Bin Location & Status</h5>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Location Name:</span>
                            <span><?= htmlspecialchars($bin['location_name']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">GPS Coordinates:</span>
                            <span><?= htmlspecialchars($gps) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Current Status:</span>
                            <h5><span class="badge <?= $badgeClass ?>"><?= $status ?></span></h5>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Last Reading:</span>
                            <span><?= htmlspecialchars($lastReading) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-info bg-opacity-10 rounded-bottom">
                            <span class="fw-semibold text-primary">Last Collection Date:</span>
                            <span class="text-primary fw-bold">2024-10-25</span>
                        </li>
                    </ul>

                    <div class="d-grid">
                        <a href="reportform.php?bin_code=<?= urlencode($bin['bin_code']) ?>&bin_id=<?= urlencode($bin['id']) ?>" 
   class="btn btn-primary btn-lg">
    <i class="bi bi-exclamation-octagon me-2"></i> Report an Issue for Bin <?= htmlspecialchars($bin['bin_code']) ?>
</a>


                    </div>
                </div>
            </div>

           <div class="col-lg-5">
    <div class="custom-card p-4">
        <h5 class="fw-bold mb-3">Map View</h5>

        <!-- Leaflet Map Container -->
        <div id="map" class="ratio ratio-1x1 rounded-3 border"></div>

        <!-- Google Maps Button -->
        <div class="d-grid mt-3">
            <a href="https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars($sensor['gps_lat'] ?? $bin['latitude']) ?>,<?= htmlspecialchars($sensor['gps_lng'] ?? $bin['longitude']) ?>" 
               target="_blank" 
               class="btn btn-outline-primary">
                <i class="bi bi-geo-alt me-2"></i> Open in Google Maps
            </a>
        </div>

        <p class="mt-2 text-muted small text-center">GPS Module Data Visualization</p>
    </div>
</div>

    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Split the GPS string into latitude and longitude
    let gps = "<?= htmlspecialchars($gps) ?>";
    let coords = gps.split(",").map(Number);

    // Initialize map
    let map = L.map('map').setView(coords, 17); // Zoom 17 for close-up

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add a marker for the bin
    L.marker(coords)
        .addTo(map)
        .bindPopup("<b><?= htmlspecialchars($bin['bin_code']) ?></b><br><?= htmlspecialchars($bin['location_name']) ?>")
        .openPopup();
});
</script>

</body>
</html>
