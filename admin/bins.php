<?php
require '../auth/config.php'; // Defines SUPABASE_URL and SUPABASE_KEY

// Helper: basic Supabase REST GET (server-side)
function supabase_get($path, $query = '') {
    // In a real application, proper error handling and logging should be more robust
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY')) {
        throw new Exception("Supabase configuration missing.");
    }
    $url = SUPABASE_URL . '/rest/v1/' . $path . ($query ? $query : '');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Accept: application/json'
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new Exception($err);
    
    $data = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response: " . json_last_error_msg());
    }
    return $data;
}

// Fetch bins server-side to render initial table
$bins = [];
try {
    // Optimized select to fetch bins, their capacity (from sensors), and all readings.
    // NOTE: This will fetch ALL readings for the joined tables. 
    // The PHP logic below manually finds the latest reading, which is inefficient 
    // for a large dataset. For production, define a PostgreSQL VIEW/RPC to find the 
    // LAST sensor reading per bin on the database side before fetching.
    $bins_data = supabase_get('bins', 
        '?select=id,bin_code,location_name,latitude,longitude,status,installation_date,last_communication,sensors(max_weight_capacity),sensor_readings(load_cell_weight_kg,timestamp)&order=installation_date.desc');

    if (is_array($bins_data)) {
        foreach ($bins_data as $bin) {
            
            // Extract Max Capacity from the linked 'sensors' table
            // Default to 10.0 if sensor data is missing
            $capacity = $bin['sensors'][0]['max_weight_capacity'] ?? 10.0; 

            // Find the actual latest reading based on timestamp (PHP-side sort)
            $latest_weight = 0.0;
            $latest_timestamp = null;

            if (!empty($bin['sensor_readings'])) {
                // Sort readings by timestamp in descending order in PHP to find the latest
                usort($bin['sensor_readings'], function($a, $b) {
                    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
                });

                // Get the latest reading
                $latest_reading = $bin['sensor_readings'][0];
                $latest_weight = $latest_reading['load_cell_weight_kg'] ?? 0.0;
                $latest_timestamp = $latest_reading['timestamp'] ?? null;
            }

            // Calculate Fill Percentage
            $fill_percent = ($capacity > 0) ? min(100, round(($latest_weight / $capacity) * 100)) : 0;
            
            // Use the latest sensor reading timestamp if it's more recent than the bin's stored communication time
            if ($latest_timestamp && (!$bin['last_communication'] || strtotime($latest_timestamp) > strtotime($bin['last_communication']))) {
                $bin['last_communication'] = $latest_timestamp;
            }

            $bins[] = [
                'id' => $bin['id'],
                'bin_code' => $bin['bin_code'],
                'location_name' => $bin['location_name'],
                'latitude' => $bin['latitude'],
                'longitude' => $bin['longitude'],
                'status' => $bin['status'],
                'installation_date' => $bin['installation_date'],
                'last_communication' => $bin['last_communication'],
                
                // New Calculated/Derived Data Points for quick access
                'latest_weight_kg' => $latest_weight,
                'max_capacity_kg' => $capacity,
                'fill_percent' => $fill_percent
            ];
        }
    }

} catch (Exception $e) {
    // Log the error and display an empty table gracefully
    error_log("Supabase fetch error: " . $e->getMessage());
    $bins = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>BinTrack Admin Dashboard - Bins</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#binMap{height:400px;width:100%;margin-bottom:1rem;}
/* Style for progress bar labels */
.progress-container { margin-top: 10px; }
.progress-label { font-weight: bold; margin-bottom: 5px; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="d-flex" id="wrapper">
    <?php include '../includes/admin/sidebar.php'; ?>
    <div id="page-content-wrapper" class="w-100">
        <?php include '../includes/admin/topnavbar.php'; ?>

        <div id="bins" class="page-content p-4">
            <h1 class="mb-4">Bins Management</h1>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#binModal" id="addBinBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add New Bin
                </button>
                <div class="input-group w-auto">
                    <input type="text" class="form-control" placeholder="Search bins..." id="binSearch">
                    <button class="btn btn-outline-secondary" type="button" id="doSearch"><i class="bi bi-search"></i></button>
                </div>
            </div>

            <div class="card p-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="binsTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Location</th>
                                <th>Fill/Weight</th>
                                <th>Status</th>
                                <th>Installation Date</th>
                                <th>Last Comms</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="binsTbody">
                            <?php if(!empty($bins)): ?>
                                <?php foreach($bins as $bin): 
                                    $fill_percent = $bin['fill_percent'];
                                    $fill_class = match(true) {
                                        $fill_percent >= 90 => 'bg-danger',
                                        $fill_percent >= 70 => 'bg-warning text-dark',
                                        $fill_percent > 0 => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                    $status = strtolower($bin['status'] ?? 'active');
                                    $badgeClass = match($status) {
                                        'active' => 'bg-success',
                                        'maintenance' => 'bg-warning text-dark',
                                        'inactive' => 'bg-secondary',
                                        default => 'bg-dark'
                                    };
                                ?>
                                    <tr id="bin-<?= htmlspecialchars($bin['id']) ?>">
                                        <td><?= htmlspecialchars($bin['bin_code']) ?></td>
                                        <td><?= htmlspecialchars($bin['location_name']) ?></td>
                                        <td>
                                            <div class="progress" role="progressbar" style="width: 100px;">
                                                <div class="progress-bar <?= $fill_class ?>" style="width: <?= $fill_percent ?>%"><?= $fill_percent ?>%</div>
                                            </div>
                                            <small class="text-muted"><?= number_format($bin['latest_weight_kg'], 2) ?> kg / <?= number_format($bin['max_capacity_kg'], 2) ?> kg</small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                        </td>
                                        <td><?= !empty($bin['installation_date']) ? date('Y-m-d', strtotime($bin['installation_date'])) : '' ?></td>
                                        <td><?= !empty($bin['last_communication']) ? date('Y-m-d H:i', strtotime($bin['last_communication'])) : 'N/A' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-dark me-1 view-data-btn"
                                                data-id="<?= htmlspecialchars($bin['id']) ?>"
                                                data-code="<?= htmlspecialchars($bin['bin_code']) ?>"
                                                data-weight="<?= htmlspecialchars(number_format($bin['latest_weight_kg'], 2, '.', '')) ?>"
                                                data-capacity="<?= htmlspecialchars(number_format($bin['max_capacity_kg'], 2, '.', '')) ?>"
                                                data-fill="<?= htmlspecialchars($bin['fill_percent']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#dataModal">
                                                <i class="bi bi-bar-chart-fill"></i>
                                            </button>

                                            <button class="btn btn-sm btn-primary me-1 show-uid-btn"
                                                data-id="<?= htmlspecialchars($bin['id']) ?>"
                                                data-code="<?= htmlspecialchars($bin['bin_code']) ?>">
                                                <i class="bi bi-cpu"></i>
                                            </button>

                                            <button class="btn btn-sm btn-info me-1 edit-bin-btn"
                                                data-id="<?= htmlspecialchars($bin['id']) ?>"
                                                data-bin_code="<?= htmlspecialchars($bin['bin_code']) ?>"
                                                data-location="<?= htmlspecialchars($bin['location_name']) ?>"
                                                data-latitude="<?= htmlspecialchars($bin['latitude']) ?>"
                                                data-longitude="<?= htmlspecialchars($bin['longitude']) ?>"
                                                data-status="<?= htmlspecialchars($bin['status']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#binModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button class="btn btn-sm btn-danger delete-bin-btn"
                                                data-id="<?= htmlspecialchars($bin['id']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted">No bins found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="binModal" tabindex="-1" aria-labelledby="binModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="binForm">
      <div class="modal-header">
        <h5 class="modal-title" id="binModalLabel">Add/Edit Bin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="binId" name="id">
          <div class="mb-3">
            <label for="binCode" class="form-label">Bin Code</label>
            <input type="text" class="form-control" id="binCode" name="bin_code" required>
          </div>
          <div class="mb-3">
            <label for="location" class="form-label">Location Name</label>
            <input type="text" class="form-control" id="location" name="location_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Select Location on Map</label>
            <div id="binMap"></div>
            <small class="text-muted">Drag the marker or click on the map to set the exact bin location.</small>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
                <label for="latitude" class="form-label">Latitude</label>
                <input type="text" class="form-control bg-light" id="latitude" name="latitude" readonly required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="longitude" class="form-label">Longitude</label>
                <input type="text" class="form-control bg-light" id="longitude" name="longitude" readonly required>
            </div>
          </div>
          <div class="mb-3">
            <label for="binStatus" class="form-label">Status</label>
            <select class="form-select" id="binStatus" name="status">
                <option value="active" selected>Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
            </select>
          </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
        <button type="submit" class="btn btn-success" id="saveBinBtn">Save Changes</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="binUidModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          ESP32 Bin UID
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Bin Code</label>
          <input type="text" class="form-control" id="uidBinCode" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">UID (Use in ESP32 Code)</label>
          <div class="input-group">
            <input type="text" class="form-control bg-light" id="uidValue" readonly>
            <button class="btn btn-outline-secondary" id="copyUidBtn">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        </div>

        <div class="alert alert-info small">
          Paste this UID inside your ESP32 firmware as <code>BIN_ID</code>.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="dataModalLabel"><i class="bi bi-bar-chart-fill me-2"></i> Real-Time Bin Data: <span id="dataBinCode"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="text-primary">Fill Level Status</h6>
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Fill Level Percentage (Calculated)</span>
                        <span id="dataFillPercent"></span>
                    </div>
                    <div class="progress" style="height: 30px;">
                        <div id="fillProgressBar" class="progress-bar" role="progressbar"></div>
                    </div>
                    <div class="text-muted small mt-2">Current estimated fill level based on sensor readings.</div>
                </div>

                <hr class="my-4">

                <h6 class="text-primary">Load Cell Weight</h6>
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Current Weight (Load Cell)</span>
                        <span id="dataCurrentWeight"></span>
                    </div>
                    <div class="progress" style="height: 30px;">
                        <div id="weightProgressBar" class="progress-bar" role="progressbar"></div>
                    </div>
                    <div class="text-muted small mt-2">Maximum Capacity: <span id="dataMaxCapacity"></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const DEFAULT_LAT = 14.6760; // Manila example
const DEFAULT_LNG = 121.0437; 

let map, marker;
const binModalEl = document.getElementById('binModal');
const dataModalEl = document.getElementById('dataModal');

// --- Helper Functions ---
function updateLocationInputs(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}
function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s]);
}
function escapeAttr(text){ return escapeHtml(text).replace(/"/g,'&quot;'); }
function capitalize(s){ return s ? s.charAt(0).toUpperCase()+s.slice(1) : ''; }

// --- Map Initialization ---
function initializeMap() {
    const binMapContainer = document.getElementById('binMap');
    if (!binMapContainer) return;

    const initialLat = parseFloat(document.getElementById('latitude').value) || DEFAULT_LAT;
    const initialLng = parseFloat(document.getElementById('longitude').value) || DEFAULT_LNG;

    if (map) map.remove();

    map = L.map('binMap').setView([initialLat, initialLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
    updateLocationInputs(initialLat, initialLng);

    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        updateLocationInputs(pos.lat, pos.lng);
    });

    map.on('click', e => {
        marker.setLatLng(e.latlng);
        updateLocationInputs(e.latlng.lat, e.latlng.lng);
    });

    // Fix map tile display issue inside Bootstrap modal
    setTimeout(()=> map.invalidateSize(), 100);
}

// --- Data Fetching and Rendering ---

/**
 * Fetches the latest bin data and rebuilds the table body.
 * NOTE: This relies on bins/list_bins.php returning latest_weight_kg, max_capacity_kg, and fill_percent.
 */
async function refreshBins() {
    const res = await fetch('bins/list_bins.php');
    if (!res.ok) {
        console.error('Failed to fetch bins');
        return;
    }
    const bins = await res.json();
    const tbody = document.getElementById('binsTbody');
    tbody.innerHTML = '';
    if (!bins || bins.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No bins found</td></tr>';
        return;
    }
    for (const bin of bins) {
        const fill_percent = bin.fill_percent ?? 0;
        const latest_weight_kg = bin.latest_weight_kg ?? 0;
        const max_capacity_kg = bin.max_capacity_kg ?? 10.0;
        
        let fillClass = 'bg-secondary';
        if (fill_percent >= 90) {
            fillClass = 'bg-danger';
        } else if (fill_percent >= 70) {
            fillClass = 'bg-warning text-dark';
        } else if (fill_percent > 0) {
            fillClass = 'bg-success';
        }
        
        const status = (bin.status || 'active').toLowerCase();
        let badgeClass = 'bg-dark';
        if (status === 'active') badgeClass = 'bg-success';
        if (status === 'maintenance') badgeClass = 'bg-warning text-dark';
        if (status === 'inactive') badgeClass = 'bg-secondary';

        const tr = document.createElement('tr');
        tr.id = 'bin-' + bin.id;
        tr.innerHTML = `
            <td>${escapeHtml(bin.bin_code || '')}</td>
            <td>${escapeHtml(bin.location_name || '')}</td>
            <td>
                <div class="progress" role="progressbar" style="width: 100px;">
                    <div class="progress-bar ${fillClass}" style="width: ${fill_percent}%">${fill_percent}%</div>
                </div>
                <small class="text-muted">${latest_weight_kg.toFixed(2)} kg / ${max_capacity_kg.toFixed(2)} kg</small>
            </td>
            <td><span class="badge ${badgeClass}">${capitalize(status)}</span></td>
            <td>${bin.installation_date ? bin.installation_date.split('T')[0] : ''}</td>
            <td>${bin.last_communication ? new Date(bin.last_communication).toLocaleString() : 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-dark me-1 view-data-btn"
                    data-id="${bin.id}"
                    data-code="${escapeAttr(bin.bin_code)}"
                    data-weight="${latest_weight_kg.toFixed(2)}"
                    data-capacity="${max_capacity_kg.toFixed(2)}"
                    data-fill="${fill_percent}"
                    data-bs-toggle="modal" data-bs-target="#dataModal">
                    <i class="bi bi-bar-chart-fill"></i>
                </button>
              <button class="btn btn-sm btn-primary me-1 show-uid-btn"
                data-id="${bin.id}" data-code="${escapeAttr(bin.bin_code)}">
                <i class="bi bi-cpu"></i>
              </button>
              <button class="btn btn-sm btn-info me-1 edit-bin-btn" data-id="${bin.id}"
                data-bin_code="${escapeAttr(bin.bin_code)}" data-location="${escapeAttr(bin.location_name)}"
                data-latitude="${bin.latitude}" data-longitude="${bin.longitude}" data-status="${bin.status}"
                data-bs-toggle="modal" data-bs-target="#binModal">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-danger delete-bin-btn" data-id="${bin.id}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    attachEventHandlers(); 
}

// --- Event Handlers ---

function attachEventHandlers() {
    // 1. Add/Edit Modal Map Initialization
    binModalEl.addEventListener('shown.bs.modal', initializeMap);

    // 2. Clear modal for Add New
    document.getElementById('addBinBtn').addEventListener('click', () => {
        document.getElementById('binForm').reset();
        document.getElementById('binId').value = '';
        document.getElementById('latitude').value = DEFAULT_LAT;
        document.getElementById('longitude').value = DEFAULT_LNG;
    });

    // 3. Populate modal when editing (for dynamically loaded elements)
    document.querySelectorAll('.edit-bin-btn').forEach(btn => {
        btn.onclick = () => {
            document.getElementById('binId').value = btn.dataset.id;
            document.getElementById('binCode').value = btn.dataset.bin_code;
            document.getElementById('location').value = btn.dataset.location;
            document.getElementById('latitude').value = btn.dataset.latitude;
            document.getElementById('longitude').value = btn.dataset.longitude;
            document.getElementById('binStatus').value = btn.dataset.status;
            const m = new bootstrap.Modal(binModalEl);
            m.show();
        };
    });

    // 4. Submit handler: create or update via our server endpoints
    document.getElementById('binForm').onsubmit = async (ev) => {
        ev.preventDefault();
        const id = document.getElementById('binId').value;
        const payload = {
            bin_code: document.getElementById('binCode').value.trim(),
            location_name: document.getElementById('location').value.trim(),
            latitude: parseFloat(document.getElementById('latitude').value),
            longitude: parseFloat(document.getElementById('longitude').value),
            status: document.getElementById('binStatus').value
        };
        try {
            const url = id ? 'bins/update_bin.php' : 'bins/create_bin.php';
            const res = await fetch(url, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(id ? { id, ...payload } : payload)
            });
            const result = await res.json();
            if (res.ok) {
                await refreshBins();
                const modal = bootstrap.Modal.getInstance(binModalEl);
                modal.hide();
            } else {
                alert('Error: ' + (result.message || JSON.stringify(result)));
            }
        } catch (err) {
            alert('Network or server error: ' + err);
        }
    };

    // 5. Delete handler (for dynamically loaded elements)
    document.querySelectorAll('.delete-bin-btn').forEach(btn => {
        btn.onclick = async () => {
            if (!confirm('Delete this bin? This operation cannot be undone.')) return;
            const id = btn.dataset.id;
            try {
                const res = await fetch('bins/delete_bin.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ id })
                });
                const j = await res.json();
                if (res.ok) {
                    await refreshBins();
                } else alert('Error: ' + (j.message || JSON.stringify(j)));
            } catch (err) {
                alert('Delete failed: ' + err);
            }
        };
    });

    // 6. View Data Handler (Sensor Data Modal) - CORRECTED
    document.querySelectorAll('.view-data-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const binCode = btn.dataset.code;
            // Parse as float using dataset
            const currentWeight = parseFloat(btn.dataset.weight);
            const maxCapacity = parseFloat(btn.dataset.capacity);
            const fillPercent = parseInt(btn.dataset.fill);

            // --- Update Modal Title ---
            document.getElementById('dataBinCode').textContent = binCode;

            // --- Determine Fill Class (Color) ---
            let fillClass = 'bg-secondary';
            if (fillPercent >= 90) {
                fillClass = 'bg-danger';
            } else if (fillPercent >= 70) {
                fillClass = 'bg-warning text-dark';
            } else if (fillPercent > 0) {
                fillClass = 'bg-success';
            }

            // --- Update Fill Level Progress Bar ---
            const fillProgressBar = document.getElementById('fillProgressBar');
            document.getElementById('dataFillPercent').textContent = `${fillPercent}%`;
            
            fillProgressBar.className = `progress-bar ${fillClass}`;
            fillProgressBar.style.width = `${fillPercent}%`;
            fillProgressBar.textContent = `${fillPercent}%`;


            // --- Update Weight Data Progress Bar ---
            const weightProgressBar = document.getElementById('weightProgressBar');
            document.getElementById('dataCurrentWeight').textContent = `${currentWeight.toFixed(2)} kg`;
            document.getElementById('dataMaxCapacity').textContent = `${maxCapacity.toFixed(2)} kg`;

            // Calculate the percentage of current weight relative to max capacity
            const weightPercent = (currentWeight / maxCapacity) * 100;
            
            weightProgressBar.className = `progress-bar ${fillClass}`; 
            weightProgressBar.style.width = `${weightPercent}%`;
            weightProgressBar.textContent = `${currentWeight.toFixed(2)} kg`;
        });
    });

    // 7. Show UID modal (for dynamically loaded elements)
    document.querySelectorAll('.show-uid-btn').forEach(btn => {
        btn.onclick = () => {
            document.getElementById('uidBinCode').value = btn.dataset.code;
            document.getElementById('uidValue').value = btn.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('binUidModal'));
            modal.show();
        };
    });
}

// 8. Copy to clipboard
document.getElementById('copyUidBtn')?.addEventListener('click', () => {
    const input = document.getElementById('uidValue');
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');

    const btn = document.getElementById('copyUidBtn');
    btn.innerHTML = '<i class="bi bi-check"></i>';
    setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
    }, 1500);
});

// 9. Search functionality
document.getElementById('doSearch').addEventListener('click', async () => {
    const q = document.getElementById('binSearch').value.trim().toLowerCase();
    const url = q ? 'bins/list_bins.php?q=' + encodeURIComponent(q) : 'bins/list_bins.php';
    
    const res = await fetch(url);
    if (res.ok) {
        const filtered = await res.json();
        const tbody = document.getElementById('binsTbody');
        tbody.innerHTML = '';

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No bins found</td></tr>';
        } else {
            // Re-render the search results (similar to refreshBins but using filtered data)
            for (const bin of filtered) {
                const fill_percent = bin.fill_percent ?? 0;
                const latest_weight_kg = bin.latest_weight_kg ?? 0;
                const max_capacity_kg = bin.max_capacity_kg ?? 10.0;
                
                let fillClass = 'bg-secondary';
                if (fill_percent >= 90) {
                    fillClass = 'bg-danger';
                } else if (fill_percent >= 70) {
                    fillClass = 'bg-warning text-dark';
                } else if (fill_percent > 0) {
                    fillClass = 'bg-success';
                }

                const status = (bin.status || 'active').toLowerCase();
                let badgeClass = 'bg-dark';
                if (status === 'active') badgeClass = 'bg-success';
                if (status === 'maintenance') badgeClass = 'bg-warning text-dark';
                if (status === 'inactive') badgeClass = 'bg-secondary';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(bin.bin_code)}</td>
                    <td>${escapeHtml(bin.location_name)}</td>
                    <td>
                        <div class="progress" role="progressbar" style="width: 100px;">
                            <div class="progress-bar ${fillClass}" style="width: ${fill_percent}%">${fill_percent}%</div>
                        </div>
                        <small class="text-muted">${latest_weight_kg.toFixed(2)} kg / ${max_capacity_kg.toFixed(2)} kg</small>
                    </td>
                    <td><span class="badge ${badgeClass}">${capitalize(bin.status)}</span></td>
                    <td>${bin.installation_date?bin.installation_date.split('T')[0]:''}</td>
                    <td>${bin.last_communication?new Date(bin.last_communication).toLocaleString():'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-dark me-1 view-data-btn"
                            data-id="${bin.id}"
                            data-code="${escapeAttr(bin.bin_code)}"
                            data-weight="${latest_weight_kg.toFixed(2)}"
                            data-capacity="${max_capacity_kg.toFixed(2)}"
                            data-fill="${fill_percent}"
                            data-bs-toggle="modal" data-bs-target="#dataModal">
                            <i class="bi bi-bar-chart-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-primary me-1 show-uid-btn" data-id="${bin.id}" data-code="${escapeAttr(bin.bin_code)}"><i class="bi bi-cpu"></i></button>
                        <button class="btn btn-sm btn-info me-1 edit-bin-btn" data-id="${bin.id}" data-bin_code="${escapeAttr(bin.bin_code)}" data-location="${escapeAttr(bin.location_name)}" data-latitude="${bin.latitude}" data-longitude="${bin.longitude}" data-status="${bin.status}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger delete-bin-btn" data-id="${bin.id}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
                tbody.appendChild(tr);
            }
            attachEventHandlers(); // Re-attach handlers after search
        }
    } else {
        alert('Search failed.');
    }
});

// Initial attachment of handlers to the server-rendered table data
document.addEventListener('DOMContentLoaded', () => {
    attachEventHandlers();
});

</script>
</body>
</html>