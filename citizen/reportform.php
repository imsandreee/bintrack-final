
<?php
session_start();

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'citizen') {
    header("Location: ../auth/index.html");
    exit;
}

$user = $_SESSION['user'];
?>

<?php
include '../auth/config.php';

$selected_bin_code = $_GET['bin_code'] ?? '';
$selected_bin_id = $_GET['bin_id'] ?? '';

// Fetch all bins for the dropdown
$all_bins = supabase_fetch("bins", "?select=bin_code,location_name,id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Report Form</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/citizen.css">
</head>
<body>

<?php include '../includes/citizen/navbar.php'; ?>

<div class="container-xl py-4"> 
    <section id="reportFormPage" class="page-content">
        <h1 class="mb-4 text-dark-green fw-bold">Submit a Citizen Report</h1>

        <div class="custom-card p-4 mx-auto" style="max-width: 700px;">
            <p class="text-muted mb-4">Help us keep your city clean by reporting issues quickly and accurately.</p>

            <form id="citizenReportForm">
                <!-- Issue Category -->
                <div class="mb-3">
                    <label for="issueCategory" class="form-label fw-semibold">Issue Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="issueCategory" name="issue_type" required>
                        <option selected disabled value="">Choose the type of problem...</option>
                        <option value="overflow">Bin Overflowing</option>
                        <option value="smell">Strong Odor / Smell</option>
                        <option value="damage">Physical Damage / Vandalism</option>
                        <option value="missed">Missed Collection</option>
                        <option value="other">Other Issue (Specify in Description)</option>
                    </select>
                </div>

                <!-- Select Bin -->
                <div class="mb-3">
                    <label for="selectBin" class="form-label fw-semibold">Select Affected Bin <span class="text-danger">*</span></label>
                    <select class="form-select" id="selectBin" name="bin_id" required>
                        <option disabled value="">Select a Bin ID or location...</option>
                        <?php
                        if (is_array($all_bins)) {
                            foreach ($all_bins as $b) {
                                $selected = ($b['id'] === $selected_bin_id) ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($b['id']).'" '.$selected.'>'.htmlspecialchars($b['bin_code']).' - '.htmlspecialchars($b['location_name']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Detailed Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the issue..." required></textarea>
                </div>

                <!-- Photo Evidence -->
                <div class="mb-4">
                    <label for="photoEvidence" class="form-label fw-semibold">Optional: Upload Photo Evidence</label>
                    <input class="form-control" type="file" id="photoEvidence" name="photoEvidence" accept="image/*">
                    <small class="text-muted">Max file size: 5MB</small>
                </div>

                <!-- Status message -->
                <div id="statusMessage" class="mb-3"></div>

                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send-fill me-2"></i> Submit Report
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('citizenReportForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const statusMessage = document.getElementById('statusMessage');
    statusMessage.innerHTML = '';
    
    const formData = new FormData(this);

    // Optional: handle photo upload
    // Currently just sending text fields. Photo upload can be handled via Supabase storage separately

    try {
        const response = await fetch('submit_report.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if(result.status === 'success') {
            statusMessage.innerHTML = `<div class="alert alert-success">Report submitted successfully!</div>`;
            this.reset(); // Clear form
        } else {
            statusMessage.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
        }
    } catch (err) {
        statusMessage.innerHTML = `<div class="alert alert-danger">Error submitting report: ${err.message}</div>`;
    }
});
</script>

</body>
</html>
