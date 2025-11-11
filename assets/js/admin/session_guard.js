// Session Guard - Protects admin pages from multiple sessions
// Include this in all admin dashboard pages

(function() {
    'use strict';
    
    // Check if session is valid on page load
    function validateCurrentSession() {
        // Check if required session data exists
        if (!window.sessionStorage.getItem('feedloop_session_active')) {
            console.warn('No active session found');
            redirectToLogin();
            return false;
        }
        
        return true;
    }
    
    // Listen for session invalidation signals
    function setupSessionListeners() {
        // Listen for localStorage changes (cross-tab communication)
        window.addEventListener('storage', function(e) {
            if (e.key === 'session_invalidation') {
                try {
                    const data = JSON.parse(e.newValue);
                    if (data.action === 'session_invalidated') {
                        handleSessionInvalidation();
                    }
                } catch (error) {
                    console.warn('Error parsing session invalidation data:', error);
                }
            }
        });
        
        // Listen for BroadcastChannel messages (modern browsers)
        if (typeof BroadcastChannel !== 'undefined') {
            const channel = new BroadcastChannel('feedloop_session');
            channel.addEventListener('message', function(event) {
                if (event.data.type === 'FORCE_LOGOUT') {
                    handleSessionInvalidation();
                }
            });
        }
    }
    
    // Handle session invalidation
    function handleSessionInvalidation() {
        // Show notification
        showSessionInvalidatedModal();
        
        // Clear local session data
        sessionStorage.clear();
        localStorage.removeItem('feedloop_session_active');
        
        // Redirect after a short delay
        setTimeout(() => {
            redirectToLogin();
        }, 3000);
    }
    
    // Show modal notification
    function showSessionInvalidatedModal() {
        // Create modal if it doesn't exist
        let modal = document.getElementById('sessionInvalidatedModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'sessionInvalidatedModal';
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
                        max-width: 400px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    ">
                        <div style="font-size: 3rem; margin-bottom: 15px;">⚠️</div>
                        <h2 style="color: #dc3545; margin-bottom: 15px;">Session Invalidated</h2>
                        <p style="color: #666; margin-bottom: 20px;">
                            Your session has been invalidated due to a new login from another location. 
                            You will be redirected to the login page.
                        </p>
                        <div style="color: #007bff;">
                            Redirecting in <span id="sessionCountdown">3</span> seconds...
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Start countdown
            let countdown = 3;
            const countdownElement = document.getElementById('sessionCountdown');
            const timer = setInterval(() => {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        }
        
        modal.style.display = 'flex';
    }
    
    // Redirect to login page
    function redirectToLogin() {
        // Determine the correct path to login based on current location
        const currentPath = window.location.pathname;
        let loginPath = '../login/unified_login.php';
        
        // Adjust path based on current directory depth
        if (currentPath.includes('/admin/super_admin/')) {
            loginPath = '../../login/unified_login.php';
        } else if (currentPath.includes('/admin/')) {
            loginPath = '../login/unified_login.php';
        }
        
        window.location.href = loginPath;
    }
    
    // Periodic session validation (every 30 seconds)
    function startPeriodicValidation() {
        setInterval(() => {
            // Make AJAX call to validate session server-side
            fetch('../api/validate_session.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    console.warn('Server-side session validation failed');
                    handleSessionInvalidation();
                }
            })
            .catch(error => {
                console.warn('Session validation request failed:', error);
            });
        }, 30000); // Check every 30 seconds
    }
    
    // Initialize session guard when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Session Guard initialized');
        
        // Mark session as active
        sessionStorage.setItem('feedloop_session_active', 'true');
        
        // Validate current session
        if (validateCurrentSession()) {
            setupSessionListeners();
            startPeriodicValidation();
        }
    });
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible, validate session
            validateCurrentSession();
        }
    });
    
})();
