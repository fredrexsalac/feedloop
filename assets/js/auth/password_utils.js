/**
 * Password Utilities JavaScript
 * Handles password strength checking and validation
 * Extracted from inline scripts in login/reset_password.php and change_password.php
 */

// Initialize password utilities when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializePasswordUtils();
});

function initializePasswordUtils() {
    // Toggle password visibility functionality
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (!input || !icon) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Initialize password strength checking
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword) {
        newPassword.addEventListener('input', handlePasswordStrengthCheck);
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
}

// Simple password strength check (for reset password page)
function checkPasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthBar = document.getElementById('password-strength');
    
    if (!password || !strengthBar) return;
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    strengthBar.style.width = (strength * 20) + '%';
    
    if (strength < 2) {
        strengthBar.className = 'password-strength strength-weak';
    } else if (strength < 4) {
        strengthBar.className = 'password-strength strength-medium';
    } else {
        strengthBar.className = 'password-strength strength-strong';
    }
}

// Advanced password strength check (for change password page)
function handlePasswordStrengthCheck() {
    const value = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const passwordFeedback = document.getElementById('passwordFeedback');
    
    if (!strengthBar || !passwordFeedback) {
        // Fallback to simple strength check
        checkPasswordStrength();
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    if (value.length >= 8) {
        strength += 25;
    } else {
        feedback.push('at least 8 characters');
    }
    
    if (/[A-Z]/.test(value)) {
        strength += 25;
    } else {
        feedback.push('uppercase letters');
    }
    
    if (/[a-z]/.test(value)) {
        strength += 25;
    } else {
        feedback.push('lowercase letters');
    }
    
    if (/[0-9]/.test(value) || /[^A-Za-z0-9]/.test(value)) {
        strength += 25;
    } else {
        feedback.push('numbers or special characters');
    }
    
    strengthBar.style.width = strength + '%';
    
    if (strength <= 25) {
        strengthBar.className = 'password-strength bg-danger';
        passwordFeedback.className = 'form-text text-danger';
    } else if (strength <= 50) {
        strengthBar.className = 'password-strength bg-warning';
        passwordFeedback.className = 'form-text text-warning';
    } else if (strength <= 75) {
        strengthBar.className = 'password-strength bg-info';
        passwordFeedback.className = 'form-text text-info';
    } else {
        strengthBar.className = 'password-strength bg-success';
        passwordFeedback.className = 'form-text text-success';
    }
    
    if (feedback.length > 0) {
        passwordFeedback.textContent = 'Add ' + feedback.join(', ');
    } else {
        passwordFeedback.textContent = 'Strong password!';
    }
    
    checkPasswordMatch();
}

// Password match checking
function checkPasswordMatch() {
    const password = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Try different possible match feedback elements
    let matchDiv = document.getElementById('password-match') || 
                   document.getElementById('matchFeedback');
    
    if (!password || !confirmPassword || !matchDiv) return;
    
    const passwordValue = password.value;
    const confirmValue = confirmPassword.value;
    
    if (confirmValue === '') {
        if (matchDiv.id === 'matchFeedback') {
            matchDiv.textContent = '';
            matchDiv.className = 'form-text';
        } else {
            matchDiv.innerHTML = '';
        }
        return;
    }
    
    if (passwordValue === confirmValue) {
        if (matchDiv.id === 'matchFeedback') {
            matchDiv.textContent = 'Passwords match!';
            matchDiv.className = 'form-text text-success';
        } else {
            matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Passwords match</small>';
        }
    } else {
        if (matchDiv.id === 'matchFeedback') {
            matchDiv.textContent = 'Passwords do not match!';
            matchDiv.className = 'form-text text-danger';
        } else {
            matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</small>';
        }
    }
}
