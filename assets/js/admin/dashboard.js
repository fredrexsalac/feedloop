
// Dashboard JavaScript Functions

// Mobile menu functions for regular admin dashboard
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Prevent body scroll when menu is open
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// FeedLoop v2.0: Navigation State Management
function saveNavigationState(page, category = null) {
    const navigationState = {
        page: page,
        category: category,
        timestamp: Date.now()
    };
    sessionStorage.setItem('feedloop_navigation_state', JSON.stringify(navigationState));
}

function loadNavigationState() {
    try {
        const saved = sessionStorage.getItem('feedloop_navigation_state');
        if (saved) {
            const state = JSON.parse(saved);
            // Only restore if less than 1 hour old
            if (Date.now() - state.timestamp < 3600000) {
                return state;
            }
        }
    } catch (error) {
        console.warn('Error loading navigation state:', error);
    }
    return null;
}

// Function to load dashboard home content
function loadDashboard() {
    try {
        // Reset active state - add null checks
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarLinks.forEach(a => {
            if (a && a.classList) {
                a.classList.remove('active');
            }
        });
        
        // Find and activate the dashboard link
        const dashboardLink = document.querySelector('a[onclick="loadDashboard()"]');
        if (dashboardLink && dashboardLink.classList) {
            dashboardLink.classList.add('active');
        }
        
        // Save navigation state
        saveNavigationState('dashboard');
    } catch (error) {
        console.warn('Error in loadDashboard:', error);
    }
    
    // Close mobile menu
    closeSidebar();
    
    // Reload the page to show dashboard content
    location.reload();
}

// Function to load dynamic content
function loadContent(page, params = '') {
    try {
        // Reset active state - add null checks
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarLinks.forEach(a => {
            if (a && a.classList) {
                a.classList.remove('active');
            }
        });
        
        // Add active class to clicked element
        if (event && event.target && event.target.classList) {
            event.target.classList.add('active');
        }
        
        // Save navigation state
        saveNavigationState(page);
    } catch (error) {
        console.warn('Error in loadContent:', error);
    }
    
    // Close mobile menu
    closeSidebar();
    
    // Load content via AJAX
    const url = 'admin_load_content.php?page=' + page + params;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            const contentDiv = document.getElementById('dynamic-content');
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            const contentDiv = document.getElementById('dynamic-content');
            contentDiv.innerHTML = '<div class="alert alert-danger">Error loading content. Please try again.</div>';
            console.error('Error:', error);
        });
}

// Mobile menu functions for super admin dashboard
function toggleSuperAdminSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
}

function closeSuperAdminSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
}

// Function to load dashboard home content (for super admin)
function loadSuperAdminDashboard() {
    try {
        // Reset active state - add null checks
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarLinks.forEach(a => {
            if (a && a.classList) {
                a.classList.remove('active');
            }
        });
        
        // Find and activate the dashboard link
        const dashboardLink = document.querySelector('a[onclick="loadSuperAdminDashboard()"]');
        if (dashboardLink && dashboardLink.classList) {
            dashboardLink.classList.add('active');
        }
        
        // Save navigation state
        saveNavigationState('dashboard');
    } catch (error) {
        console.warn('Error in loadSuperAdminDashboard:', error);
    }
    
    // Close mobile menu
    closeSuperAdminSidebar();
    
    // Reload the page to show dashboard content
    location.reload();
}

// Function to load dynamic content (for super admin)
function loadSuperAdminContent(page) {
    try {
        // Reset active state - add null checks
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarLinks.forEach(a => {
            if (a && a.classList) {
                a.classList.remove('active');
            }
        });
        
        // Add active class to clicked element
        if (event && event.target && event.target.classList) {
            event.target.classList.add('active');
        }
        
        // Save navigation state
        saveNavigationState(page);
    } catch (error) {
        console.warn('Error in loadSuperAdminContent:', error);
    }
    
    // Close mobile menu
    closeSuperAdminSidebar();
    
    // Load content via AJAX
    fetch('../load_content.php?page=' + page)
        .then(response => response.text())
        .then(html => {
            const contentDiv = document.getElementById('dynamic-content');
            contentDiv.innerHTML = html;
            
            // Execute any scripts in the loaded content
            const scripts = contentDiv.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                document.head.appendChild(newScript);
                // Remove the script after execution to avoid duplicates
                setTimeout(() => {
                    if (newScript.parentNode) {
                        newScript.parentNode.removeChild(newScript);
                    }
                }, 100);
            });
        })
        .catch(error => {
            const contentDiv = document.getElementById('dynamic-content');
            contentDiv.innerHTML = '<div class="alert alert-danger">Error loading content. Please try again.</div>';
            console.error('Error:', error);
        });
}

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
    fetch('../api/user_actions.php?action=get_user_details&user_id=' + userId)
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
    document.getElementById('suspendUserName').innerHTML = 'User: <strong>' + userName + '</strong><br>This action will prevent the user from accessing the system.';
    
    // Show suspend confirmation modal
    const suspendModal = document.getElementById('suspendConfirmModal');
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(suspendModal);
        bsModal.show();
    } else {
        suspendModal.style.display = 'block';
        suspendModal.classList.add('show');
        document.body.classList.add('modal-open');
    }
    
    // Set up confirm button click
    document.getElementById('confirmSuspendBtn').onclick = function() {
        suspendUser(userId, userName);
    };
}

function suspendUser(userId, userName) {
    // Close confirmation modal first
    const suspendModal = document.getElementById('suspendConfirmModal');
    if (typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(suspendModal);
        if (modal) {
            modal.hide();
        }
    } else {
        suspendModal.style.display = 'none';
    }
    
    // Show loading toast
    showToast('Suspending user...', 'info');
    
    // Make API call to suspend user
    fetch('../../admin/api/user_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'suspend_user',
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('User suspended successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error suspending user: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        showToast('Network error occurred', 'error');
    });
}

function unsuspendUser(userId, userName) {
    if (confirm('Are you sure you want to unsuspend ' + userName + '? This will restore their access to the system.')) {
        fetch('../api/user_actions.php', {
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
                        <span class="status-badge status-active">Active</span>
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard JavaScript loaded');
});

// Global admin management functions
window.editAdmin = function(adminId) {
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
};

window.deleteAdmin = function(adminId) {
    // First get admin details to show in confirmation
    fetch(`../../admin/api/admin_actions.php?action=get_admin&admin_id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const admin = data.admin;
                
                // Show confirmation dialog
                if (confirm(`Are you sure you want to delete the admin account for "${admin.full_name}"?\n\nThis action cannot be undone and will permanently remove:\n- Admin account access\n- All associated data\n- Login credentials\n\nClick OK to confirm deletion.`)) {
                    
                    // Proceed with deletion
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
                    });
                }
            } else {
                showAlert('danger', 'Failed to load admin data for deletion');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while loading admin data');
        });
};

window.showAlert = function(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of content
    const content = document.querySelector('#dynamic-content');
    if (content) {
        content.insertBefore(alertDiv, content.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
};
