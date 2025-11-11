// Settings Management JavaScript - Dynamic Loading Compatible
(function() {
    // Initialize immediately if DOM is ready, or wait for it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSettingsForms);
    } else {
        // DOM is already loaded, initialize immediately
        initializeSettingsForms();
    }
})();

// Function to get correct API path based on current location
function getApiPath(endpoint) {
    const currentPath = window.location.pathname;
    console.log('getApiPath - Current path:', currentPath);
    
    // Extract the base path (e.g., "/feedloop/")
    const pathParts = currentPath.split('/');
    let basePath = '';
    
    // Find the feedloop part in the path
    const feedloopIndex = pathParts.indexOf('feedloop');
    console.log('getApiPath - Feedloop index:', feedloopIndex);
    
    if (feedloopIndex !== -1) {
        basePath = pathParts.slice(0, feedloopIndex + 1).join('/');
    } else {
        // Fallback: assume we're in feedloop directory
        basePath = '/feedloop';
    }
    
    // Create absolute path from web root
    const apiPath = `${basePath}/admin/api/${endpoint}`;
    console.log('getApiPath - Final API path:', apiPath);
    
    return apiPath;
}

function initializeSettingsForms() {
    // Handle General Settings Form
    const generalForm = document.getElementById('generalSettingsForm');
    if (generalForm) {
        generalForm.removeEventListener('submit', handleGeneralSettings);
        generalForm.addEventListener('submit', handleGeneralSettings);
    }

    // Handle Security Settings Form
    const securityForm = document.getElementById('securitySettingsForm');
    if (securityForm) {
        securityForm.removeEventListener('submit', handleSecuritySettings);
        securityForm.addEventListener('submit', handleSecuritySettings);
    }
    
    // Initialize delete account modal functionality
    initializeDeleteAccountModal();
}

function initializeDeleteAccountModal() {
    const deleteConfirmation = document.getElementById('deleteConfirmation');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (deleteConfirmation && confirmDeleteBtn) {
        deleteConfirmation.addEventListener('input', function() {
            const isValid = this.value === 'DELETE';
            confirmDeleteBtn.disabled = !isValid;
            
            if (isValid) {
                confirmDeleteBtn.classList.remove('btn-secondary');
                confirmDeleteBtn.classList.add('btn-danger');
            } else {
                confirmDeleteBtn.classList.remove('btn-danger');
                confirmDeleteBtn.classList.add('btn-secondary');
            }
        });
    }
}

// Handle General Settings Form Submission
async function handleGeneralSettings(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Prevent multiple submissions
    if (submitBtn.disabled) {
        return;
    }
    
    setButtonLoading(submitBtn, 'Saving...');
    
    try {
        const formData = {
            type: 'general',
            admin_email: document.getElementById('adminEmail').value,
            session_timeout: document.getElementById('sessionTimeout').value,
            theme_mode: document.getElementById('themeMode').value
        };

        const apiPath = getApiPath('save_settings.php');

        const response = await fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            
            // Apply theme change immediately with notification
            applyTheme(formData.theme_mode, true);
        } else {
            showNotification('error', result.message || 'Unknown error occurred');
        }
    } catch (error) {
        showNotification('error', 'Error saving settings: ' + error.message);
    } finally {
        resetButton(submitBtn, originalText);
    }
}

// Handle Security Settings Form Submission
async function handleSecuritySettings(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    setButtonLoading(submitBtn, 'Updating...');
    
    try {
        const formData = {
            type: 'security',
            require_strong_password: document.getElementById('requireStrongPassword').checked,
            enable_activity_logging: document.getElementById('enableActivityLogging').checked,
            max_login_attempts: document.getElementById('maxLoginAttempts').value
        };

        const apiPath = getApiPath('save_settings.php');

        const response = await fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
        } else {
            showNotification('error', result.message || 'Unknown error occurred');
        }
    } catch (error) {
        showNotification('error', 'Error saving security settings: ' + error.message);
    } finally {
        resetButton(submitBtn, originalText);
    }
}

// Utility Functions
function setButtonLoading(button, text) {
    button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${text}`;
    button.disabled = true;
}

function resetButton(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

function showNotification(type, message) {
    
    // Remove any existing notifications first (including other types)
    const existingNotifications = document.querySelectorAll('.settings-notification, .toast, .alert-dismissible');
    existingNotifications.forEach(notification => {
        if (notification.parentNode) {
            notification.remove();
        }
    });
    
    // Create notification element with unique styling
    const notification = document.createElement('div');
    
    // Define unique colors and styles for different types
    let alertClass, bgColor, borderColor, textColor, icon, iconColor;
    
    if (type === 'success') {
        alertClass = 'alert-success';
        bgColor = 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)';
        borderColor = '#28a745';
        textColor = '#155724';
        icon = 'check-circle';
        iconColor = '#28a745';
    } else {
        alertClass = 'alert-danger';
        bgColor = 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)';
        borderColor = '#dc3545';
        textColor = '#721c24';
        icon = 'exclamation-triangle';
        iconColor = '#dc3545';
    }
    
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed settings-notification`;
    notification.style.cssText = `
        top: 80px; 
        right: 20px; 
        left: 20px;
        z-index: 10000; 
        max-width: 500px;
        margin: 0 auto;
        background: ${bgColor};
        border: 3px solid ${borderColor};
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.25);
        color: ${textColor};
        font-weight: 600;
        animation: slideInDown 0.4s ease-out;
        transform: translateX(0);
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${icon} me-3" style="color: ${iconColor}; font-size: 1.2rem;"></i>
            <div class="flex-grow-1">
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong><br>
                <span style="font-size: 0.9rem;">${message}</span>
            </div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" style="filter: brightness(0.7);"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Add animation styles if not already added
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInDown {
                from {
                    transform: translateY(-100px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutUp {
                from {
                    transform: translateY(0);
                    opacity: 1;
                }
                to {
                    transform: translateY(-100px);
                    opacity: 0;
                }
            }
            
            .settings-notification:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 35px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Auto remove after 5 seconds with slide out animation
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutUp 0.4s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 400);
        }
    }, 5000);
}

// Form validation helpers
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateSessionTimeout(timeout) {
    const timeoutNum = parseInt(timeout);
    return timeoutNum >= 5 && timeoutNum <= 1440; // 5 minutes to 24 hours
}

function validateMaxLoginAttempts(attempts) {
    const attemptsNum = parseInt(attempts);
    return attemptsNum >= 1 && attemptsNum <= 20;
}

// Show delete account modal
function showDeleteAccountModal() {
    const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
    modal.show();
    
    // Reset confirmation input when modal is shown
    document.getElementById('deleteConfirmation').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

// Delete account function
async function deleteAccount() {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmDeleteBtn.innerHTML;
    
    try {
        // Show loading state
        confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
        confirmDeleteBtn.disabled = true;
        
        const deleteApiPath = getApiPath('delete_account.php');

        const response = await fetch(deleteApiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_admin_account'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            showNotification('Account deleted successfully. You will be logged out.', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
            modal.hide();
            
            // Redirect to login after 2 seconds
            setTimeout(() => {
                window.location.href = '../login/unified_login.php?message=account_deleted';
            }, 2000);
        } else {
            throw new Error(result.message || 'Failed to delete account');
        }
    } catch (error) {
        showNotification('error', 'Error: ' + error.message);
        
        // Reset button
        confirmDeleteBtn.innerHTML = originalText;
        confirmDeleteBtn.disabled = false;
    }
}

// Theme application functions
function applyTheme(theme, showNotificationFlag = false) {
    const body = document.body;
    const html = document.documentElement;
    
    // Remove existing theme classes
    body.classList.remove('theme-light', 'theme-dark', 'theme-animated', 'theme-auto');
    html.classList.remove('theme-light', 'theme-dark', 'theme-animated', 'theme-auto');
    
    // Apply new theme
    body.classList.add(`theme-${theme}`);
    html.classList.add(`theme-${theme}`);
    
    // Store theme preference
    localStorage.setItem('admin_theme', theme);
    
    // Apply theme-specific styles
    switch(theme) {
        case 'dark':
            applyDarkTheme();
            break;
        case 'animated':
            applyAnimatedTheme();
            break;
        case 'auto':
            applyAutoTheme();
            break;
        default:
            applyLightTheme();
    }
    
    // Only show notification if explicitly requested (when user actively changes theme)
    if (showNotificationFlag) {
        showNotification('success', `Theme changed to ${theme} mode`);
    }
}

function applyLightTheme() {
    // Light theme is the default, no additional styles needed
    removeAnimatedStyles();
}

function applyDarkTheme() {
    removeAnimatedStyles();
    
    // Inject dark theme styles
    let darkStyle = document.getElementById('dark-theme-style');
    if (!darkStyle) {
        darkStyle = document.createElement('style');
        darkStyle.id = 'dark-theme-style';
        document.head.appendChild(darkStyle);
    }
    
    darkStyle.textContent = `
        .theme-dark {
            background: #1a1a1a !important;
            color: #ffffff !important;
        }
        
        .theme-dark .sidebar {
            background: linear-gradient(135deg, #2d3748, #1a202c) !important;
        }
        
        .theme-dark .main-content {
            background: #1a1a1a !important;
        }
        
        .theme-dark .card {
            background: #2d3748 !important;
            border: 1px solid #4a5568 !important;
            color: #ffffff !important;
        }
        
        .theme-dark .card-header {
            background: linear-gradient(135deg, #4a5568, #2d3748) !important;
            border-bottom: 1px solid #4a5568 !important;
        }
        
        .theme-dark .form-control,
        .theme-dark .form-select {
            background: #2d3748 !important;
            border: 1px solid #4a5568 !important;
            color: #ffffff !important;
        }
        
        .theme-dark .form-control:focus,
        .theme-dark .form-select:focus {
            background: #2d3748 !important;
            border-color: #63b3ed !important;
            color: #ffffff !important;
        }
        
        .theme-dark .modal-content {
            background: #2d3748 !important;
            color: #ffffff !important;
        }
        
        .theme-dark .alert-danger {
            background: #742a2a !important;
            border-color: #c53030 !important;
            color: #fed7d7 !important;
        }
    `;
}

function applyAnimatedTheme() {
    // Inject animated theme styles
    let animatedStyle = document.getElementById('animated-theme-style');
    if (!animatedStyle) {
        animatedStyle = document.createElement('style');
        animatedStyle.id = 'animated-theme-style';
        document.head.appendChild(animatedStyle);
    }
    
    animatedStyle.textContent = `
        @keyframes colorShift {
            0% { filter: hue-rotate(0deg); }
            25% { filter: hue-rotate(90deg); }
            50% { filter: hue-rotate(180deg); }
            75% { filter: hue-rotate(270deg); }
            100% { filter: hue-rotate(360deg); }
        }
        
        @keyframes backgroundPulse {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .theme-animated .sidebar {
            background: linear-gradient(-45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3) !important;
            background-size: 400% 400% !important;
            animation: backgroundPulse 8s ease-in-out infinite !important;
        }
        
        .theme-animated .dashboard-header {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe) !important;
            background-size: 400% 400% !important;
            animation: backgroundPulse 6s ease-in-out infinite !important;
        }
        
        .theme-animated .card-header {
            background: linear-gradient(-45deg, #a8edea, #fed6e3, #d299c2, #fef9d7, #667eea, #764ba2) !important;
            background-size: 400% 400% !important;
            animation: backgroundPulse 10s ease-in-out infinite !important;
        }
        
        .theme-animated .btn-primary {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c) !important;
            background-size: 400% 400% !important;
            animation: backgroundPulse 4s ease-in-out infinite !important;
            border: none !important;
        }
        
        .theme-animated .stat-card {
            animation: colorShift 12s linear infinite !important;
            transform-origin: center !important;
        }
        
        .theme-animated .stat-card:hover {
            animation: colorShift 2s linear infinite !important;
        }
        
        .theme-animated .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            animation: colorShift 3s linear infinite !important;
        }
    `;
}

function applyAutoTheme() {
    removeAnimatedStyles();
    
    // Detect system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyDarkTheme();
    } else {
        applyLightTheme();
    }
}

function removeAnimatedStyles() {
    const animatedStyle = document.getElementById('animated-theme-style');
    if (animatedStyle) {
        animatedStyle.remove();
    }
}

// Initialize theme on page load
async function initializeTheme() {
    try {
        // Get user's theme from server
        const apiPath = getApiPath('get_user_theme.php');
        const response = await fetch(apiPath);
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                applyTheme(data.theme);
                
                // Update select if it exists
                const themeSelect = document.getElementById('themeMode');
                if (themeSelect) {
                    themeSelect.value = data.theme;
                }
                return;
            }
        }
    } catch (error) {
        // Fallback to localStorage if server request fails
        console.warn('Failed to load theme from server, using fallback');
    }
    
    // Fallback: use localStorage or default
    const savedTheme = localStorage.getItem('admin_theme') || 'light';
    applyTheme(savedTheme);
    
    // Update select if it exists
    const themeSelect = document.getElementById('themeMode');
    if (themeSelect) {
        themeSelect.value = savedTheme;
    }
}

// Make functions globally available
window.showDeleteAccountModal = showDeleteAccountModal;
window.deleteAccount = deleteAccount;
window.applyTheme = applyTheme;
window.initializeTheme = initializeTheme;

// Re-initialize when content is dynamically loaded
window.initializeSettingsForms = initializeSettingsForms;

// Auto-initialize theme when this script loads
(function() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTheme);
    } else {
        // DOM is already loaded, initialize theme immediately
        initializeTheme();
    }
})();