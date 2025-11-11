// Admin Management JavaScript Functions

function showAddAdminForm() {
    document.getElementById('add-admin-form').style.display = 'block';
}

function hideAddAdminForm() {
    document.getElementById('add-admin-form').style.display = 'none';
    document.getElementById('adminForm').reset();
}

function addAdmin(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('adminForm'));
    formData.append('action', 'add_admin');
    
    fetch('../../api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Admin added successfully!');
            document.getElementById('add-admin-form').style.display = 'none';
            document.getElementById('adminForm').reset();
            loadContent('manage_admins'); // Refresh the list
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add admin');
    });
}

function editAdmin(adminId) {
    // Fetch admin data and populate edit form
    fetch(`../../admin/api/admin_actions.php?action=get_admin&admin_id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const admin = data.admin;
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
                alert('Failed to load admin data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading admin data');
        });
}

function updateAdmin(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editAdminForm'));
    formData.append('action', 'update_admin');
    
    // Validate password fields if they are filled
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password && password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    fetch('../../admin/api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Admin updated successfully!');
            
            // Hide the modal
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
            if (editModal) {
                editModal.hide();
            }
            
            // Refresh the admin list
            if (typeof loadContent === 'function') {
                loadContent('manage_admins');
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (data.message || data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update admin');
    });
}

function deleteAdmin(adminId) {
    if (confirm('Are you sure you want to delete this admin?')) {
        const formData = new FormData();
        formData.append('action', 'delete_admin');
        formData.append('admin_id', adminId);
        
        fetch('../../api/admin_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Admin deleted successfully!');
                loadContent('manage_admins');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting admin');
            console.error('Error:', error);
        });
    }
}

// Initialize form handlers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin management JavaScript loaded');
});
