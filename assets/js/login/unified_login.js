// Unified Login JavaScript with Dynamic Theme Detection

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnSpinner = loginBtn.querySelector('.btn-spinner');
    const logoBorder = document.getElementById('logoBorder');
    
    // Function to apply theme based on username
    function applyTheme(theme) {
        const body = document.body;
        const currentTheme = getCurrentTheme();
        
        // Remove existing theme classes
        body.classList.remove('theme-admin', 'theme-super-admin', 'theme-feedback-manager', 'theme-student');
        
        // Add transition class for smooth theme change
        if (currentTheme !== theme) {
            body.classList.add('theme-transitioning');
            setTimeout(() => {
                body.classList.remove('theme-transitioning');
            }, 800);
        }
        
        body.classList.add(theme);
        
        // Update logo border animation
        updateLogoBorder(theme);
    }
    
    function getCurrentTheme() {
        const body = document.body;
        if (body.classList.contains('theme-admin')) return 'theme-admin';
        if (body.classList.contains('theme-super-admin')) return 'theme-super-admin';
        if (body.classList.contains('theme-feedback-manager')) return 'theme-feedback-manager';
        if (body.classList.contains('theme-student')) return 'theme-student';
        return '';
    }
    
    function updateLogoBorder(theme) {
        // Add special animation based on theme
        logoBorder.classList.remove('admin-border', 'super-admin-border', 'feedback-manager-border', 'student-border');
        
        switch(theme) {
            case 'theme-admin':
                logoBorder.classList.add('admin-border');
                break;
            case 'theme-super-admin':
                logoBorder.classList.add('super-admin-border');
                break;
            case 'theme-feedback-manager':
                logoBorder.classList.add('feedback-manager-border');
                break;
            case 'theme-student':
                logoBorder.classList.add('student-border');
                break;
        }
    }
    
    // Username input event listener for real-time theme changes with flash effect
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        if (username.length >= 3) {
            checkUserRoleAndApplyTheme(username);
        } else if (username.length === 0) {
            // Revert to black background when username is cleared
            revertToBlackBackground();
        }
    });
    
    // Function to check user role from database and apply theme
    async function checkUserRoleAndApplyTheme(username) {
        try {
            console.log('Checking role for username:', username);
            
            const response = await fetch('check_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username })
            });
            
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('API Response:', data);
            
            if (data.success && data.theme) {
                console.log('Applying theme:', data.theme);
                applyThemeWithFlash(data.theme);
            } else {
                console.log('No theme found, no theme applied');
                // Don't apply any theme for non-existent users
                revertToBlackBackground();
            }
        } catch (error) {
            console.error('Theme detection error:', error);
            // On error, don't apply any theme
            revertToBlackBackground();
        }
    }
    
    // Function to apply theme with flash animation
    function applyThemeWithFlash(theme) {
        const body = document.body;
        
        // Remove existing theme classes
        body.classList.remove('theme-admin', 'theme-super-admin', 'theme-feedback-manager', 'theme-student', 'role-detected');
        
        // Add the detected role theme
        body.classList.add(theme);
        
        // Trigger flash animation
        body.classList.add('role-detected');
        
        // Update logo border
        updateLogoBorder(theme);
        
        // Remove flash class after animation completes
        setTimeout(() => {
            body.classList.remove('role-detected');
        }, 1500);
    }
    
    // Function to apply fallback theme based on username pattern
    function applyFallbackTheme(username) {
        // Don't apply any theme for non-existent users
        // Only the database API should determine themes
        console.log('User not found in database, no theme applied for:', username);
        revertToBlackBackground();
    }
    
    // Function to revert to black background
    function revertToBlackBackground() {
        const body = document.body;
        
        // Remove all theme classes and animations
        body.classList.remove('theme-admin', 'theme-super-admin', 'theme-feedback-manager', 'theme-student', 'role-detected');
        
        // Remove logo border animations
        logoBorder.classList.remove('admin-border', 'super-admin-border', 'feedback-manager-border', 'student-border');
        
        // Add smooth transition back to black
        body.classList.add('theme-transitioning');
        setTimeout(() => {
            body.classList.remove('theme-transitioning');
        }, 800);
    }
    
    // Form submission handler
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();
        
        if (!username || !password) {
            showAlert('Please fill in all fields', 'danger');
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('ajax', '1');
        
        // Submit via AJAX
        fetch('unified_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            setLoadingState(false);
            
            if (data.success) {
                // Store user info for session management
                sessionStorage.setItem('login_user_id', data.user_id || 'unknown');
                sessionStorage.setItem('login_user_type', data.user_type || 'unknown');
                
                showSuccessModal(data.user_type, data.redirect_url);
            } else if (data.suspended) {
                // Show suspension modal instead of regular error
                showSuspensionModal(data.message);
            } else {
                showAlert(data.error || 'Login failed', 'danger');
            }
        })
        .catch(error => {
            setLoadingState(false);
            showAlert('Network error. Please try again.', 'danger');
            console.error('Login error:', error);
        });
    });
    
    function setLoadingState(loading) {
        if (loading) {
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-flex';
        } else {
            loginBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
        }
    }
    
    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        // Insert before form
        loginForm.parentNode.insertBefore(alert, loginForm);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    function showSuspensionModal(message) {
        const modal = document.getElementById('suspensionModal');
        const suspensionMessage = document.getElementById('suspensionMessage');
        
        suspensionMessage.textContent = message;
        
        modal.style.display = 'flex';
        
        // Auto-close after 5 seconds
        setTimeout(() => {
            closeSuspensionModal();
        }, 5000);
        
        // Close on click anywhere in modal
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeSuspensionModal();
            }
        });
    }
    
    function closeSuspensionModal() {
        const modal = document.getElementById('suspensionModal');
        
        // Add fade-out class for smooth animation
        modal.classList.add('fade-out');
        
        // Hide modal after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('fade-out');
        }, 300);
        
        // Clear form fields to allow retry
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        document.getElementById('username').focus();
    }
    
    function showSuccessModal(userType, redirectUrl) {
        const modal = document.getElementById('successModal');
        const successIcon = document.getElementById('successIcon');
        const successTitle = document.getElementById('successTitle');
        const welcomeMessage = document.getElementById('welcomeMessage');
        const countdownElement = document.getElementById('countdown');
        
        // Update modal content based on user type
        let welcomeText = '';
        switch(userType) {
            case 'Super Admin':
                welcomeText = 'Welcome, Super Administrator!';
                break;
            case 'Feedback Manager':
                welcomeText = 'Welcome, Feedback Manager!';
                break;
            case 'Admin':
                welcomeText = 'Welcome, Administrator!';
                break;
            case 'Student':
                welcomeText = 'Welcome, Student!';
                break;
            default:
                welcomeText = `Welcome to your ${userType} account!`;
        }
        
        successTitle.textContent = 'Login Successful!';
        welcomeMessage.textContent = welcomeText;
        
        // Apply theme to success icon
        const currentTheme = getCurrentTheme();
        successIcon.className = 'success-icon';
        
        // Show modal
        modal.style.display = 'flex';
        
        // Countdown and redirect
        let countdown = 3;
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                
                // Add exit animation
                modal.style.animation = 'fadeOut 0.3s ease-out forwards';
                
                setTimeout(() => {
                    // Invalidate previous sessions by broadcasting to other tabs
                    invalidatePreviousSessions();
                    
                    // Try to open in new tab, fallback to current tab
                    try {
                        const newTab = window.open(redirectUrl, '_blank');
                        
                        if (newTab && !newTab.closed) {
                            // New tab opened successfully, try to close current tab
                            setTimeout(() => {
                                // Show a message before closing
                                document.body.innerHTML = `
                                    <div style="
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        height: 100vh;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                        color: white;
                                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                        text-align: center;
                                    ">
                                        <div>
                                            <h2>✅ Login Successful!</h2>
                                            <p>Dashboard opened in new tab.</p>
                                            <p><small>You can close this tab now.</small></p>
                                            <button onclick="window.close()" style="
                                                padding: 10px 20px;
                                                background: rgba(255,255,255,0.2);
                                                border: 1px solid rgba(255,255,255,0.3);
                                                color: white;
                                                border-radius: 8px;
                                                cursor: pointer;
                                                margin-top: 15px;
                                            ">Close Tab</button>
                                        </div>
                                    </div>
                                `;
                            }, 500);
                        } else {
                            // Popup blocked or failed, redirect current tab
                            window.location.href = redirectUrl;
                        }
                    } catch (error) {
                        // Error opening new tab, redirect current tab
                        window.location.href = redirectUrl;
                    }
                }, 300);
            }
        }, 1000);
    }
    
    // Show modal for user to choose tab behavior
    function showTabChoiceModal(redirectUrl) {
        // Create modal
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 15px;
                    text-align: center;
                    max-width: 450px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                ">
                    <div style="font-size: 2.5rem; margin-bottom: 15px;">🚀</div>
                    <h2 style="color: #28a745; margin-bottom: 15px;">Login Successful!</h2>
                    <p style="color: #666; margin-bottom: 25px;">
                        How would you like to open your dashboard?
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button onclick="openNewTabAndClose('${redirectUrl}')" style="
                            padding: 12px 20px;
                            background: #007bff;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: 600;
                        ">
                            🗂️ New Tab
                        </button>
                        <button onclick="redirectCurrentTab('${redirectUrl}')" style="
                            padding: 12px 20px;
                            background: #28a745;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: 600;
                        ">
                            📄 Same Tab
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Function to invalidate previous sessions and close other tabs
    function invalidatePreviousSessions() {
        try {
            // Use localStorage to signal other tabs to close
            const sessionInvalidation = {
                action: 'session_invalidated',
                timestamp: Date.now(),
                user_id: sessionStorage.getItem('login_user_id') || 'unknown'
            };
            
            // Broadcast to other tabs
            localStorage.setItem('session_invalidation', JSON.stringify(sessionInvalidation));
            
            // Use BroadcastChannel if available (modern browsers)
            if (typeof BroadcastChannel !== 'undefined') {
                const channel = new BroadcastChannel('feedloop_session');
                channel.postMessage({
                    type: 'FORCE_LOGOUT',
                    timestamp: Date.now()
                });
            }
            
            console.log('Session invalidation signal sent to other tabs');
        } catch (error) {
            console.warn('Could not invalidate previous sessions:', error);
        }
    }
    
    // Listen for session invalidation from other tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'session_invalidation') {
            try {
                const data = JSON.parse(e.newValue);
                if (data.action === 'session_invalidated') {
                    // Force logout and close this tab
                    alert('Your session has been invalidated due to a new login. This tab will now close.');
                    
                    // Clear session data
                    sessionStorage.clear();
                    
                    // Redirect to login or close tab
                    window.location.href = 'unified_login.php';
                }
            } catch (error) {
                console.warn('Error handling session invalidation:', error);
            }
        }
    });
    
    // Listen for BroadcastChannel messages
    if (typeof BroadcastChannel !== 'undefined') {
        const channel = new BroadcastChannel('feedloop_session');
        channel.addEventListener('message', function(event) {
            if (event.data.type === 'FORCE_LOGOUT') {
                alert('Your session has been invalidated due to a new login. This tab will now close.');
                window.location.href = 'unified_login.php';
            }
        });
    }
    
    // Initialize with default black theme (no role detected)
    // Don't apply any theme initially - keep black background
    
    // Add CSS for additional border animations
    const style = document.createElement('style');
    style.textContent = `
        .admin-border::before {
            animation: rotateBorder 3s linear infinite, adminPulse 2s ease-in-out infinite;
        }
        
        .super-admin-border::before {
            animation: rotateBorder 2s linear infinite, superAdminPulse 1.5s ease-in-out infinite;
        }
        
        .feedback-manager-border::before {
            animation: rotateBorder 4s linear infinite, feedbackPulse 3s ease-in-out infinite;
        }
        
        .student-border::before {
            animation: rotateBorder 3s linear infinite, studentPulse 2.5s ease-in-out infinite;
        }
        
        @keyframes adminPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.3); }
            50% { box-shadow: 0 0 30px rgba(40, 167, 69, 0.6); }
        }
        
        @keyframes superAdminPulse {
            0%, 100% { box-shadow: 0 0 25px rgba(253, 126, 20, 0.4); }
            50% { box-shadow: 0 0 40px rgba(253, 126, 20, 0.7); }
        }
        
        @keyframes feedbackPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(154, 205, 50, 0.3); }
            50% { box-shadow: 0 0 35px rgba(154, 205, 50, 0.6); }
        }
        
        @keyframes studentPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 123, 255, 0.3); }
            50% { box-shadow: 0 0 30px rgba(0, 123, 255, 0.5); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.9); }
        }
        
        .fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
    `;
    document.head.appendChild(style);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Enter key to submit form
    if (e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
        e.preventDefault();
        document.getElementById('loginForm').dispatchEvent(new Event('submit'));
    }
});

// Auto-focus username field
window.addEventListener('load', function() {
    document.getElementById('username').focus();
});

// Global functions for tab choice modal
window.openNewTabAndClose = function(redirectUrl) {
    // Open dashboard in new tab
    const newTab = window.open(redirectUrl, '_blank');
    
    if (newTab) {
        // Try to close current tab after a short delay
        setTimeout(() => {
            window.close();
            
            // If window.close() doesn't work, redirect to blank page
            if (!window.closed) {
                window.location.href = 'about:blank';
            }
        }, 1000);
    } else {
        // If popup blocked, show message and redirect
        alert('Popup blocked! Redirecting in current tab...');
        window.location.href = redirectUrl;
    }
};

window.redirectCurrentTab = function(redirectUrl) {
    // Simply redirect current tab
    window.location.href = redirectUrl;
};
