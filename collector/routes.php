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
                <!-- 2. Assigned Routes Page -->
                <!-- ======================================================= -->
                <section id="routesPage" class="page-content">
                    <h1 class="mb-4 fw-bold">Assigned Collection Routes</h1>

                    <div class="custom-card p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Your Active Routes</h5>
                            <button class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light-green">
                                    <tr>
                                        <th>Route ID / Name</th>
                                        <th>Total Bins</th>
                                        <th>Completed</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr onclick="switchPage('routeDetails', 'R002')">
                                        <td class="fw-bold">R002 - Downtown Loop</td>
                                        <td>40</td>
                                        <td>24 / 40</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar progress-bar-green" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-warning text-dark">In Progress</span></td>
                                        <td><button class="btn btn-sm btn-primary">View Route</button></td>
                                    </tr>
                                    <tr onclick="switchPage('routeDetails', 'R005')">
                                        <td class="fw-bold">R005 - Residential West</td>
                                        <td>45</td>
                                        <td>0 / 45</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar progress-bar-green bg-secondary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-danger">Not Started</span></td>
                                        <td><button class="btn btn-sm btn-outline-primary">Start Route</button></td>
                                    </tr>
                                    <tr onclick="switchPage('routeDetails', 'R001')">
                                        <td class="fw-bold">R001 - Industrial Park</td>
                                        <td>28</td>
                                        <td>28 / 28</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar progress-bar-green" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td><button class="btn btn-sm btn-outline-success" disabled>Completed</button></td>
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