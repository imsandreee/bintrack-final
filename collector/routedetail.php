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
                <!-- 3. Route Details Page -->
                <!-- ======================================================= -->
                <section id="routeDetailsPage" class="page-content">
                    <h1 class="mb-4 fw-bold">Route Details: <span id="routeIdDisplay">R002 - Downtown Loop</span></h1>

                    <div class="row g-4 mb-4">
                        <div class="col-lg-12">
                            <div class="custom-card p-4 bg-white">
                                <h5 class="fw-bold mb-3">Bin Locations Overview</h5>
                                <div class="ratio ratio-16x9 bg-light-green border rounded-3 p-3 text-center d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0">
                                        [Map Placeholder: Route R002]<br>
                                        Interactive map showing all 40 bins, highlighting completed and alert bins.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="custom-card p-4 bg-white">
                        <h5 class="fw-bold mb-3">Bins in Route (Remaining: 16)</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="bg-light-green">
                                    <tr>
                                        <th>Bin ID</th>
                                        <th>Location</th>
                                        <th>Fill Level</th>
                                        <th>Weight</th>
                                        <th>Alerts</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="routeBinList">
                                    <!-- Bin 1 (High Priority Overflow) -->
                                    <tr data-bin-id="B101">
                                        <td class="fw-bold">B101</td>
                                        <td>Main St. & 1st Ave</td>
                                        <td>
                                            <div class="progress" style="height: 8px;"><div class="progress-bar bg-danger" style="width: 100%;"></div></div>
                                            <small class="text-danger fw-semibold">100% Full</small>
                                        </td>
                                        <td>9.8 kg</td>
                                        <td><span class="badge bg-danger"><i class="bi bi-lightning-fill"></i> Overflow</span></td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" onclick="logCollection('B101', '9.8')">Collected</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="reportIssue('B101')">Report</button>
                                        </td>
                                    </tr>
                                    <!-- Bin 2 (Completed) -->
                                    <tr data-bin-id="B102" class="table-success bg-opacity-25">
                                        <td class="fw-bold">B102</td>
                                        <td>City Hall Parking</td>
                                        <td><small>N/A</small></td>
                                        <td><small>0.2 kg</small></td>
                                        <td>-</td>
                                        <td><span class="badge bg-success">Collected</span></td>
                                        <td><button class="btn btn-sm btn-outline-success" disabled>Done</button></td>
                                    </tr>
                                    <!-- Bin 3 (Nearly Full) -->
                                    <tr data-bin-id="B103">
                                        <td class="fw-bold">B103</td>
                                        <td>Oak St. Park Gate</td>
                                        <td>
                                            <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: 76%;"></div></div>
                                            <small class="text-warning fw-semibold">76% Full</small>
                                        </td>
                                        <td>7.5 kg</td>
                                        <td>-</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" onclick="logCollection('B103', '7.5')">Collected</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="reportIssue('B103')">Report</button>
                                        </td>
                                    </tr>
                                    <!-- Bin 4 (Error) -->
                                    <tr data-bin-id="B104">
                                        <td class="fw-bold">B104</td>
                                        <td>Library Back Door</td>
                                        <td><small>N/A</small></td>
                                        <td><small>N/A</small></td>
                                        <td><span class="badge bg-secondary"><i class="bi bi-x-octagon-fill"></i> Sensor Error</span></td>
                                        <td><span class="badge bg-danger">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" onclick="logCollection('B104', 'Manual')">Collected</button>
                                            <button class="btn btn-sm btn-danger" onclick="reportIssue('B104')">Report Error</button>
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