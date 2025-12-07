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
                <!-- 6. Alerts & Notifications Page -->
                <!-- ======================================================= -->
                <section id="alertsPage" class="page-content">
                    <h1 class="mb-4 fw-bold">Urgent Alerts & Notifications</h1>

                    <div class="custom-card p-4 bg-white">
                        <p class="text-muted mb-4">Prioritize and respond to critical bin status alerts.</p>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light-green">
                                    <tr>
                                        <th>Priority</th>
                                        <th>Alert Type</th>
                                        <th>Bin ID / Location</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-danger">
                                        <td class="fw-bold text-danger">High</td>
                                        <td>Overflow Critical</td>
                                        <td>B101 - Main St. & 1st Ave</td>
                                        <td>14:05 PM</td>
                                        <td><span class="badge bg-danger">Pending Response</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger me-2" onclick="acknowledgeAlert('B101')">Acknowledge</button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="switchPage('routeDetails', 'R002')">View Bin</button>
                                        </td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td class="fw-bold text-warning">Medium</td>
                                        <td>Fill Level 95%</td>
                                        <td>B145 - Riverfront Park</td>
                                        <td>13:10 PM</td>
                                        <td><span class="badge bg-warning text-dark">Awaiting Pickup</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="resolveAlert('B145')">Resolve</button>
                                        </td>
                                    </tr>
                                    <tr class="table-secondary">
                                        <td class="fw-bold text-muted">Low</td>
                                        <td>Sensor Offline</td>
                                        <td>B104 - Library Back Door</td>
                                        <td>10:00 AM</td>
                                        <td><span class="badge bg-secondary">Issue Reported</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="reportIssue('B104')">Update Note</button>
                                        </td>
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