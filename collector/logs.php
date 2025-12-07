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
                <!-- 4. Collection Logs Page -->
                <!-- ======================================================= -->
                <section id="logsPage" class="page-content">
                    <h1 class="mb-4 fw-bold">Collection Logs (Last 7 Days)</h1>

                    <div class="custom-card p-4 bg-white">
                         <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-primary" onclick="switchPage('logs', 'add')">
                                <i class="bi bi-plus-circle-fill me-2"></i> Add New Collection Log
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light-green">
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Bin ID / Location</th>
                                        <th>Date & Time</th>
                                        <th>Weight Collected</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-primary">L2024-03</td>
                                        <td>B102 - City Hall Parking</td>
                                        <td>2024-10-28 14:30</td>
                                        <td>5.2 kg</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>Standard pickup, low contamination.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-primary">L2024-02</td>
                                        <td>B101 - Main St. & 1st Ave</td>
                                        <td>2024-10-27 09:15</td>
                                        <td>9.1 kg</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>Overflow response, heavy load.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-primary">L2024-01</td>
                                        <td>B045 - Pier 3</td>
                                        <td>2024-10-27 08:45</td>
                                        <td>7.8 kg</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>Routine collection.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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