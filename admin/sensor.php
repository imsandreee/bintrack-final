<?php
require '../auth/config.php';

// Helper: GET from Supabase
function supabase_get($path) {
    $url = SUPABASE_URL . '/rest/v1/' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Accept: application/json'
        ]
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Join sensors + bins
$sensors = supabase_get(
    "sensors?select=id,bin_id,ultrasonic_enabled,load_cell_enabled,gps_enabled,microcontroller_type,max_weight_capacity,created_at,bins(bin_code,location_name)"
);
?>
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
            <h1 class="mb-3">Sensors Management</h1>
            <p class="text-muted">Remotely view and manage sensor modules installed in smart bins.</p>

            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bin</th>
                                <th>Ultrasonic</th>
                                <th>Load Cell</th>
                                <th>GPS</th>
                                <th>Microcontroller</th>
                                <th>Max Weight (kg)</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sensorTableBody">
                        <?php foreach ($sensors as $i => $s): ?>
                            <tr data-id="<?= $s['id'] ?>">
                                <td>
                                    <strong><?= htmlspecialchars($s['bins']['bin_code'] ?? 'N/A') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($s['bins']['location_name'] ?? '') ?></small>
                                </td>

                                <td><?= $s['ultrasonic_enabled'] ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>' ?></td>
                                <td><?= $s['load_cell_enabled'] ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>' ?></td>
                                <td><?= $s['gps_enabled'] ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>' ?></td>

                                <td><?= $s['microcontroller_type'] ?></td>
                                <td><?= $s['max_weight_capacity'] ?></td>
                                <td><?= date('Y-m-d', strtotime($s['created_at'])) ?></td>

                                <td>
                                    <button class="btn btn-sm btn-outline-success toggle-sensor" data-type="ultrasonic_enabled">
                                        <i class="bi bi-toggle-on"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success toggle-sensor" data-type="load_cell_enabled">
                                        <i class="bi bi-app-indicator"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success toggle-sensor" data-type="gps_enabled">
                                        <i class="bi bi-geo-alt"></i>
                                    </button>

                                    <button class="btn btn-sm btn-secondary show-uid-btn"
                                        data-sensor="<?= $s['id'] ?>"
                                        data-bin="<?= $s['bin_id'] ?>"
                                        data-code="<?= htmlspecialchars($s['bins']['bin_code'] ?? '') ?>">
                                        <i class="bi bi-cpu"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger delete-sensor">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="sensorActionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Remote Sensor Actions</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Hardware commands placeholder (ESP32/ESP8266).</p>
        <button class="btn btn-sm btn-success">Calibrate</button>
        <button class="btn btn-sm btn-warning">Restart</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="sensorUidModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ESP32 Sensor UID</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Bin Code</label>
          <input type="text" class="form-control" id="sensorBinCode" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">Bin UID (Use in ESP32)</label>
          <div class="input-group">
            <input type="text" class="form-control bg-light" id="sensorUidValue" readonly>
            <button class="btn btn-outline-secondary" id="copySensorUidBtn">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        </div>

        <div class="alert alert-info small">
          Paste this into your ESP32 firmware as:
          <code>const char* BIN_ID</code>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle sensor status → updates Supabase
document.querySelectorAll('.toggle-sensor').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
        const row = btn.closest('tr');
        const id = row.dataset.id;
        const column = btn.dataset.type;

        const badge = row.querySelector(
            `td:nth-child(${column==='ultrasonic_enabled'?2:column==='load_cell_enabled'?3:4}) span`
        );

        const isEnabled = badge.classList.contains('bg-success');
        const newValue = !isEnabled;

        const res = await fetch('sensor/update_sensor.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id, column, value: newValue })
        });

        if(res.ok){
            badge.className = 'badge ' + (newValue ? 'bg-success' : 'bg-danger');
            badge.textContent = newValue ? 'Enabled' : 'Disabled';
        } else {
            alert('Update failed');
        }
    });
});

// Delete sensor
document.querySelectorAll('.delete-sensor').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
        if(!confirm('Delete this sensor?')) return;
        const row = btn.closest('tr');
        const id = row.dataset.id;

        const res = await fetch('sensor/delete_sensor.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });

        if(res.ok){
            row.remove();
        } else {
            alert('Delete failed');
        }
    });
});

// Show UID modal
document.addEventListener('click', e => {
    const btn = e.target.closest('.show-uid-btn');
    if(!btn) return;

    const binId = btn.dataset.bin;
    const binCode = btn.dataset.code;

    document.getElementById('sensorBinCode').value = binCode;
    document.getElementById('sensorUidValue').value = binId;

    const modal = new bootstrap.Modal(document.getElementById('sensorUidModal'));
    modal.show();
});

// Copy UID
document.getElementById('copySensorUidBtn')?.addEventListener('click', ()=>{
    const input = document.getElementById('sensorUidValue');
    input.select();
    input.setSelectionRange(0,99999);
    document.execCommand('copy');

    const btn = document.getElementById('copySensorUidBtn');
    btn.innerHTML = '<i class="bi bi-check"></i>';
    setTimeout(()=> {
        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
    },1500);
});

</script>
</body>
</html>
