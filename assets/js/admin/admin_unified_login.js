/**
 * Admin Unified Login JavaScript
 * Handles theme detection, login functionality, and session management
 */

// Theme detection and login functionality
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const loginForm = document.getElementById('loginForm');
    
    // Theme detection on username input
    usernameInput.addEventListener('blur', async function() {
        const username = this.value.trim();
        if (username) {
            await detectUserTheme(username);
        }
    });
    
    // Form submission with AJAX
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        await handleLogin();
    });
});

// Detect user theme based on username
async function detectUserTheme(username) {
    try {
        const response = await fetch('api/check_user_role.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Apply theme to body
            document.body.className = result.theme;
            
            // Store theme info for login
            window.userThemeInfo = {
                theme: result.theme,
                role: result.role,
                position: result.position
            };
            
            console.log('Theme applied:', result.theme);
        } else {
            // Reset to default theme if user not found
            document.body.className = 'theme-admin';
            window.userThemeInfo = null;
        }
    } catch (error) {
        console.error('Theme detection error:', error);
        document.body.className = 'theme-admin';
        window.userThemeInfo = null;
    }
}

// Handle login with session management
async function handleLogin() {
    const formData = new FormData(document.getElementById('loginForm'));
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnSpinner = loginBtn.querySelector('.btn-spinner');
    
    // Show loading state
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline-block';
    loginBtn.disabled = true;
    
    try {
        const response = await fetch('unified_login.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store session info
            sessionStorage.setItem('feedloop_session_active', 'true');
            sessionStorage.setItem('feedloop_user_type', result.user_type);
            sessionStorage.setItem('feedloop_user_id', result.user_id);
            
            // Show success modal
            showSuccessModal(result.user_type, result.redirect_url);
        } else {
            // Show error
            showError(result.error || 'Login failed');
            resetLoginButton();
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('Network error. Please try again.');
        resetLoginButton();
    }
}

// Show success modal with countdown
function showSuccessModal(userType, redirectUrl) {
    const modal = document.getElementById('successModal');
    const title = document.getElementById('successTitle');
    const message = document.getElementById('welcomeMessage');
    const countdownSpan = document.getElementById('countdown');
    
    title.textContent = 'Login Successful!';
    message.textContent = `Welcome to your ${userType} account`;
    
    modal.style.display = 'flex';
    
    let countdown = 3;
    const timer = setInterval(() => {
        countdownSpan.textContent = countdown;
        countdown--;
        
        if (countdown < 0) {
            clearInterval(timer);
            window.location.href = redirectUrl;
        }
    }, 1000);
}

// Show error message
function showError(message) {
    const existingAlert = document.querySelector('.alert-danger');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger';
    alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    
    const form = document.getElementById('loginForm');
    form.insertBefore(alertDiv, form.firstChild);
}

// Reset login button
function resetLoginButton() {
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnSpinner = loginBtn.querySelector('.btn-spinner');
    
    btnText.style.display = 'inline';
    btnSpinner.style.display = 'none';
    loginBtn.disabled = false;
}

// Close suspension modal
function closeSuspensionModal() {
    document.getElementById('suspensionModal').style.display = 'none';
}
