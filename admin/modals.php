<?php
require '../auth/config.php';

// Total Users
$users = supabase_fetch('profiles', '?select=id');
$total_users = count($users);

// Total Bins
$bins = supabase_fetch('bins', '?select=id');
$total_bins = count($bins);

// Active Alerts
$active_alerts = supabase_fetch('bin_alerts', '?select=id&resolved=eq.false');
$total_alerts = count($active_alerts);

// Collected Today (sum weight_collected_kg)
$today = date('Y-m-d');
$collected_today = supabase_fetch('collection_logs', "?select=weight_collected_kg&collected_at=gte.$today");
$total_collected = 0;
foreach ($collected_today as $log) {
    $total_collected += floatval($log['weight_collected_kg']);
}

// --- Waste Collected Trends (Monthly) ---
$currentYear = date('Y');
$monthlyData = array_fill(1, 12, 0); // Initialize Jan-Dec with 0

$collection_logs = supabase_fetch('collection_logs', "?select=collected_at,weight_collected_kg");
foreach ($collection_logs as $log) {
    $date = new DateTime($log['collected_at']);
    if ($date->format('Y') == $currentYear) {
        $month = (int)$date->format('n'); // 1-12
        $monthlyData[$month] += floatval($log['weight_collected_kg']);
    }
}

// --- Bins by Status ---
$bins = supabase_fetch('bins', '?select=status');
$binStatusCounts = ['active'=>0, 'inactive'=>0, 'maintenance'=>0];
foreach ($bins as $bin) {
    $status = $bin['status'];
    if(isset($binStatusCounts[$status])){
        $binStatusCounts[$status]++;
    }
}

// --- Recent Alerts (Latest 5) ---
$alerts = supabase_fetch('bin_alerts', '?select=id,bin_id,alert_type,message,created_at,resolved&order=created_at.desc&limit=5');
// Convert bin_id to bin_code
foreach ($alerts as &$alert) {
    $bin = supabase_fetch('bins', "?select=bin_code&id=eq.".$alert['bin_id']);
    $alert['bin_code'] = $bin[0]['bin_code'] ?? 'N/A';
}
unset($alert);

// --- Recent Collections (Latest 5) ---
$collections = supabase_fetch('collection_logs', '?select=collected_at,weight_collected_kg,bin_id,collector_id&order=collected_at.desc&limit=5');
foreach ($collections as &$c) {
    $bin = supabase_fetch('bins', "?select=bin_code&id=eq.".$c['bin_id']);
    $collector = supabase_fetch('profiles', "?select=full_name&id=eq.".$c['collector_id']);
    $c['bin_code'] = $bin[0]['bin_code'] ?? 'N/A';
    $c['collector_name'] = $collector[0]['full_name'] ?? 'N/A';
}
unset($c);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha2d1-Zcm7NMoRofWdD7R+A8S7iU0T7YV+vU8sWJ2hU0K7mP8g0T3Z0P8G0Y9z9E7VwL+8vQ"
        crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex" id="wrapper">

     <?php
    include '../includes/admin/sidebar.php'; // Includes the content of header.php
    ?>

    
    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper" class="w-100">

            <?php
        include '../includes/admin/topnavbar.php'; // Includes the content of header.php
        ?>


        <!-- Main Content Area (Pages) -->
         <!-- 2. Users Management Page -->
            <div id="users" class="page-content" style="display:none;">
                <h1 class="mb-4">Users Management</h1>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-circle me-1"></i> Add New User</button>
                    <div class="input-group w-auto">
                        <input type="text" class="form-control" placeholder="Search users...">
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="card p-4 shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Contact Number</th>
                                    <th>Address</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Alice Johnson</td>
                                    <td><span class="badge bg-success">Admin</span></td>
                                    <td>(555) 123-4567</td>
                                    <td>123 Main St</td>
                                    <td>2023-01-15</td>
                                    <td>
                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Bob Williams</td>
                                    <td><span class="badge bg-success">Collector</span></td>
                                    <td>(555) 987-6543</td>
                                    <td>456 Oak Ave</td>
                                    <td>2023-03-20</td>
                                    <td>
                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Citizen User</td>
                                    <td><span class="badge bg-secondary">Citizen</span></td>
                                    <td>N/A</td>
                                    <td>789 Pine Ln</td>
                                    <td>2023-05-10</td>
                                    <td>
                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /Users Management Page -->
    </div>
        </div>    

    <!-- /#page-content-wrapper -->

</div>
<!-- /#wrapper -->

<!-- Modals -->

<!-- 1. User Add/Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add/Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label for="fullName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullName" value="Alice Johnson">
          </div>
          <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role">
                <option>Admin</option>
                <option>Collector</option>
                <option selected>Citizen</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="contactNumber" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="contactNumber" value="(555) 123-4567">
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address">123 Main St</textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success">Save Changes</button>
      </div>
    </div>
  </div>
</div>


<!-- 4. Report Update Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportModalLabel">Update Citizen Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label class="form-label fw-bold">Issue:</label>
            <p>Bin B-101: Overflow (Bin overflowing onto sidewalk.)</p>
          </div>
          <div class="mb-3">
            <label for="reportStatus" class="form-label">Update Status</label>
            <select class="form-select" id="reportStatus">
                <option selected>Pending</option>
                <option>In Progress</option>
                <option>Resolved</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="updateRemarks" class="form-label">Internal Remarks</label>
            <textarea class="form-control" id="updateRemarks" rows="3" placeholder="Add notes on the investigation or resolution."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success">Update Report</button>
      </div>
    </div>
  </div>
</div>


<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Leaflet JS for Map functionality -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha2d1-s6uq4b2x2dE0Q2pL+B6gV+CgM6y8T1xL5ZgX8qT5z8Qz9T4P7z7Z7z7W7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G7z7H7z7I7z7J7z7K7z7L7z7M7z7N7z7O7z7P7z7Q7z7R7z7S7z7T7z7U7z7V7z7W7z7X7z7Y7z7Z7z7A7z7B7z7C7z7D7z7E7z7F7z7G+E0w=="
    crossorigin=""></script>

<script>
    // Mock data for Sensors
    let sensorData = [
        { bin: 'B-101', ultrasonicStatus: 'Enabled', loadCellStatus: 'Enabled', gpsStatus: 'Enabled', mcu: 'ESP32', maxWeight: 100, createdAt: '2023-01-01' },
        { bin: 'B-205', ultrasonicStatus: 'Disabled', loadCellStatus: 'Enabled', gpsStatus: 'Faulty', mcu: 'Arduino Nano', maxWeight: 50, createdAt: '2023-02-15' },
        { bin: 'B-310', ultrasonicStatus: 'Enabled', loadCellStatus: 'N/A', gpsStatus: 'Enabled', mcu: 'ESP8266', maxWeight: 75, createdAt: '2023-04-10' },
        { bin: 'B-400', ultrasonicStatus: 'Enabled', loadCellStatus: 'Disabled', gpsStatus: 'Enabled', mcu: 'ESP32', maxWeight: 120, createdAt: '2023-05-20' },
    ];

    // --- LEAFLET MAP GLOBALS ---
    let map = null;
    let marker = null;
    const DEFAULT_LAT = 41.8781; // Chicago Latitude (Default center)
    const DEFAULT_LNG = -87.6298; // Chicago Longitude

    /**
     * Updates the hidden input fields with the new coordinates.
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    function updateLocationInputs(lat, lng) {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        // Limit to 6 decimal places for readability and precision
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
    }

    /**
     * Initializes the Leaflet map and marker when the modal is shown.
     * This must be called only when the map container is visible.
     */
    function initializeMap() {
        const binMapContainer = document.getElementById('binMap');
        if (!binMapContainer) return;

        // Try to get current values from inputs for editing, otherwise use default
        const initialLat = parseFloat(document.getElementById('latitude').value) || DEFAULT_LAT;
        const initialLng = parseFloat(document.getElementById('longitude').value) || DEFAULT_LNG;
        
        // 1. Destroy existing map instance if it exists (important for modals)
        if (map) {
            map.remove();
            map = null;
        }

        // 2. Initialize Map
        map = L.map('binMap').setView([initialLat, initialLng], 13);
        
        // 3. Add Tile Layer (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // 4. Add Draggable Marker
        marker = L.marker([initialLat, initialLng], {
            draggable: true
        }).addTo(map);

        // 5. Update Inputs with Initial Marker Position
        updateLocationInputs(initialLat, initialLng);

        // 6. Add Event Listener for Marker Drag
        marker.on('dragend', function(e) {
            const newLatLng = marker.getLatLng();
            updateLocationInputs(newLatLng.lat, newLatLng.lng);
        });

        // 7. Handle Map Click (Allows setting marker by clicking)
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateLocationInputs(e.latlng.lat, e.latlng.lng);
        });

        // 8. Invalidate size to ensure map tiles render correctly after modal animation
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }
    
    // Function to handle form submission with map data
    function handleBinFormSubmit(e) {
        e.preventDefault();
        
        const binCode = document.getElementById('binCode').value;
        const locationName = document.getElementById('location').value;
        const latitude = document.getElementById('latitude').value;
        const longitude = document.getElementById('longitude').value;
        const binStatus = document.getElementById('binStatus').value;
        
        // This is where you would send data to the backend/Firestore
        console.log('--- Submitting Bin Data ---');
        console.log(`Code: ${binCode}, Location: ${locationName}, Lat: ${latitude}, Lon: ${longitude}, Status: ${binStatus}`);
        
        // Close modal after submission (or success response)
        const binModalElement = document.getElementById('binModal');
        const modal = bootstrap.Modal.getInstance(binModalElement) || new bootstrap.Modal(binModalElement);
        modal.hide();

        // Optional: Re-render the Bins table if necessary (not implemented in this mock)
    }

    document.addEventListener('DOMContentLoaded', () => {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar-wrapper');
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        const navLinks = document.querySelectorAll('.sidebar .nav-link, a[data-page]');
        const sensorTableBody = document.getElementById('sensorTableBody');
        const binModalElement = document.getElementById('binModal');
        const binForm = document.getElementById('binForm');

        // --- BIN MODAL MAP INTEGRATION ---
        // 1. Listen for the modal to be fully visible
        if (binModalElement) {
            binModalElement.addEventListener('shown.bs.modal', initializeMap);
        }
        
        // 2. Attach submit handler to the form
        if (binForm) {
             binForm.addEventListener('submit', handleBinFormSubmit);
        }

        // --- Utility Functions (Existing) ---

        // Helper to get badge color based on status
        function getStatusBadge(status) {
            if (status === 'Enabled') return `<span class="badge bg-success">${status}</span>`;
            if (status === 'Disabled') return `<span class="badge bg-danger">${status}</span>`;
            if (status === 'Faulty') return `<span class="badge bg-warning text-dark">${status}</span>`;
            if (status === 'N/A') return `<span class="badge bg-secondary">${status}</span>`;
            return `<span class="badge bg-secondary">${status}</span>`;
        }
        
        // Renders the sensor table rows
        function renderSensorTable() {
            if (!sensorTableBody) return;

            sensorTableBody.innerHTML = ''; // Clear existing rows

            sensorData.forEach((sensor, index) => {
                const row = sensorTableBody.insertRow();
                row.innerHTML = `
                    <td>${sensor.bin}</td>
                    <td id="ultrasonic-${index}">${getStatusBadge(sensor.ultrasonicStatus)}</td>
                    <td id="loadCell-${index}">${getStatusBadge(sensor.loadCellStatus)}</td>
                    <td id="gps-${index}">${getStatusBadge(sensor.gpsStatus)}</td>
                    <td>${sensor.mcu}</td>
                    <td>${sensor.maxWeight}</td>
                    <td>${sensor.createdAt}</td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Sensor Actions">
                            <!-- Ultrasonic Toggle -->
                            <button class="btn btn-sm btn-outline-success me-2 sensor-toggle" 
                                data-sensor-type="ultrasonic" 
                                data-sensor-index="${index}" 
                                data-current-status="${sensor.ultrasonicStatus}"
                                title="${sensor.ultrasonicStatus === 'Enabled' ? 'Disable Ultrasonic' : 'Enable Ultrasonic'}"
                                ${sensor.ultrasonicStatus === 'N/A' || sensor.ultrasonicStatus === 'Faulty' ? 'disabled' : ''}>
                                <i class="bi bi-toggle-on"></i>
                            </button>
                            
                            <!-- Load Cell Toggle -->
                            <button class="btn btn-sm btn-outline-success me-2 sensor-toggle" 
                                data-sensor-type="loadCell" 
                                data-sensor-index="${index}" 
                                data-current-status="${sensor.loadCellStatus}"
                                title="${sensor.loadCellStatus === 'Enabled' ? 'Disable Load Cell' : 'Enable Load Cell'}"
                                ${sensor.loadCellStatus === 'N/A' || sensor.loadCellStatus === 'Faulty' ? 'disabled' : ''}>
                                <i class="bi bi-app-indicator"></i>
                            </button>

                             <!-- Other Actions (e.g., Calibrate, Delete) -->
                            <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#sensorActionModal" title="Advanced Actions"><i class="bi bi-cpu"></i></button>
                            <button class="btn btn-sm btn-danger" title="Remove Sensor"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                `;
            });

            // Re-attach listeners for new buttons
            document.querySelectorAll('.sensor-toggle').forEach(button => {
                button.addEventListener('click', toggleSensorStatus);
                updateToggleButtonAppearance(button);
            });
        }
        
        // Function to update the appearance (color/icon) of the toggle button
        function updateToggleButtonAppearance(button) {
            const currentStatus = button.getAttribute('data-current-status');
            const isEnabled = currentStatus === 'Enabled';
            
            button.classList.remove('btn-outline-success', 'btn-outline-danger');
            button.classList.add(isEnabled ? 'btn-outline-danger' : 'btn-outline-success');
            
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-toggle-on', 'bi-toggle-off', 'bi-app-indicator', 'bi-slash-circle');
                // Use different icons based on sensor type for visual distinction
                if (button.getAttribute('data-sensor-type') === 'ultrasonic') {
                    icon.classList.add(isEnabled ? 'bi-toggle-on' : 'bi-toggle-off');
                } else if (button.getAttribute('data-sensor-type') === 'loadCell') {
                    icon.classList.add(isEnabled ? 'bi-app-indicator' : 'bi-slash-circle');
                }
            }
            button.title = isEnabled ? `Disable ${button.getAttribute('data-sensor-type')}` : `Enable ${button.getAttribute('data-sensor-type')}`;
        }

        // Action handler for toggling sensor status
        function toggleSensorStatus(event) {
            const button = event.currentTarget;
            const index = parseInt(button.getAttribute('data-sensor-index'));
            const type = button.getAttribute('data-sensor-type'); // 'ultrasonic' or 'loadCell'
            const currentStatus = button.getAttribute('data-current-status');
            
            if (currentStatus === 'N/A' || currentStatus === 'Faulty') {
                console.warn(`Cannot toggle status for a ${currentStatus} sensor.`);
                return;
            }

            const newStatus = currentStatus === 'Enabled' ? 'Disabled' : 'Enabled';
            const statusKey = `${type}Status`;
            const cellId = `${type}-${index}`;
            
            // 1. Update Mock Data
            sensorData[index][statusKey] = newStatus;

            // 2. Update the badge in the table
            const statusCell = document.getElementById(cellId);
            if (statusCell) {
                statusCell.innerHTML = getStatusBadge(newStatus);
            }

            // 3. Update the button's internal state and appearance
            button.setAttribute('data-current-status', newStatus);
            updateToggleButtonAppearance(button);

            // 4. Log the action (In a real app, this would be an API call)
            console.log(`Bin ${sensorData[index].bin}: ${type} sensor status changed from ${currentStatus} to ${newStatus}.`);

            // Provide a visual cue
            const alertPlaceholder = document.createElement('div');
            alertPlaceholder.innerHTML = `<div class="alert alert-${newStatus === 'Enabled' ? 'success' : 'warning'} alert-dismissible fade show" role="alert">
                ${sensorData[index].bin}'s ${type} sensor is now <strong>${newStatus.toUpperCase()}</strong>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            document.querySelector('#sensors h1').insertAdjacentElement('afterend', alertPlaceholder);
            setTimeout(() => { alertPlaceholder.remove(); }, 3000);
        }

        // --- Sidebar Toggle for Mobile ---
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // --- Dark/Light Mode Toggle ---
        function setMode(isDark) {
            if (isDark) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                darkModeToggle.innerHTML = '<i class="bi bi-sun-fill" data-mode="light"></i>';
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                darkModeToggle.innerHTML = '<i class="bi bi-moon-fill" data-mode="dark"></i>';
            }
        }

        // Initialize theme from localStorage
        const preferredTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        setMode(preferredTheme === 'dark');

        // Toggle button handler
        darkModeToggle.addEventListener('click', () => {
            setMode(!body.classList.contains('dark-mode'));
        });

        // --- Page Navigation Logic (Client-side routing) ---
        function setActivePage(pageId) {
            // Hide all pages
            document.querySelectorAll('.page-content').forEach(page => {
                page.style.display = 'none';
            });

            // Show the requested page
            const targetPage = document.getElementById(pageId);
            if (targetPage) {
                targetPage.style.display = 'block';
                // Special action for the sensor page: re-render the dynamic table
                if (pageId === 'sensors') {
                    renderSensorTable();
                }
            } else {
                console.error(`Page '${pageId}' not found.`);
                document.getElementById('dashboard').style.display = 'block'; // Fallback
            }

            // Update active link in sidebar
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`.sidebar .nav-link[data-page="${pageId}"]`)?.classList.add('active');

            // Hide sidebar on mobile after navigation
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
            }
        }

        // Attach event listeners to all navigation links
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pageId = link.getAttribute('data-page');
                if (pageId) {
                    setActivePage(pageId);
                }
            });
        });

        // Set initial page view
        setActivePage('dashboard');
    });
</script>

</body>
</html>