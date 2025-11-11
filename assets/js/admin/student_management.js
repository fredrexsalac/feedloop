// Student Management JavaScript Functions

// Student management functions
function viewStudent(userId, studentName) {
    const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
    const content = document.getElementById('studentDetailsContent');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading student details...</div>';
    modal.show();
    
    // Fetch student details
    console.log('Fetching student details for user ID:', userId);
    fetch('api/user_actions.php?action=get_user_details&user_id=' + userId)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                displayStudentDetails(data.user);
            } else {
                content.innerHTML = '<div class="alert alert-danger">Error: ' + (data.message || 'Student not found') + '</div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading student details: ' + error.message + '</div>';
        });
}

function displayStudentDetails(student) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Full Name:</strong> ${student.full_name || 'N/A'}</p>
                        <p><strong>Username:</strong> ${student.username || 'N/A'}</p>
                        <p><strong>Email:</strong> ${student.email || 'N/A'}</p>
                        <p><strong>Student Number:</strong> ${student.student_number || 'N/A'}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Course:</strong> ${student.course || 'N/A'}</p>
                        <p><strong>Year Level:</strong> ${student.year_level || 'N/A'}</p>
                        <p><strong>Section:</strong> ${student.section || 'N/A'}</p>
                        <p><strong>Account Created:</strong> ${student.created_at ? new Date(student.created_at).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('studentDetailsContent').innerHTML = content;
}

function resetStudentPassword(userId, studentName) {
    document.getElementById('resetPasswordText').innerHTML = `Reset password for: <strong>${studentName}</strong>`;
    generatePassword(); // Generate initial password
    
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
    
    document.getElementById('confirmResetBtn').onclick = function() {
        const newPassword = document.getElementById('newPassword').value;
        
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';
        this.disabled = true;
        
        // Make API call to reset password
        fetch('api/user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reset_student_password',
                user_id: userId,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Password Reset Successful', `Password for ${studentName} has been reset to: ${newPassword}`);
                modal.hide();
            } else {
                showToast('error', 'Reset Failed', data.message || 'Failed to reset password');
            }
        })
        .catch(error => {
            showToast('error', 'Network Error', 'Failed to connect to server');
        })
        .finally(() => {
            // Reset button state
            this.innerHTML = '<i class="fas fa-key me-2"></i>Reset Password';
            this.disabled = false;
        });
    };
}

function suspendStudent(userId, studentName) {
    document.getElementById('suspendStudentText').innerHTML = `Suspend account for: <strong>${studentName}</strong>`;
    document.getElementById('suspendReason').value = '';
    document.getElementById('suspendNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('suspendStudentModal'));
    modal.show();
    
    document.getElementById('confirmSuspendBtn').onclick = function() {
        const reason = document.getElementById('suspendReason').value;
        const notes = document.getElementById('suspendNotes').value;
        
        if (!reason) {
            showToast('error', 'Validation Error', 'Please select a reason for suspension');
            return;
        }
        
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Suspending...';
        this.disabled = true;
        
        // Make API call to suspend student
        fetch('api/user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'suspend_student',
                user_id: userId,
                reason: reason,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Account Suspended', `${studentName}'s account has been suspended`);
                modal.hide();
                // Refresh the page to update the student list
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('error', 'Suspension Failed', data.message || 'Failed to suspend account');
            }
        })
        .catch(error => {
            showToast('error', 'Network Error', 'Failed to connect to server');
        })
        .finally(() => {
            // Reset button state
            this.innerHTML = '<i class="fas fa-ban me-2"></i>Suspend Account';
            this.disabled = false;
        });
    };
}

// Generate random password
function generatePassword() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('newPassword').value = password;
}

// Show toast notification
function showToast(type, title, message) {
    const toast = document.getElementById('actionToast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    
    // Set icon and colors based on type
    if (type === 'success') {
        toastIcon.className = 'fas fa-check-circle text-success me-2';
        toast.className = 'toast border-success';
    } else {
        toastIcon.className = 'fas fa-exclamation-circle text-danger me-2';
        toast.className = 'toast border-danger';
    }
    
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
}

function unsuspendStudent(userId, studentName) {
    document.getElementById('unsuspendStudentText').innerHTML = `Unsuspend account for: <strong>${studentName}</strong>`;
    document.getElementById('unsuspendNotes').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('unsuspendStudentModal'));
    modal.show();
    
    document.getElementById('confirmUnsuspendBtn').onclick = function() {
        const notes = document.getElementById('unsuspendNotes').value;
        
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Unsuspending...';
        this.disabled = true;
        
        // Make API call to unsuspend student
        fetch('api/user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unsuspend_student',
                user_id: userId,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Account Restored', `${studentName}'s account has been unsuspended`);
                modal.hide();
                // Refresh the page to update the student list
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('error', 'Unsuspension Failed', data.message || 'Failed to unsuspend account');
            }
        })
        .catch(error => {
            showToast('error', 'Network Error', 'Failed to connect to server');
        })
        .finally(() => {
            // Reset button state
            this.innerHTML = '<i class="fas fa-check me-2"></i>Unsuspend Account';
            this.disabled = false;
        });
    };
}

// Make functions globally available
window.viewStudent = viewStudent;
window.resetStudentPassword = resetStudentPassword;
window.suspendStudent = suspendStudent;
window.unsuspendStudent = unsuspendStudent;
window.generatePassword = generatePassword;
window.showToast = showToast;
