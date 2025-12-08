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
