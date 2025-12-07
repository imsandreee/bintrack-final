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
                <!-- 7. Profile Settings Page -->
                <!-- ======================================================= -->
                <section id="profilePage" class="page-content">
                    <h1 class="mb-4 fw-bold">Collector Profile</h1>

                    <div class="custom-card p-4 bg-white mx-auto" style="max-width: 600px;">
                        <h5 class="fw-bold mb-3">Personal & Route Details</h5>
                        <form>
                            <div class="mb-3">
                                <label for="collectorName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="collectorName" value="John Doe" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="collectorId" class="form-label">Collector ID</label>
                                <input type="text" class="form-control" id="collectorId" value="#C901" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="assignedRoutes" class="form-label">Assigned Routes</label>
                                <input type="text" class="form-control" id="assignedRoutes" value="R002, R005" disabled>
                            </div>
                            <h5 class="fw-bold mb-3 mt-4">Contact & Security</h5>
                             <div class="mb-3">
                                <label for="contactNumber" class="form-label">Emergency Contact Number</label>
                                <input type="tel" class="form-control" id="contactNumber" value="(555) 500-1001">
                            </div>
                            <div class="mb-4">
                                <label for="passwordChange" class="form-label">Password</label>
                                <button type="button" class="btn btn-outline-secondary w-100">Change Password</button>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
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