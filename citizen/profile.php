<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Dashboard</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>

    <?php
include '../includes/citizen/navbar.php';
?>


    <div class="container-xl py-4">
        <!-- ======================================================= -->
        <!-- 7. Profile Settings Page (New) -->
        <!-- ======================================================= -->
        <section id="profilePage" class="page-content">
            <h1 class="mb-4 text-dark-green fw-bold">Profile Settings & Account</h1>

            <div class="custom-card p-4 mx-auto" style="max-width: 800px;">
                <h5 class="fw-bold mb-3">Account Information</h5>
                <form>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" value="Jane Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" value="jane.doe@example.com" disabled>
                        </div>
                        <div class="col-md-12">
                            <label for="address" class="form-label">Primary Address</label>
                            <input type="text" class="form-control" id="address" value="456 Pine Ave, Apartment 4B">
                            <small class="text-muted">Used for neighborhood-specific bin tracking.</small>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 mt-4">Notification Preferences</h5>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">Receive email updates on my reports</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="binAlerts">
                        <label class="form-check-label" for="binAlerts">Alert me if a bin near my address is nearly full (via app notification)</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="mockLogout()">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout of Account
                        </button>
                    </div>
                </form>
            </div>
        </section>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
