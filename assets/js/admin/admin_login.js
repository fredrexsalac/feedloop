/**
 * Admin Login JavaScript
 * Handles form submission and login functionality
 */

document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loginBtn = document.getElementById('loginBtn');
    
    // Show loading state
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    loginBtn.disabled = true;
    
    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loginBtn.innerHTML = '<i class="fas fa-check"></i> Success! Redirecting...';
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 1000);
        } else {
            loginBtn.innerHTML = 'Login';
            loginBtn.disabled = false;
            
            // Show error
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (data.error || 'Login failed');
            
            const form = document.querySelector('.admin-login-form');
            const existingAlert = form.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            form.insertBefore(alertDiv, form.firstChild);
        }
    })
    .catch(error => {
        loginBtn.innerHTML = 'Login';
        loginBtn.disabled = false;
        console.error('Login error:', error);
    });
});

// Auto-focus username field
document.getElementById('username').focus();
