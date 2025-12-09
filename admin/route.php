<?php
// route.php

// --- Configuration Setup ---
require_once '../auth/config.php';
// NOTE: Assuming a working supabase_fetch function definition exists here or in config.php

// --- 1. Fetch Collectors ---
// Filters profile data to only show collectors for assignment dropdown.
$collectors = supabase_fetch("profiles?select=id,full_name&role=eq.collector");


// --- 2. Fetch Bins with nested data ---
// Fetch bins, sensor config, and ALL sensor readings.
// NOTE: This remains inefficient but mirrors the original logic for fill level calculation.
$bins_raw = supabase_fetch("bins?select=id,bin_code,location_name,latitude,longitude,status,sensors(max_weight_capacity),sensor_readings(load_cell_weight_kg,timestamp)&order=bin_code.asc");


// --- 3. Process Bins to calculate Latest Fill Level ---
$processed_bins = [];
if (is_array($bins_raw)) {
    foreach ($bins_raw as $bin) {
        $fill_percent = 0; 
        $current_status = strtolower($bin['status'] ?? 'active'); 
        // Accessing the nested max_weight_capacity
        $capacity = $bin['sensors'][0]['max_weight_capacity'] ?? 10.0;
        $latest_weight = 0.0;

        // Find the LATEST reading from the fetched set of readings
        if (!empty($bin['sensor_readings'])) {
            // Sort descending by timestamp to get the latest reading first
            usort($bin['sensor_readings'], function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            $latest_weight = $bin['sensor_readings'][0]['load_cell_weight_kg'] ?? 0.0;
        }

        // Calculate fill percentage
        if ($capacity > 0) {
            $fill_percent = min(100, round(($latest_weight / $capacity) * 100));
        }

        // Override status based on fill level for route planning
        if ($fill_percent >= 90) {
            $current_status = 'Full';
        } elseif ($fill_percent >= 70) {
            $current_status = 'Nearly Full';
        }
        
        // Capitalize status for display and JS key
        $display_status = ucwords($current_status);

        $processed_bins[] = [
            'id' => $bin['id'],
            'bin_code' => $bin['bin_code'],
            'location_name' => $bin['location_name'],
            'latitude' => $bin['latitude'],
            'longitude' => $bin['longitude'],
            'status' => $display_status, // Use capitalized status for JS and display
            'fill_level_percent' => $fill_percent,
        ];
    }
}
$bins = $processed_bins;


// --- 4. Fetch routes with bin count and assigned collector (if any) ---
// Fetch from the single 'collection_route' table and join to 'profiles' using 'driver' FK
$routes = supabase_fetch("collection_route?select=*,profiles(full_name)&order=created_at.desc");

if (!is_array($routes) || isset($routes['error'])) {
    $error_message = $routes['error'] ?? 'Failed to fetch routes.';
    $routes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BinTrack - Route Assignment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
/* Style for the map container */
#routeMap {
    height: 600px; /* Make the main map larger */
    border-radius: .25rem;
    margin-top: 5px;
}
#routeMapInModal {
    height: 400px;
    border-radius: .25rem;
}

/* ---------------------------------- */
/* Custom Leaflet Marker Icon Styles */
/* ---------------------------------- */

.custom-div-icon {
    /* Base style for the marker container */
    background-color: transparent !important;
    border: none !important;
}

.custom-div-icon i {
    /* Style for the Bootstrap Icon */
    font-size: 2rem; /* Larger icon */
    transform: translateY(-50%); /* Center the trash icon slightly higher */
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5); /* Add shadow for visibility */
}

/* Full Bin (Red - Urgent) */
.full-bin i { color: #dc3545; } 

/* Nearly Full Bin (Yellow - High Priority) */
.nearly-full-bin i { color: #ffc107; }

/* Active Bin (Green - Normal) */
.active-bin i { color: #198754; } 

/* Inactive Bin (Gray) */
.inactive-bin i { color: #6c757d; } 

/* Maintenance Bin (Blue - Service required) */
.maintenance-bin i { color: #0dcaf0; }

/* Default/Fallback (Black) */
.default-bin i { color: #343a40; }

/* Legend Styles */
.legend-color {
    display: inline-block;
    width: 20px;
    height: 10px;
    margin-right: 5px;
    border-radius: 2px;
    border: 1px solid #ccc;
}

.full-bin.legend-color { background-color: #dc3545; } 
.nearly-full-bin.legend-color { background-color: #ffc107; }
.active-bin.legend-color { background-color: #198754; } 
.inactive-bin.legend-color { background-color: #6c757d; } 
.maintenance-bin.legend-color { background-color: #0dcaf0; }

</style>
</head>

<body>
<div class="d-flex" id="wrapper">

<?php 
// Assuming this file exists and contains the sidebar HTML
include '../includes/admin/sidebar.php'; 
?>
<div id="page-content-wrapper" class="w-100">
<?php 
// Assuming this file exists and contains the top navigation bar HTML
include '../includes/admin/topnavbar.php'; 
?>

<div class="page-content p-4">

    <h1 class="mb-4">Bin Location Map <i class="bi bi-map-fill"></i></h1>
    
    <div class="row mb-4">
        <div class="col-lg-9">
            <div id="routeMap" class="shadow-lg"></div>
        </div>
        
        <div class="col-lg-3">
            <div class="card shadow-sm p-3 h-100">
                <h5>Map Controls & Legend</h5>
                <hr>
                <p>Total Tracked Bins: <strong><?= count($bins) ?></strong></p>
                <button class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#routeModal" onclick="resetRouteForm()">
                    <i class="bi bi-plus-circle"></i> Create New Route
                </button>
                
                <h6 class="mt-2">Real-time Bin Status</h6>
                <ul class="list-unstyled mt-2 small">
                    <li><span class="legend-color full-bin"></span> **Full** (>= 90% fill)</li>
                    <li><span class="legend-color nearly-full-bin"></span> **Nearly Full** (>= 70% fill)</li>
                    <li><span class="legend-color active-bin"></span> **Active** (Normal Operation)</li>
                    <li><span class="legend-color inactive-bin"></span> **Inactive** (Offline/Error)</li>
                    <li><span class="legend-color maintenance-bin"></span> **Maintenance** (Service Scheduled)</li>
                </ul>
                <a href="#routeTableSection" class="btn btn-outline-secondary btn-sm mt-auto">
                    View Route Assignments <i class="bi bi-table"></i>
                </a>
            </div>
        </div>
    </div>
    <hr id="routeTableSection">

    <h2 class="mb-4">Existing Route Assignments <i class="bi bi-list-task"></i></h2>
    
    <div class="card shadow-sm border-0 p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
            <thead class="table-light">
            <tr>
                <th>Route Name</th>
                <th>Assigned Collector</th> 
                <th>Total Bins</th>
                <th>Status</th>
                <th>Created Date</th>
                <th width="120">Actions</th>
            </tr>
            </thead>

            <tbody>
            <?php if($routes): ?>
            <?php foreach($routes as $route): 
                // Accessing the driver name through the direct join
                $collector_name = $route['profiles']['full_name'] ?? '<span class="text-danger">Unassigned</span>';
                
                // Using the new 'total_bins' field and 'status' field
                $bin_count = $route['total_bins'] ?? count($route['bin_ids'] ?? []); // Fallback count on the array size
                $route_status = htmlspecialchars(ucwords($route['status'] ?? 'pending'));

                // Determine badge color for status
                $status_class = match(strtolower($route['status'] ?? 'pending')) {
                    'active' => 'bg-info',
                    'completed' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
            ?>
            <tr>
                <td><?= htmlspecialchars($route['route_name']) ?></td>
                <td><?= $collector_name ?></td> 
                <td><?= $bin_count ?></td>
                <td><span class="badge <?= $status_class ?>"><?= $route_status ?></span></td>
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
            <?php else: ?>
            <tr><td colspan="6" class="text-center">No collection routes found.</td></tr>
            <?php endif; ?>
            </tbody>

            </table>
        </div>
    </div>
    </div>
</div>
</div>

---

<div class="modal fade" id="routeModal" tabindex="-1">
<div class="modal-dialog modal-xl"> 
    <div class="modal-content">

    <form id="routeForm">

        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Create Route Assignment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="clearMap()"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" name="route_id" id="route_id"> 
            <input type="hidden" name="action" id="routeAction" value="create">

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="route_name" id="routeName" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign Collector (Driver)</label>
                        <select name="driver" id="routeCollector" class="form-select" required>
                            <option value="">-- Select Collector --</option>
                            <?php if ($collectors): ?>
                                <?php foreach($collectors as $collector): ?>
                                    <option value="<?= htmlspecialchars($collector['id']) ?>">
                                        <?= htmlspecialchars($collector['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Collector assigned to this route.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assign Bins</label>
                        <select name="bin_ids[]" id="assignedBins" class="form-select" multiple size="8" required>
                            <?php if ($bins): ?>
                                <?php foreach($bins as $bin): ?>
                                    <option 
                                        value="<?= htmlspecialchars($bin['id']) ?>" 
                                        data-lat="<?= htmlspecialchars($bin['latitude']) ?>"
                                        data-lng="<?= htmlspecialchars($bin['longitude']) ?>"
                                        data-status="<?= htmlspecialchars($bin['status']) ?>" 
                                        data-fill="<?= htmlspecialchars($bin['fill_level_percent']) ?? 'N/A' ?>" >
                                        <?= htmlspecialchars($bin['bin_code']) ?> — <?= htmlspecialchars($bin['location_name']) ?> (<?= htmlspecialchars($bin['status']) ?> | Fill: <?= htmlspecialchars($bin['fill_level_percent']) ?? 'N/A' ?>%)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Hold **CTRL/CMD** to select multiple bins. Selected bins will appear on the map when modal is opened.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Route Status</label>
                        <select name="status" id="routeStatus" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <small class="text-muted">The current operational state of this route.</small>
                    </div>
                    </div>

                <div class="col-md-6">
                    <div id="routeMapInModal" class="shadow-sm"></div>
                    <small class="text-muted">Map in modal shows only selected bins for route planning.</small>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn btn-success" type="submit">
                <i class="bi bi-save"></i> Save Route
            </button>
        </div>

    </form>

    </div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Global variable to hold all bin data fetched by PHP
    const ALL_BINS_DATA = <?= json_encode($bins) ?>;

    // Global variables for map and markers
    let map = null; // The main page map
    let mainMarkerLayer = null;

    // --- 1. Custom Leaflet Icons based on Bin Status (Remains the same) ---
    const BinIcons = {
        // Fill Levels (Priority for collection)
        'Full': L.divIcon({ 
            className: 'custom-div-icon full-bin', 
            html: '<i class="bi bi-trash-fill full-icon"></i>', // Red marker
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
        'NearlyFull': L.divIcon({ 
            className: 'custom-div-icon nearly-full-bin', 
            html: '<i class="bi bi-trash-fill nearly-full-icon"></i>', // Yellow marker
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
        // Operational Statuses
        'Active': L.divIcon({ 
            className: 'custom-div-icon active-bin', 
            html: '<i class="bi bi-trash-fill active-icon"></i>', // Green marker
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
        'Inactive': L.divIcon({ 
            className: 'custom-div-icon inactive-bin', 
            html: '<i class="bi bi-trash-fill inactive-icon"></i>', // Gray marker
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
        'Maintenance': L.divIcon({ 
            className: 'custom-div-icon maintenance-bin', 
            html: '<i class="bi bi-tools maintenance-icon"></i>', // Blue marker
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
        // Default/Fallback
        'Default': L.divIcon({ 
            className: 'custom-div-icon default-bin', 
            html: '<i class="bi bi-trash-fill default-icon"></i>', 
            iconSize: [30, 42], 
            iconAnchor: [15, 42] 
        }),
    };

    /* Helper function for coloring badge in popup (Remains the same) */
    function getStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'full': return 'bg-danger';
            case 'nearly full': return 'bg-warning text-dark';
            case 'active': return 'bg-success';
            case 'inactive': return 'bg-secondary';
            case 'maintenance': return 'bg-info';
            default: return 'bg-primary';
        }
    }

    /* INITIALIZE MAIN MAP (Remains the same) */
    function initializeMainMap() {
        const mapContainerId = 'routeMap';
        if (map === null && document.getElementById(mapContainerId)) {
            map = L.map(mapContainerId).setView([14.5995, 120.9842], 12); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            mainMarkerLayer = L.layerGroup().addTo(map);
        }
    }

    /* DRAW ALL BINS on the main map view (Remains the same) */
    function drawAllBinsOnPageLoad() {
        if (map === null || ALL_BINS_DATA.length === 0) return;
        
        mainMarkerLayer.clearLayers();
        let bounds = [];
        
        ALL_BINS_DATA.forEach(bin => {
            const lat = parseFloat(bin.latitude);
            const lng = parseFloat(bin.longitude);
            const status = bin.status;
            const fill = bin.fill_level_percent;
            const binCode = bin.bin_code;
            const locationName = bin.location_name;

            if (!isNaN(lat) && !isNaN(lng)) {
                // Determine icon based on status (remove spaces)
                const iconKey = status.replace(/\s/g, ''); 
                const binIcon = BinIcons[iconKey] || BinIcons.Default;
                
                // Construct detailed popup content
                const popupContent = `
                    <div style="min-width: 150px;">
                        <b>Bin Code:</b> ${binCode}<br>
                        Location: ${locationName}<br>
                        Status: <span class="badge ${getStatusClass(status)}">${status}</span><br>
                        Fill Level: <b>${fill ?? 'N/A'}%</b>
                    </div>
                `;

                const marker = L.marker([lat, lng], { icon: binIcon })
                    .bindPopup(popupContent);
                
                mainMarkerLayer.addLayer(marker);
                bounds.push([lat, lng]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }

    // Modal Map Instance
    let modalMapInstance = null;
    let modalMarkerLayer = null;

    /* INITIALIZE MODAL MAP (Remains the same) */
    function initializeModalMap() {
        const mapContainerId = 'routeMapInModal';
        if (modalMapInstance === null && document.getElementById(mapContainerId)) {
            modalMapInstance = L.map(mapContainerId).setView([14.5995, 120.9842], 12); 
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(modalMapInstance);
            modalMarkerLayer = L.layerGroup().addTo(modalMapInstance);
        }
    }

    /* UPDATE MAP WITH SELECTED BINS (Used by the Modal only - Remains the same) */
    function updateMapWithBins() {
        initializeModalMap(); 

        if (!modalMapInstance || !modalMarkerLayer) return;
        
        modalMarkerLayer.clearLayers();

        let bounds = [];
        const assignedBinsSelect = document.getElementById('assignedBins');
        
        Array.from(assignedBinsSelect.options).forEach(opt => {
            if (opt.selected) {
                const lat = parseFloat(opt.dataset.lat);
                const lng = parseFloat(opt.dataset.lng);
                const status = opt.dataset.status; 
                const fill = opt.dataset.fill; 
                const binInfo = opt.text.trim();

                if (!isNaN(lat) && !isNaN(lng)) {
                    const iconKey = status.replace(/\s/g, ''); 
                    const binIcon = BinIcons[iconKey] || BinIcons.Default;
                    
                    const popupContent = `
                        <div style="min-width: 150px;">
                            <b>Bin Code:</b> ${binInfo.split('—')[0].trim()}<br>
                            Location: ${binInfo.split('—')[1].split('(')[0].trim()}<br>
                            Status: <span class="badge ${getStatusClass(status)}">${status}</span><br>
                            Fill Level: <b>${fill}%</b>
                        </div>
                    `;

                    const marker = L.marker([lat, lng], { icon: binIcon })
                        .bindPopup(popupContent);
                    
                    modalMarkerLayer.addLayer(marker);
                    bounds.push([lat, lng]);
                }
            }
        });

        if (bounds.length > 0) {
            modalMapInstance.fitBounds(bounds, { padding: [20, 20] });
        } else {
            modalMapInstance.setView([14.5995, 120.9842], 12);
        }
    }


    /* CLEAR MODAL MAP MARKERS (Remains the same) */
    function clearMap() {
        if (modalMarkerLayer) {
            modalMarkerLayer.clearLayers();
        }
    }


    /* RESET FORM FOR CREATE (Updated to include new status field and action) */
    function resetRouteForm() {
        document.getElementById("modalTitle").innerText = "Create Route Assignment";
        document.getElementById("routeForm").reset();
        document.getElementById("route_id").value = "";
        document.getElementById("routeAction").value = "create"; // Set action to create
        document.getElementById("routeStatus").value = "pending"; // Default status
        document.querySelectorAll("#assignedBins option").forEach(o => o.selected = false);
    }


    /* PAGE LOAD AND MODAL EVENT LISTENERS */
    document.addEventListener('DOMContentLoaded', () => {
        initializeMainMap();
        drawAllBinsOnPageLoad();
        
        document.getElementById('assignedBins').addEventListener('change', updateMapWithBins);

        const routeModal = document.getElementById('routeModal');
        
        initializeModalMap(); 
        
        routeModal.addEventListener('shown.bs.modal', function () {
            if (modalMapInstance) {
                modalMapInstance.invalidateSize(); 
            }
            updateMapWithBins();
        });

        routeModal.addEventListener('hidden.bs.modal', function () {
            clearMap();
        });
        
        // --- EDIT Route Listener (Updated Fetch Endpoint/Data Mapping) ---
        document.querySelectorAll('.editRouteBtn').forEach(button => {
            button.addEventListener('click', async () => {
                const id = button.dataset.id;
                document.getElementById("modalTitle").innerText = "Edit Route Assignment";
                document.getElementById("routeAction").value = "update"; // Set action to update

                // Assumes 'route/fetch_route.php' handles fetching the single collection_route row
                const res = await fetch("route/fetch_route.php?id=" + id);
                const data = await res.json();
                
                if (data.route) {
                    const route = data.route;
                    document.getElementById('route_id').value = route.id;
                    document.getElementById('routeName').value = route.route_name;
                    
                    // Map 'driver' FK to the select box
                    document.getElementById('routeCollector').value = route.driver || ''; 
                    
                    // Map 'status' to the select box
                    document.getElementById('routeStatus').value = route.status || 'pending'; 

                    // Get an array of bin IDs assigned to the route from the `bin_ids` array column
                    // Supabase often returns array types as stringified JSON or proper arrays, handle accordingly.
                    let selectedBins = route.bin_ids || [];
                    if (typeof selectedBins === 'string') {
                        try {
                           selectedBins = JSON.parse(selectedBins);
                        } catch (e) {
                           console.error("Failed to parse bin_ids array:", e);
                           selectedBins = [];
                        }
                    }

                    // Select the corresponding options in the multi-select
                    document.querySelectorAll('#assignedBins option').forEach(opt => {
                        opt.selected = selectedBins.includes(opt.value);
                    });
                    
                    // The 'shown.bs.modal' listener will handle updating the map once the modal is fully open
                } else {
                    alert("Error: Route data not found.");
                }
            });
        });

        // --- DELETE Route Listener (Remains the same) ---
        document.querySelectorAll('.deleteRouteBtn').forEach(button => {
            button.addEventListener('click', async () => {
                if (!confirm("Delete this route assignment? This will NOT delete the associated bins or collector profiles.")) return;

                const res = await fetch("route/delete_route.php", {
                    method: "POST",
                    headers: {"Content-Type":"application/x-www-form-urlencoded"},
                    body: "id=" + button.dataset.id
                });

                const msg = await res.text();
                if (msg.trim() === "success") location.reload();
                else alert(msg);
            });
        });

        // --- FORM SUBMISSION (Updated to use routeAction field) ---
        document.getElementById("routeForm").addEventListener("submit", async function(e){
            e.preventDefault();

            const formData = new FormData(this);
            const routeID = document.getElementById("route_id").value;
            const action = document.getElementById("routeAction").value;

            // Determine endpoint based on the action field set by resetRouteForm/editRouteBtn
            const endpoint = (action === "update") ? "route/update_route.php" : "route/save_route.php";

            const res = await fetch(endpoint, {
                method: "POST",
                body: formData
            });

            const msg = await res.text();
            if (msg.trim() === "success") {
                const modal = bootstrap.Modal.getInstance(document.getElementById('routeModal'));
                modal.hide();
                location.reload(); 
            }
            else alert(msg);
        });
    });

</script>
</body>
</html>