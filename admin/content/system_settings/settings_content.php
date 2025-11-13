<?php
// Load current settings
if (!isset($pdo)) {
    require __DIR__ . '/../../../db.php';
}

function getUserSetting($user_id, $key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?");
        $stmt->execute([$user_id, $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Get current admin's information from session
$user_id = $_SESSION['user_id'] ?? 0;
$admin_email = $_SESSION['email'] ?? 'admin@feedloop.com';
$admin_position = $_SESSION['position'] ?? 'Admin';
$admin_role = $_SESSION['role'] ?? 'admin';
$admin_name = $_SESSION['full_name'] ?? 'Admin User';

// Get dynamic theme based on admin role/position
function getAdminTheme($position, $role) {
    $position_lower = strtolower($position);
    
    if (strpos($position_lower, 'super') !== false) {
        return 'animated'; // Super Admin gets animated theme
    } else {
        return 'light'; // Regular Admin gets light theme
    }
}

// Load user-specific settings
$default_theme = getAdminTheme($admin_position, $admin_role);
$session_timeout = getUserSetting($user_id, 'session_timeout', '30');
$require_strong_password = getUserSetting($user_id, 'require_strong_password', '1');
$enable_activity_logging = getUserSetting($user_id, 'enable_activity_logging', '1');
$max_login_attempts = getUserSetting($user_id, 'max_login_attempts', '5');
$theme_mode = getUserSetting($user_id, 'theme_mode', $default_theme);
?>

<div class="dashboard-header">
    <h1><i class="fas fa-user-cog me-2"></i>Personal Settings</h1>
    <p>Configure your personal preferences and account settings</p>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Personal Account:</strong> <?php echo htmlspecialchars($admin_name); ?> (<?php echo htmlspecialchars($admin_position); ?>)
        <br><small>These settings are specific to your account and won't affect other administrators.</small>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>General Settings</h3>
            </div>
            <div class="card-body">
                <form id="generalSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">System Name</label>
                        <input type="text" class="form-control" value="FeedLoop" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-2"></i>Your Email Address</label>
                        <input type="email" class="form-control" id="adminEmail" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                        <small class="text-muted">This email will be used for notifications and account recovery</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-clock me-2"></i>Your Session Timeout (minutes)</label>
                        <input type="number" class="form-control" id="sessionTimeout" value="<?php echo htmlspecialchars($session_timeout); ?>" min="5" max="1440" required>
                        <small class="text-muted">How long you stay logged in when inactive</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-palette me-2"></i>Your Personal Theme</label>
                        <select class="form-select" id="themeMode">
                            <option value="light" <?php echo $theme_mode === 'light' ? 'selected' : ''; ?>>
                                <i class="fas fa-sun"></i> Light Mode
                            </option>
                            <option value="dark" <?php echo $theme_mode === 'dark' ? 'selected' : ''; ?>>
                                <i class="fas fa-moon"></i> Dark Mode
                            </option>
                            <option value="animated" <?php echo $theme_mode === 'animated' ? 'selected' : ''; ?>>
                                <i class="fas fa-palette"></i> Animated Color Mix
                            </option>
                            <option value="auto" <?php echo $theme_mode === 'auto' ? 'selected' : ''; ?>>
                                <i class="fas fa-adjust"></i> Auto (System)
                            </option>
                        </select>
                        <small class="text-muted">
                            <i class="fas fa-user-tag me-1"></i>Your role (<?php echo htmlspecialchars($admin_position); ?>) default: <?php echo ucfirst($default_theme); ?> Mode
                            <br><i class="fas fa-lightbulb me-1"></i>This theme will only apply to your account
                        </small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary" id="saveGeneralSettings">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <button type="button" class="btn btn-danger" onclick="showDeleteAccountModal()">
                            <i class="fas fa-trash-alt me-2"></i>Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shield-alt me-2"></i>Personal Security Preferences</h3>
            </div>
            <div class="card-body">
                <form id="securitySettingsForm">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="requireStrongPassword" <?php echo $require_strong_password == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requireStrongPassword">
                                <i class="fas fa-lock me-2"></i>Require Strong Passwords for My Account
                            </label>
                            <small class="form-text text-muted d-block">Enforce strong password requirements when you change your password</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enableActivityLogging" <?php echo $enable_activity_logging == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enableActivityLogging">
                                <i class="fas fa-history me-2"></i>Enable Activity Logging for My Account
                            </label>
                            <small class="form-text text-muted d-block">Track and log your login activities and actions</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-exclamation-triangle me-2"></i>Max Login Attempts for My Account</label>
                        <input type="number" class="form-control" id="maxLoginAttempts" value="<?php echo htmlspecialchars($max_login_attempts); ?>" min="1" max="20" required>
                        <small class="text-muted">How many failed login attempts before your account is temporarily locked</small>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveSecuritySettings">
                        <i class="fas fa-shield-alt me-2"></i>Update Security
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Password Change Section -->
<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-key me-2"></i>Change Password</h3>
    </div>
    <div class="card-body">
        <form id="changePasswordForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock me-2"></i>Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                        <small class="text-muted">Enter your current password to confirm changes</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-unlock-alt me-2"></i>New Password</label>
                        <input type="password" class="form-control" id="newPassword" required>
                        <small class="text-muted">Minimum 8 characters, include letters and numbers</small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-check-circle me-2"></i>Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                        <small class="text-muted">Re-enter your new password</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="password-strength mt-4">
                            <small class="text-muted">Password Strength:</small>
                            <div class="progress mt-1" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="passwordStrengthText" class="text-muted">Enter a password to see strength</small>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                <i class="fas fa-key me-2"></i>Change Password
            </button>
        </form>
    </div>
</div>


<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-info-circle me-2"></i>Account & System Information</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong><i class="fas fa-user me-1"></i>Your Account:</strong><br>
                <?php echo htmlspecialchars($admin_name); ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($admin_role); ?></small>
            </div>
            <div class="col-md-3">
                <strong><i class="fas fa-envelope me-1"></i>Email:</strong><br>
                <?php echo htmlspecialchars($admin_email); ?>
                <br><small class="text-muted">Personal contact</small>
            </div>
            <div class="col-md-3">
                <strong><i class="fas fa-palette me-1"></i>Current Theme:</strong><br>
                <?php echo ucfirst($theme_mode); ?> Mode
                <br><small class="text-muted">Personal preference</small>
            </div>
            <div class="col-md-3">
                <strong><i class="fas fa-server me-1"></i>System:</strong><br>
                FeedLoop v1.0
                <br><small class="text-muted">PHP <?php echo phpversion(); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-warning me-2"></i>
                    <strong>WARNING:</strong> This action cannot be undone!
                </div>
                
                <p><strong>You are about to permanently delete your admin account:</strong></p>
                <div class="bg-light p-3 rounded mb-3">
                    <strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Unknown'); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'Unknown'); ?><br>
                    <strong>Position:</strong> <?php echo htmlspecialchars($_SESSION['position'] ?? 'Unknown'); ?>
                </div>
                
                <p>This will:</p>
                <ul class="text-danger">
                    <li>Permanently delete your admin account</li>
                    <li>Remove all your personal data</li>
                    <li>Log you out immediately</li>
                    <li>Prevent you from accessing the admin panel</li>
                </ul>
                
                <div class="mt-4">
                    <label for="deleteConfirmation" class="form-label">
                        <strong>Type "DELETE" to confirm:</strong>
                    </label>
                    <input type="text" class="form-control" id="deleteConfirmation" placeholder="Type DELETE here" autocomplete="off">
                    <small class="text-muted">This confirmation is case-sensitive</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="deleteAccount()">
                    <i class="fas fa-trash-alt me-2"></i>Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal Functions -->
<script>
// Show delete account modal
function showDeleteAccountModal() {
    const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
    modal.show();
    
    // Reset confirmation input
    document.getElementById('deleteConfirmation').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

// Enable delete button when correct confirmation is typed
document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('deleteConfirmation');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    if (confirmInput && confirmBtn) {
        confirmInput.addEventListener('input', function() {
            confirmBtn.disabled = this.value !== 'DELETE';
        });
    }
});

// Delete account function
function deleteAccount() {
    const confirmation = document.getElementById('deleteConfirmation').value;
    
    if (confirmation !== 'DELETE') {
        showNotification('error', 'Please type DELETE to confirm');
        return;
    }
    
    // Make API call to delete account
    fetch('../../api/delete_account.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ confirm: 'DELETE' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Account deleted successfully. Redirecting...');
            setTimeout(() => {
                window.location.href = '../../login/';
            }, 2000);
        } else {
            showNotification('error', data.message || 'Failed to delete account');
        }
    })
    .catch(error => {
        console.error('Delete account error:', error);
        showNotification('error', 'Network error: ' + error.message);
    });
}
</script>

<!-- Include Settings JavaScript -->
<script src="../../assets/js/admin/settings.js?v=<?php echo time(); ?>"></script>

<!-- Initialize Settings -->
<script>
// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme if function is available
    if (typeof initializeTheme === 'function') {
        initializeTheme();
    }
    
    // Initialize new features
    initializePasswordChange();
    initializeStudentManagement();
    loadStudentStats();
});

// Also initialize immediately if DOM is already loaded (for dynamically loaded content)
if (document.readyState === 'loading') {
    // DOM is still loading, wait for DOMContentLoaded
} else {
    // DOM is already loaded, initialize immediately
    setTimeout(function() {
        // Call global reinitialize function if available
        if (typeof window.reinitializeSettings === 'function') {
            window.reinitializeSettings();
        } else {
            // Fallback initialization
            if (typeof initializeTheme === 'function') {
                initializeTheme();
            }
            if (typeof window.initializeSettingsForms === 'function') {
                window.initializeSettingsForms();
            }
        }
        initializePasswordChange();
        initializeStudentManagement();
        loadStudentStats();
    }, 200); // Increased delay to ensure all scripts are loaded
}

// Password Change Functions
function initializePasswordChange() {
    const newPasswordField = document.getElementById('newPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const changePasswordForm = document.getElementById('changePasswordForm');
    
    if (newPasswordField) {
        newPasswordField.addEventListener('input', checkPasswordStrength);
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', validatePasswordMatch);
    }
    
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handlePasswordChange);
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let strength = 0;
    let feedback = '';
    
    if (password.length >= 8) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[a-z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    
    if (strength === 0) {
        feedback = 'Enter a password';
        strengthBar.className = 'progress-bar';
    } else if (strength <= 25) {
        feedback = 'Weak';
        strengthBar.className = 'progress-bar bg-danger';
    } else if (strength <= 50) {
        feedback = 'Fair';
        strengthBar.className = 'progress-bar bg-warning';
    } else if (strength <= 75) {
        feedback = 'Good';
        strengthBar.className = 'progress-bar bg-info';
    } else {
        feedback = 'Strong';
        strengthBar.className = 'progress-bar bg-success';
    }
    
    strengthBar.style.width = strength + '%';
    strengthText.textContent = feedback;
}

function validatePasswordMatch() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const confirmField = document.getElementById('confirmPassword');
    
    if (confirmPassword && newPassword !== confirmPassword) {
        confirmField.setCustomValidity('Passwords do not match');
        confirmField.classList.add('is-invalid');
    } else {
        confirmField.setCustomValidity('');
        confirmField.classList.remove('is-invalid');
    }
}

async function handlePasswordChange(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const submitBtn = document.getElementById('changePasswordBtn');
    const originalText = submitBtn.innerHTML;
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showNotification('error', 'New password and confirmation do not match');
        return;
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing Password...';
    submitBtn.disabled = true;
    
    try {
        const apiPath = getApiPath('change_password.php');
        const response = await fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            // Clear the form
            document.getElementById('changePasswordForm').reset();
            checkPasswordStrength(); // Reset strength indicator
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Error changing password: ' + error.message);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Student Management Functions
function initializeStudentManagement() {
    const searchBtn = document.getElementById('searchStudentBtn');
    const studentSearch = document.getElementById('studentSearch');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', searchStudent);
    }
    
    if (studentSearch) {
        studentSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudent();
            }
        });
    }
}

async function loadStudentStats() {
    try {
        const apiPath = getApiPath('student_stats.php');
        const response = await fetch(apiPath);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalStudentsCount').textContent = result.stats.total_students;
            document.getElementById('activeStudentsCount').textContent = result.stats.active_students;
            document.getElementById('pendingFeedbackCount').textContent = result.stats.pending_feedback;
            document.getElementById('newStudentsCount').textContent = result.stats.new_students;
        }
    } catch (error) {
        console.warn('Could not load student statistics:', error);
    }
}

function searchStudent() {
    const searchTerm = document.getElementById('studentSearch').value;
    if (searchTerm.trim()) {
        showNotification('info', `Searching for student: ${searchTerm}`);
        // Here you would implement the actual search functionality
        // For now, just show a placeholder message
    } else {
        showNotification('warning', 'Please enter a student name or ID to search');
    }
}

function showAddStudentModal() {
    const modal = new bootstrap.Modal(document.getElementById('addStudentModal'));
    modal.show();
}

function exportStudentList() {
    const modal = new bootstrap.Modal(document.getElementById('exportStudentModal'));
    modal.show();
}

function bulkStudentActions() {
    showNotification('info', 'Bulk Actions - This would show options for bulk student operations');
    // Placeholder for bulk actions functionality
}

// Add Student Modal Functions
document.addEventListener('DOMContentLoaded', function() {
    // Add Student Form Handler
    const saveStudentBtn = document.getElementById('saveStudentBtn');
    if (saveStudentBtn) {
        saveStudentBtn.addEventListener('click', handleAddStudent);
    }
    
    // Export Form Handler
    const generateExportBtn = document.getElementById('generateExportBtn');
    if (generateExportBtn) {
        generateExportBtn.addEventListener('click', handleExportGeneration);
    }
    
    // Show/Hide PDF options based on format selection
    const exportFormatRadios = document.querySelectorAll('input[name="exportFormat"]');
    exportFormatRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const pdfOptions = document.getElementById('pdfOptions');
            if (this.value === 'pdf') {
                pdfOptions.style.display = 'block';
            } else {
                pdfOptions.style.display = 'none';
            }
        });
    });
});

async function handleAddStudent() {
    const form = document.getElementById('addStudentForm');
    const formData = new FormData(form);
    const saveBtn = document.getElementById('saveStudentBtn');
    const originalText = saveBtn.innerHTML;
    
    // Validate required fields
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding Student...';
    saveBtn.disabled = true;
    
    try {
        const studentData = {
            student_number: formData.get('student_number'),
            email: formData.get('email'),
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            course: formData.get('course'),
            year_level: formData.get('year_level'),
            phone_number: formData.get('phone_number')
        };
        
        const response = await fetch('/admin/api/add_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(studentData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Student added successfully!');
            form.reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            modal.hide();
            
            // Refresh student statistics
            loadStudentStats();
        } else {
            showNotification('error', result.message || 'Error adding student');
        }
    } catch (error) {
        console.error('Error adding student:', error);
        showNotification('error', 'Error adding student: ' + error.message);
    } finally {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }
}

async function handleExportGeneration() {
    const form = document.getElementById('exportStudentForm');
    const generateBtn = document.getElementById('generateExportBtn');
    const originalText = generateBtn.innerHTML;
    
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
    generateBtn.disabled = true;
    
    try {
        const exportData = {
            format: document.querySelector('input[name="exportFormat"]:checked').value,
            include_course_info: document.getElementById('includeCourseInfo').checked,
            include_feedback_stats: document.getElementById('includeFeedbackStats').checked,
            include_activity_status: document.getElementById('includeActivityStatus').checked,
            include_summary: document.getElementById('includeSummary').checked,
            include_charts: document.getElementById('includeCharts').checked
        };
        
        const response = await fetch('/admin/api/export_students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(exportData)
        });
        
        if (exportData.format === 'pdf') {
            // Handle PDF download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `student_report_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showNotification('success', 'PDF report generated and downloaded successfully!');
        } else {
            // Handle CSV download
            const result = await response.json();
            if (result.success) {
                const csvContent = result.csv_data;
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `student_list_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                showNotification('success', 'CSV file generated and downloaded successfully!');
            } else {
                showNotification('error', result.message || 'Error generating CSV');
            }
        }
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportStudentModal'));
        modal.hide();
        
    } catch (error) {
        console.error('Error generating export:', error);
        showNotification('error', 'Error generating export: ' + error.message);
    } finally {
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    }
}
{{ ... }}
</script>