/**
 * Admin Registration JavaScript
 * Handles redirect countdown, password validation, and form styling
 */

// Redirect countdown functionality
let countdown = 5;
let timer;
const countdownElement = document.getElementById('countdown');

function startRedirect() {
    timer = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = 'login.php';
        }
    }, 1000);
}

function cancelRedirect() {
    clearInterval(timer);
    document.querySelector('.alert-success div').innerHTML = '<small>✋ Redirect cancelled. You can stay on this page.</small>';
}

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

// Position selection styling
document.getElementById('position').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    this.style.borderColor = '';
    
    if (option.value === 'Admin') {
        this.style.borderColor = '#1976d2';
    }
});

// Dynamic logo border based on selection
document.getElementById('position').addEventListener('change', function() {
    const logoBorder = document.getElementById('logoBorder');
    const position = this.value;
    
    if (logoBorder) {
        switch(position) {
            case 'Admin':
                logoBorder.style.borderColor = '#1976d2';
                break;
            case 'Super Admin':
                logoBorder.style.borderColor = '#d32f2f';
                break;
            default:
                logoBorder.style.borderColor = '#666';
        }
    }
});

// Start the redirect timer if countdown element exists
if (countdownElement) {
    startRedirect();
}
