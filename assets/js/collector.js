/**
 * BinTrack Collector Interface Core JavaScript
 * Contains utility functions for alerts, navigation, and API calls.
 */

// --- Global Utility Functions ---

/**
 * Shows the Bootstrap modal with a custom message.
 * This function is used globally by all dashboard and route actions.
 */
function displayAlert(title, body, type = 'primary') {
    const modalTitle = document.getElementById('customAlertModalLabel');
    const modalBody = document.getElementById('alertModalBody');
    const modalElement = document.getElementById('customAlertModal');
    
    if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.error(`Alert Modal Setup Error. Message: ${body}`);
        alert(`${title}: ${body}`);
        return;
    }

    modalTitle.innerHTML = `<i class="bi bi-truck-flatbed me-2"></i> BinTrack Message`;
    modalBody.textContent = body;
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

/**
 * 🚀 CORE NAVIGATION FUNCTION
 * Handles application navigation by showing/hiding main content sections 
 * or triggering a redirect for dedicated pages like 'routes'.
 * @param {string} pageId - The ID of the page section or file (e.g., 'dashboard', 'routes', 'alerts').
 * @param {string} [routeId] - Optional ID to pass (e.g., the route ID for the 'routes' page).
 */
function switchPage(pageId, routeId = null) {
    // 1. Hide all page content sections (assuming sections have class 'page-content')
    document.querySelectorAll('.page-content').forEach(section => {
        section.classList.remove('active-page');
    });

    // 2. Handle dedicated page redirects (like the routes page)
    if (pageId === 'routes' && routeId) {
        // Redirect to the routes dedicated page with the ID as a query param
        window.location.href = 'routes.php?route_id=' + routeId;
        return;
    }
    
    // 3. Handle dashboard section switching (like the alerts or logs tab)
    const targetPage = document.getElementById(pageId + 'Page');
    if (targetPage) {
        targetPage.classList.add('active-page');
        
        if (pageId === 'logs' && routeId === 'add') {
             // Scroll to the manual log form if requested
            const logForm = document.getElementById('addCollectionLogForm');
            if(logForm) logForm.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        displayAlert('Error', `Page section '${pageId}' not found.`, 'danger');
    }
}


// --- API Interaction Functions (Used by Dashboard Elements) ---

/**
 * Logs a collection event for a specific bin via AJAX.
 * @param {string} bin_id - The unique ID of the bin collected.
 */
function logCollection(bin_id) {
    displayAlert('Confirmation', 'Logging collection...', 'primary'); 
    
    fetch("ajax/log_collection.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ bin_id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            displayAlert("Success", "Bin collection logged successfully! Reloading...", "success");
            // Reload the page to refresh bin counts and map markers
            setTimeout(() => location.reload(), 1500); 
        } else {
            displayAlert("Error", "Collection Log Error: " + data.message, "danger");
        }
    })
    .catch(err => displayAlert("Network Error", "Could not reach the logging endpoint. " + err, "danger"));
}

/**
 * Reports a simple issue for a bin via AJAX.
 * @param {string} bin_id - The unique ID of the bin with the issue.
 */
function reportIssue(bin_id) {
    displayAlert('Confirmation', 'Reporting issue...', 'warning');

    fetch("ajax/report_issue.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        // Placeholder data; replace with a form input
        body: JSON.stringify({ bin_id, issue_type: "Collector Reported", description: "Standard issue reported by collector." })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            displayAlert("Success", "Issue reported successfully! Maintenance notified.", "success");
        } else {
            displayAlert("Error", "Issue Report Error: " + data.message, "danger");
        }
    })
    .catch(err => displayAlert("Network Error", "Could not reach the reporting endpoint. " + err, "danger"));
}


// --- Manual Collection Log Form Handler ---

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addCollectionLogForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop default form submission

            const formData = new FormData(this);
            formData.append('action', 'manual_log'); 

            const binId = formData.get('bin_id');
            const remarks = formData.get('remarks');

            // Basic Validation
            if (binId === 'MANUAL' && (!remarks || remarks.length < 10)) {
                displayAlert('Validation Error', 'When selecting "MANUAL" bin, please provide a detailed location/remark (at least 10 characters).', 'danger');
                return;
            }

            // Post data to the server-side action script
            fetch('collector_actions.php', {
                method: 'POST',
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAlert('Success', data.message, 'success');
                    this.reset();
                } else {
                    displayAlert('Error', data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                displayAlert('Server Error', 'Could not connect to the server or process the request. Check network.', 'danger');
            });
        });
    }
});