// CUSTOM MODAL ALERT
function showAlert(msg) {
    document.getElementById("alertModalBody").innerText = msg;
    let modal = new bootstrap.Modal(document.getElementById("customAlertModal"));
    modal.show();
}

// LOG COLLECTION REQUEST
function logCollection(bin_id) {
    fetch("ajax/log_collection.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bin_id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert("Bin collection logged successfully!");
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert("Error: " + data.message);
        }
    })
    .catch(err => showAlert("Network Error: " + err));
}

// REPORT ISSUE REQUEST
function reportIssue(bin_id) {
    fetch("ajax/report_issue.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bin_id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert("Issue reported successfully!");
        } else {
            showAlert("Error: " + data.message);
        }
    });
}

// --- In ../assets/js/collector.js ---

document.getElementById('addCollectionLogForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Stop default form submission

    const formData = new FormData(this);
    
    // Add the action type required by collector_actions.php
    formData.append('action', 'manual_log'); 

    // Extract form data
    const binId = formData.get('bin_id');
    const collectedAt = formData.get('collected_at');
    const weight = formData.get('weight_collected_kg');
    const remarks = formData.get('remarks');

    // Basic Validation
    if (binId === 'MANUAL' && (!remarks || remarks.length < 10)) {
        displayAlert('Error', 'When selecting "MANUAL" bin, please provide a detailed location/remark.', 'danger');
        return;
    }

    fetch('collector_actions.php', {
        method: 'POST',
        body: formData // Sends action, bin_id, collected_at, weight_collected_kg, remarks
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAlert('Success', data.message, 'success');
            // Clear or reset form upon success
            this.reset();
            // Optionally redirect back to the logs page
            // setTimeout(() => switchPage('logs'), 1500); 
        } else {
            displayAlert('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        displayAlert('Server Error', 'Could not connect to the server. Check network.', 'danger');
    });
});

// Assuming a displayAlert function exists for showing the customAlertModal
function displayAlert(title, body, type = 'primary') {
    const modalTitle = document.getElementById('customAlertModalLabel');
    const modalBody = document.getElementById('alertModalBody');
    const modal = new bootstrap.Modal(document.getElementById('customAlertModal'));

    modalTitle.innerHTML = `<i class="bi bi-truck-flatbed me-2"></i> BinTrack Message`;
    modalBody.textContent = body;
    
    // You would use the 'type' to change the title/icon color if needed
    
    modal.show();
}