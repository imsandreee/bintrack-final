<?php
include '../auth/config.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    http_response_code(403);
    exit;
}

$bins = supabase_fetch("bins", "?select=*");
$binHeight = 30; // Bin height in cm

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'distance';

// Function to get latest sensor reading
function get_latest_reading($bin_id) {
    $readings = supabase_fetch(
        "sensor_readings", 
        "?select=ultrasonic_distance_cm,load_cell_weight_kg,gps_lat,gps_lng,battery_percentage,signal_strength&bin_id=eq.$bin_id&order=timestamp.desc&limit=1"
    );
    if (is_array($readings) && count($readings) > 0) {
        return $readings[0];
    }
    return null;
}

// Filter bins by search query
if ($search) {
    $bins = array_filter($bins, function($b) use ($search) {
        return str_contains(strtolower($b['bin_code']), strtolower($search)) ||
               str_contains(strtolower($b['location_name']), strtolower($search));
    });
}

// Sort bins
usort($bins, function($a, $b) use ($sort, $binHeight) {
    $latestA = get_latest_reading($a['id']);
    $latestB = get_latest_reading($b['id']);
    
    $fillA = 100 - round(min(max($latestA['ultrasonic_distance_cm'] ?? $binHeight, 0), $binHeight) / $binHeight * 100);
    $fillB = 100 - round(min(max($latestB['ultrasonic_distance_cm'] ?? $binHeight, 0), $binHeight) / $binHeight * 100);

    return match($sort) {
        'fill_high' => $fillB <=> $fillA,
        'status' => ($fillB >= 75 ? 1 : 0) <=> ($fillA >= 75 ? 1 : 0),
        default => 0, // distance placeholder, since we don't have distance yet
    };
});

// Output HTML rows
foreach ($bins as $bin):
    $latest = get_latest_reading($bin['id']);
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
?>
<tr>
    <td class="fw-bold"><?= htmlspecialchars($bin['bin_code']) ?></td>
    <td><?= htmlspecialchars($bin['location_name']) ?></td>
    <td>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar progress-bar-fill <?= $progressClass ?>" role="progressbar" style="width: <?= $fill ?>%" aria-valuenow="<?= $fill ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <small class="text-muted"><?= $fill ?>% Full</small>
    </td>
    <td><?= ($weight > 0) ? $weight . " kg" : "N/A" ?></td>
    <td class="d-none d-lg-table-cell"><?= htmlspecialchars($gps) ?></td>
    <td><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
    <td>
        <a href="bindetail.php?bin_code=<?= urlencode($bin['bin_code']) ?>" class="btn btn-sm btn-outline-secondary">View Details</a>
    </td>
</tr>
<?php endforeach; ?>
