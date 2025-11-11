/**
 * Super Admin Registration JavaScript
 * Handles redirect countdown, password validation, and security confirmation
 */

// Redirect countdown functionality
let countdown = 5;
const countdownElement = document.getElementById('countdown');

const timer = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(timer);
        window.location.href = 'login.php';
    }
}, 1000);

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Enhanced security notice for Super Admin
document.getElementById('superAdminForm').addEventListener('submit', function(e) {
    const confirmed = confirm(
        '⚠️ SUPER ADMIN REGISTRATION\n\n' +
        'You are creating a Super Administrator account with FULL SYSTEM ACCESS.\n\n' +
        'This includes:\n' +
        '• Complete user management\n' +
        '• System configuration\n' +
        '• Database access\n' +
        '• Security settings\n\n' +
        'Are you sure you want to proceed?'
    );
    
    if (!confirmed) {
        e.preventDefault();
        return false;
    }
});
