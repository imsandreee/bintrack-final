<?php
require '../auth/config.php'; // Supabase setup

// Fetch all users
try {
    $users = supabase_fetch('profiles', '?select=id,full_name,role,contact_number,address,created_at&order=created_at.desc');
} catch (Exception $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex" id="wrapper">
    <?php include '../includes/admin/sidebar.php'; ?>
    <div id="page-content-wrapper" class="w-100">
        <?php include '../includes/admin/topnavbar.php'; ?>

        <!-- Users Management -->
        <div id="users" class="page-content" style="display: block;">
            <h1 class="mb-4">Users Management</h1>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button id="addUserBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New User
                </button>
            </div>

            <div class="card p-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="usersTable">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Contact Number</th>
                                <th>Address</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTbody">
                            <?php if(!empty($users)): ?>
                                <?php foreach($users as $user): ?>
                                    <tr id="user-<?= $user['id'] ?>">
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td>
                                            <?php
                                                $role = $user['role'];
                                                $badgeClass = match($role) {
                                                    'admin' => 'bg-success',
                                                    'collector' => 'bg-primary',
                                                    'citizen' => 'bg-secondary',
                                                    default => 'bg-dark'
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($role) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($user['contact_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                                        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info me-2 edit-user-btn"
                                                data-id="<?= $user['id'] ?>"
                                                data-full_name="<?= htmlspecialchars($user['full_name']) ?>"
                                                data-role="<?= $user['role'] ?>"
                                                data-contact="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
                                                data-address="<?= htmlspecialchars($user['address'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-user-btn" data-id="<?= $user['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /Users Management -->
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add/Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="userForm">
          <input type="hidden" id="userId">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullName">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" id="role">
                <option value="admin">Admin</option>
                <option value="collector">Collector</option>
                <option value="citizen" selected>Citizen</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="contactNumber">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" id="address"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-success" id="saveUserBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const userForm = document.getElementById('userForm');
    const saveUserBtn = document.getElementById('saveUserBtn');
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));

    // Open modal for editing
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('userId').value = btn.dataset.id;
            document.getElementById('fullName').value = btn.dataset.full_name;
            document.getElementById('role').value = btn.dataset.role;
            document.getElementById('contactNumber').value = btn.dataset.contact;
            document.getElementById('address').value = btn.dataset.address;
            userModal.show();
        });
    });

    // Save changes (update profile)
    saveUserBtn.addEventListener('click', () => {
        const id = document.getElementById('userId').value;
        const fullName = document.getElementById('fullName').value;
        const role = document.getElementById('role').value;
        const contact = document.getElementById('contactNumber').value;
        const address = document.getElementById('address').value;

        if (!fullName || !role) {
            alert('Full Name and Role are required');
            return;
        }

        fetch('users/update_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, fullName, role, contact, address })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('User updated successfully!');
                location.reload();
            } else {
                alert('Update failed: ' + data.message);
            }
        });
    });

    // Delete user
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Are you sure you want to delete this user?')) return;

            const id = btn.dataset.id;
            fetch('users/delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully!');
                    document.getElementById('user-' + id).remove();
                } else {
                    alert('Delete failed: ' + data.message);
                }
            });
        });
    });
});

</script>

</body>
</html>
