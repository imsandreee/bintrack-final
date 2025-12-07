<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Collector Interface</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts (Poppins for modern, clean look) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/collector.css">
        
</head>
<body>

        <?php
include '../includes/collector/navbar.php';
?>


            <div class="container-fluid p-0">

                <!-- ======================================================= -->
                <!-- 5. Add Collection Log Page -->
                <!-- ======================================================= -->
                <section id="addLogPage" class="page-content">
                    <h1 class="mb-4 fw-bold">Manual Collection Log</h1>

                    <div class="custom-card p-4 bg-white mx-auto" style="max-width: 600px;">
                        <p class="text-muted mb-4">Use this form for collections not automatically logged by the system (e.g., manual pickups, error overrides).</p>

                        <form id="addCollectionLogForm">
                            <div class="mb-3">
                                <label for="logBinSelect" class="form-label fw-semibold">Select Bin Location <span class="text-danger">*</span></label>
                                <select class="form-select" id="logBinSelect" required>
                                    <option selected disabled value="">Choose a Bin ID or Location...</option>
                                    <option value="B101">B101 - Main St. & 1st Ave (Overflow)</option>
                                    <option value="B104">B104 - Library Back Door (Sensor Error)</option>
                                    <option value="B000">Other / Manual Location</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="logDateTime" class="form-label fw-semibold">Date & Time of Collection <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="logDateTime" required>
                            </div>

                            <div class="mb-3">
                                <label for="weightCollected" class="form-label fw-semibold">Weight Collected (kg) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" min="0" class="form-control" id="weightCollected" placeholder="e.g., 8.5" required>
                                <small class="text-muted">Enter the weight recorded by the truck's scale or estimated weight.</small>
                            </div>

                            <div class="mb-4">
                                <label for="logNotes" class="form-label fw-semibold">Notes / Remarks</label>
                                <textarea class="form-control" id="logNotes" rows="3" placeholder="Any special circumstances? (e.g., bin was jammed, access blocked, contamination noted)"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i> Log Collection Activity
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

            </div> <!-- End Container Fluid -->
        </main>
    </div> <!-- End App Container -->


    <!-- Custom Alert Modal Structure (replaces alert()) -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-card">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-green" id="customAlertModalLabel"><i class="bi bi-truck-flatbed me-2"></i> BinTrack Message</h5>
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

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
   <script src="../assets/js/collector.js"></script>
</body>
</html>