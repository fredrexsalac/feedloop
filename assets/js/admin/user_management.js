// User Management JavaScript Functions

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for view buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-btn')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.view-btn');
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const userRole = button.getAttribute('data-user-role');
            
            viewUser(userId, userName, userRole);
            return false;
        }
        
        if (e.target.closest('.suspend-btn')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.suspend-btn');
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            console.log('Suspend button clicked:', { userId, userName });
            
            if (!userId || !userName || userName === 'N/A') {
                alert('Error: Unable to get user information for suspension');
                return false;
            }
            
            showSuspendModal(userId, userName);
            return false;
        }
        
        if (e.target.closest('.unsuspend-btn')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.unsuspend-btn');
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            unsuspendUser(userId, userName);
            return false;
        }
        
        // Close modal handlers
        if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.closest('[data-bs-dismiss="modal"]')) {
            closeModal();
        }
        if (e.target.matches('.modal-backdrop')) {
            closeModal();
        }
    });
});

// Global functions for user management
function viewUser(userId, userName, userRole) {
    // Get modal element
    const modalElement = document.getElementById('userDetailsModal');
    if (!modalElement) {
        return false;
    }
    
    // Show loading state
    const contentElement = document.getElementById('userDetailsContent');
    if (contentElement) {
        contentElement.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading user details...</div>';
    }
    
    // Show modal using Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
    } else {
        // Fallback manual display
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        modalElement.setAttribute('aria-hidden', 'false');
        
        // Add backdrop
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
        
        document.body.classList.add('modal-open');
    }
    
    // Fetch user details
    fetch('../../admin/api/user_actions.php?action=get_user_details&user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUserDetails(data.user);
            } else {
                if (contentElement) {
                    contentElement.innerHTML = 
                        '<div class="alert alert-danger">Error: ' + (data.message || 'User not found') + '</div>';
                }
            }
        })
        .catch(error => {
            if (contentElement) {
                contentElement.innerHTML = 
                    '<div class="alert alert-danger">Network error. Please check your connection.</div>';
            }
        });
    
    return false;
}

function showSuspendModal(userId, userName) {
    // Set user name in modal
    const suspendUserNameElement = document.getElementById('suspendUserName');
    if (suspendUserNameElement) {
        suspendUserNameElement.innerHTML = 'User: <strong>' + userName + '</strong><br>This action will prevent the user from accessing the system.';
    }
    
    // Show suspend confirmation modal
    const suspendModal = document.getElementById('suspendConfirmModal');
    if (!suspendModal) {
        alert('Suspend confirmation modal not found. User: ' + userName + ' (ID: ' + userId + ')');
        return;
    }
    
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(suspendModal);
        bsModal.show();
    } else {
        suspendModal.style.display = 'block';
        suspendModal.classList.add('show');
        document.body.classList.add('modal-open');
    }
    
    // Set up confirm button click
    const confirmBtn = document.getElementById('confirmSuspendBtn');
    if (confirmBtn) {
        confirmBtn.onclick = function() {
            suspendUser(userId, userName);
        };
    }
}

function suspendUser(userId, userName) {
    // Close confirmation modal first
    const suspendModal = document.getElementById('suspendConfirmModal');
    if (typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getInstance(suspendModal);
        if (bsModal) bsModal.hide();
    } else {
        suspendModal.style.display = 'none';
        suspendModal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmSuspendBtn');
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suspending...';
    confirmBtn.disabled = true;
    
    // Use relative path for API call
    const apiUrl = '../../admin/api/user_actions.php';
    console.log('Making suspend API call to:', apiUrl);
    console.log('Request data:', { action: 'suspend_user', user_id: userId });
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'suspend_user',
            user_id: userId
        })
    })
    .then(response => {
        console.log('API Response status:', response.status);
        console.log('API Response headers:', response.headers);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('API Error Response:', text);
                throw new Error('HTTP ' + response.status + ': ' + text);
            });
        }
        return response.text().then(text => {
            console.log('Raw API response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Parsed API data:', data);
        if (data.success) {
            showToast('User suspended successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error suspending user: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Full suspend error:', error);
        showToast('API Error: ' + (error.message || 'Unknown error'), 'error');
    });
}

function unsuspendUser(userId, userName) {
    if (confirm('Are you sure you want to unsuspend ' + userName + '? This will restore their access to the system.')) {
        fetch('../../admin/api/user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unsuspend_user',
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('User unsuspended successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error unsuspending user: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Network error: ' + error.message, 'error');
        });
    }
}

function displayUserDetails(user) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="user-detail-card">
                    <h6 class="detail-header">Basic Information</h6>
                    <div class="detail-item">
                        <strong>Full Name:</strong> ${user.full_name || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Username:</strong> ${user.username || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong> ${user.email || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Role:</strong> 
                        <span class="role-badge ${user.role === 'admin' ? 'role-admin' : 'role-student'}">${user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <strong>Status:</strong> 
                        <span class="status-badge ${getStatusClass(user.status || 'active')}">${getStatusText(user.status || 'active')}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="user-detail-card">
                    <h6 class="detail-header">${user.role === 'student' ? 'Academic Information' : 'Administrative Information'}</h6>
                    ${user.role === 'student' ? `
                        <div class="detail-item">
                            <strong>Course:</strong> ${user.course || 'N/A'}
                        </div>
                        <div class="detail-item">
                            <strong>Year Level:</strong> ${user.year_level || 'N/A'}
                        </div>
                        <div class="detail-item">
                            <strong>Section:</strong> ${user.section || 'N/A'}
                        </div>
                        <div class="detail-item">
                            <strong>Student Number:</strong> ${user.student_number || 'N/A'}
                        </div>
                    ` : `
                        <div class="detail-item">
                            <strong>Position:</strong> ${user.position || 'N/A'}
                        </div>
                    `}
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="user-detail-card">
                    <h6 class="detail-header">Account Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong>Account Created:</strong> ${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const contentElement = document.getElementById('userDetailsContent');
    if (contentElement) {
        contentElement.innerHTML = content;
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'active': return 'status-active';
        case 'inactive': return 'status-inactive';
        case 'suspended': return 'status-suspended';
        default: return 'status-active';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Active';
        case 'inactive': return 'Inactive';
        case 'suspended': return 'Suspended';
        default: return 'Active';
    }
}

function closeModal() {
    const modalElement = document.getElementById('userDetailsModal');
    const backdrop = document.querySelector('.modal-backdrop');
    
    if (modalElement) {
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        modalElement.setAttribute('aria-hidden', 'true');
    }
    
    if (backdrop) {
        backdrop.remove();
    }
    
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}
