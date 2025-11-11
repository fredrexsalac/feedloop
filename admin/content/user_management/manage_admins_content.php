<?php
// Get all admins
$admins = [];
$debug_info = [];
try {
    // First, let's check all users with admin role
    $stmt = $pdo->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_users = $stmt->fetchAll();
    $debug_info['admin_users_count'] = count($admin_users);
    $debug_info['admin_users'] = $admin_users;
    
    // Now get admins with proper join
    $stmt = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name, a.position, 
    u.username, u.email, u.created_at 
    FROM admins a 
    JOIN users u ON a.user_id = u.user_id 
    WHERE u.role = 'admin'
    ORDER BY a.position DESC, a.full_name ASC");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    $debug_info['admins_count'] = count($admins);
    
    // Check for orphaned admin users (users with admin role but no entry in admins table)
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.email, u.created_at 
    FROM users u 
    LEFT JOIN admins a ON u.user_id = a.user_id 
    WHERE u.role = 'admin' AND a.user_id IS NULL");
    $stmt->execute();
    $orphaned_admins = $stmt->fetchAll();
    $debug_info['orphaned_admins'] = $orphaned_admins;
    
    // Auto-fix orphaned admin users
    if (!empty($orphaned_admins)) {
        foreach ($orphaned_admins as $orphaned) {
            try {
                // Create admin entry with default values
                $stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
                $full_name = $orphaned['username']; // Use username as fallback for full_name
                $position = 'Admin'; // Default position
                $stmt->execute([$orphaned['user_id'], $full_name, $position]);
                $debug_info['fixed_orphans'][] = $orphaned['username'];
            } catch (Exception $e) {
                $debug_info['fix_errors'][] = "Failed to fix " . $orphaned['username'] . ": " . $e->getMessage();
            }
        }
        
        // Re-fetch admins after fixing orphaned entries
        $stmt = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name, a.position, 
        u.username, u.email, u.created_at 
        FROM admins a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE u.role = 'admin'
        ORDER BY a.position DESC, a.full_name ASC");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        $debug_info['admins_count_after_fix'] = count($admins);
    }
} catch (Exception $e) {
    $error = "Error fetching admins: " . $e->getMessage();
    $debug_info['error'] = $error;
}

// Show debug info if there are issues
if (!empty($debug_info['orphaned_admins']) || $debug_info['admin_users_count'] != $debug_info['admins_count']) {
    echo "<!-- DEBUG INFO: ";
    echo "Admin users in users table: " . $debug_info['admin_users_count'] . ", ";
    echo "Admins in admins table: " . ($debug_info['admins_count'] ?? 0) . ", ";
    echo "Orphaned admins: " . count($debug_info['orphaned_admins'] ?? []);
    echo " -->";
}
?>

<div class="dashboard-header">
    <h1>Manage Admins</h1>
    <p>Add, edit, and manage administrator accounts</p>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Administrator List</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-2"></i>Add New Admin
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Position</th>
                    <th>Username</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="alert alert-info">
                                <h5>No administrators found</h5>
                                <p>There are currently no administrators in the system. Use the "Add New Admin" button above to create the first admin.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php if (!empty($debug_info['fixed_orphans'])): ?>
                        <tr>
                            <td colspan="5">
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Auto-fix Applied:</strong> Found and fixed orphaned admin accounts: 
                                    <?php echo implode(', ', $debug_info['fixed_orphans']); ?>. 
                                    <br><small>These accounts existed in the users table but were missing from the admins table. 
                                    All were assigned default "Admin" position - use the Edit button to change positions as needed 
                                    (e.g., to Super Admin).</small>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></small><br>
                                <small class="text-muted">@<?php echo htmlspecialchars($admin['username']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $admin['position'] === 'Super Admin' ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($admin['position']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editAdmin(<?php echo $admin['admin_id']; ?>)">Edit</button>
                                <?php if ($admin['user_id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Administrator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="adminForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" id="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" id="username" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Position *</label>
                                <select class="form-control" name="position" id="position" required>
                                    <option value="">Select Position</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" id="password" required>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="showConfirmationModal()">
                    <i class="fas fa-eye me-2"></i>Preview & Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirm Admin Registration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please review the information below before creating the admin account.
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Full Name:</strong>
                                <p id="confirm_full_name" class="text-muted"></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Username:</strong>
                                <p id="confirm_username" class="text-muted"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <p id="confirm_email" class="text-muted"></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Position:</strong>
                                <p id="confirm_position" class="text-muted"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="goBackToForm()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Edit
                </button>
                <button type="button" class="btn btn-success" onclick="submitAdminForm()">
                    <i class="fas fa-user-plus me-2"></i>Create Admin Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Administrator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAdminForm">
                    <input type="hidden" id="edit_admin_id" name="admin_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" id="edit_username" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Position *</label>
                                <select class="form-control" name="position" id="edit_position" required>
                                    <option value="">Select Position</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Leave password fields empty to keep current password
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password" id="edit_password">
                                <div class="form-text">Minimum 8 characters (optional)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" id="edit_confirm_password">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="showEditConfirmationModal()">
                    <i class="fas fa-eye me-2"></i>Preview Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Confirmation Modal -->
<div class="modal fade" id="editConfirmationModal" tabindex="-1" aria-labelledby="editConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editConfirmationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirm Admin Changes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Please review the changes below before updating the admin account.
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Full Name:</strong>
                                <p id="edit_confirm_full_name" class="text-muted"></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Username:</strong>
                                <p id="edit_confirm_username" class="text-muted"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Email:</strong>
                                <p id="edit_confirm_email" class="text-muted"></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Position:</strong>
                                <p id="edit_confirm_position" class="text-muted"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <strong>Password:</strong>
                                <p id="edit_confirm_password" class="text-muted"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="goBackToEditForm()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Edit
                </button>
                <button type="button" class="btn btn-success" onclick="submitEditAdminForm()">
                    <i class="fas fa-save me-2"></i>Update Admin Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showConfirmationModal() {
    // Validate form first
    const form = document.getElementById('adminForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Check password match
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    if (password.length < 8) {
        alert('Password must be at least 8 characters long!');
        return;
    }
    
    // Populate confirmation modal
    document.getElementById('confirm_full_name').textContent = document.getElementById('full_name').value;
    document.getElementById('confirm_username').textContent = document.getElementById('username').value;
    document.getElementById('confirm_email').textContent = document.getElementById('email').value;
    document.getElementById('confirm_position').textContent = document.getElementById('position').value;
    
    // Hide first modal and show confirmation
    const addModal = bootstrap.Modal.getInstance(document.getElementById('addAdminModal'));
    addModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    confirmModal.show();
}

function goBackToForm() {
    // Hide confirmation modal and show form modal
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
    confirmModal.hide();
    
    const addModal = new bootstrap.Modal(document.getElementById('addAdminModal'));
    addModal.show();
}

function submitAdminForm() {
    const formData = new FormData();
    formData.append('action', 'add_admin');
    formData.append('full_name', document.getElementById('full_name').value);
    formData.append('username', document.getElementById('username').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('position', document.getElementById('position').value);
    formData.append('password', document.getElementById('password').value);
    
    // Show loading state
    const submitBtn = document.querySelector('#confirmationModal .btn-success');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
    submitBtn.disabled = true;
    
    fetch('../../admin/api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal and show success message
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            confirmModal.hide();
            
            // Reset form
            document.getElementById('adminForm').reset();
            
            // Show success alert
            showAlert('success', 'Admin account created successfully!');
            
            // Reload the admin list
            loadContent('manage_admins');
        } else {
            showAlert('danger', data.message || 'Failed to create admin account');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while creating the admin account');
    })
    .finally(() => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of content
    const content = document.querySelector('#dynamic-content');
    content.insertBefore(alertDiv, content.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function editAdmin(adminId) {
    // Fetch admin data and populate edit form
    fetch(`../../admin/api/admin_actions.php?action=get_admin&admin_id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const admin = data.data; 
                document.getElementById('edit_admin_id').value = admin.admin_id;
                document.getElementById('edit_full_name').value = admin.full_name;
                document.getElementById('edit_username').value = admin.username;
                document.getElementById('edit_email').value = admin.email;
                document.getElementById('edit_position').value = admin.position;
                
                // Clear password fields
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_confirm_password').value = '';
                
                // Show edit modal
                const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                editModal.show();
            } else {
                showAlert('danger', 'Failed to load admin data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while loading admin data');
        });
}

function showEditConfirmationModal() {
    // Validate form first
    const form = document.getElementById('editAdminForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Check password match if passwords are provided
    const password = document.getElementById('edit_password').value;
    const confirmPassword = document.getElementById('edit_confirm_password').value;
    
    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }
        
        if (password.length < 8) {
            alert('Password must be at least 8 characters long!');
            return;
        }
    }
    
    // Populate confirmation modal
    document.getElementById('edit_confirm_full_name').textContent = document.getElementById('edit_full_name').value;
    document.getElementById('edit_confirm_username').textContent = document.getElementById('edit_username').value;
    document.getElementById('edit_confirm_email').textContent = document.getElementById('edit_email').value;
    document.getElementById('edit_confirm_position').textContent = document.getElementById('edit_position').value;
    document.getElementById('edit_confirm_password').textContent = password ? 'Password will be updated' : 'Password unchanged';
    
    // Hide edit modal and show confirmation
    const editModal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
    editModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('editConfirmationModal'));
    confirmModal.show();
}

function goBackToEditForm() {
    // Hide confirmation modal and show edit modal
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('editConfirmationModal'));
    confirmModal.hide();
    
    const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
    editModal.show();
}

function submitEditAdminForm() {
    const formData = new FormData();
    formData.append('action', 'edit_admin');
    formData.append('admin_id', document.getElementById('edit_admin_id').value);
    formData.append('full_name', document.getElementById('edit_full_name').value);
    formData.append('username', document.getElementById('edit_username').value);
    formData.append('email', document.getElementById('edit_email').value);
    formData.append('position', document.getElementById('edit_position').value);
    
    const password = document.getElementById('edit_password').value;
    if (password) {
        formData.append('password', password);
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#editConfirmationModal .btn-success');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    submitBtn.disabled = true;
    
    fetch('../../admin/api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal and show success message
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('editConfirmationModal'));
            confirmModal.hide();
            
            // Reset form
            document.getElementById('editAdminForm').reset();
            
            // Show success alert
            showAlert('success', 'Admin account updated successfully!');
            
            // Reload the admin list
            loadContent('manage_admins');
        } else {
            showAlert('danger', data.message || 'Failed to update admin account');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while updating the admin account');
    })
    .finally(() => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function deleteAdmin(adminId) {
    // Show confirmation dialog
    if (confirm('Are you sure you want to delete this admin account? This action cannot be undone.')) {
        // Show loading state
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        deleteBtn.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'delete_admin');
        formData.append('admin_id', adminId);
        
        fetch('../../admin/api/admin_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', 'Admin account deleted successfully!');
                
                // Reload the admin list
                loadContent('manage_admins');
            } else {
                showAlert('danger', data.message || 'Failed to delete admin account');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while deleting the admin account');
        })
        .finally(() => {
            // Reset button
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        });
    }
}
</script>
