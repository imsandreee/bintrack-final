<?php
// collector/add_log.php

// Ensure paths are correct for your structure
require_once '../auth/config.php';
session_start();

// --- Collector Authorization Check ---
$collector_id = $_SESSION['user']['id'] ?? '';

if (!$collector_id) {
    header('Location: /login.php'); 
    exit;
}

// --- 1. Fetch Active Bins for Dropdown ---
// Goal: Fetch Bin ID, Bin Code, and Location Name for all active bins
$bins_data = supabase_fetch(
    "bins",
    "?status=eq.active&select=id,bin_code,location_name&order=location_name.asc"
);

$bins = [];
$error_message = '';

if (!is_array($bins_data) || isset($bins_data['error'])) {
    $error_message = $bins_data['error'] ?? 'Could not retrieve bins for the form.';
} else {
    $bins = $bins_data;
}

// --- 2. Set Default Date/Time (Current) ---
$default_datetime = date('Y-m-d\TH:i'); // Format required by input type="datetime-local"
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Collector Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/collector.css">
        
</head>
<body>

    <?php
    include '../includes/collector/navbar.php';
    ?>

    <div class="container-fluid p-0">

        <section id="addLogPage" class="page-content">
            <h1 class="mb-4 fw-bold">Manual Collection Log</h1>

            <div class="custom-card p-4 bg-white mx-auto shadow-sm" style="max-width: 600px;">
                <p class="text-muted mb-4">Use this form for collections not automatically logged by the system (e.g., manual pickups, error overrides).</p>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        **Data Error:** <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form id="addCollectionLogForm">
                    <div class="mb-3">
                        <label for="logBinSelect" class="form-label fw-semibold">Select Bin Location <span class="text-danger">*</span></label>
                        <select class="form-select" id="logBinSelect" name="bin_id" required>
                            <option selected disabled value="">Choose a Bin ID or Location...</option>
                            
                            <?php if (!empty($bins)): ?>
                                <?php foreach ($bins as $bin): ?>
                                    <option value="<?= htmlspecialchars($bin['id']) ?>">
                                        <?= htmlspecialchars($bin['bin_code']) ?> - <?= htmlspecialchars($bin['location_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No active bins found.</option>
                            <?php endif; ?>
                            
                            <option value="MANUAL">MANUAL (Record location in notes)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="logDateTime" class="form-label fw-semibold">Date & Time of Collection <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="logDateTime" name="collected_at" value="<?= htmlspecialchars($default_datetime) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="weightCollected" class="form-label fw-semibold">Weight Collected (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" min="0" class="form-control" id="weightCollected" name="weight_collected_kg" placeholder="e.g., 8.5" required>
                        <small class="text-muted">Enter the weight recorded by the truck's scale or estimated weight.</small>
                    </div>

                    <div class="mb-4">
                        <label for="logNotes" class="form-label fw-semibold">Notes / Remarks</label>
                        <textarea class="form-control" id="logNotes" name="remarks" rows="3" placeholder="Any special circumstances? (e.g., bin was jammed, access blocked, contamination noted)"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i> Log Collection Activity
                        </button>
                    </div>
                </form>
            </div>
        </section>

    </div> </main>
</div> <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-card">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-success" id="customAlertModalLabel"><i class="bi bi-truck-flatbed me-2"></i> BinTrack Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <p id="alertModalBody" class="lead"></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/collector.js"></script>
</body>
</html>