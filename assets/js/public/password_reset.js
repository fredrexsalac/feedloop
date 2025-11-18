/**
 * Password Reset JavaScript
 * Handles the multi-step password reset process
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

class PasswordReset {
    constructor() {
        this.currentStep = 1;
        this.resetToken = null;
        this.sessionToken = null;
        this.timer = null;
        this.timeLeft = 900; // 15 minutes
        
        this.initializeEventListeners();
    }
    
    // Helper function to get correct API path
    getApiPath(endpoint) {
        // Use absolute path from root for reliability
        return `/api/password_reset/${endpoint}`;
    }
    
    initializeEventListeners() {
        // Step 1: Email form
        document.getElementById('email-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleEmailSubmit();
        });
        
        // Step 2: Verification form
        document.getElementById('verification-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleVerificationSubmit();
        });
        
        // Step 3: Password form
        document.getElementById('password-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePasswordSubmit();
        });
        
        // Resend code link
        document.getElementById('resend-code').addEventListener('click', (e) => {
            e.preventDefault();
            this.resendCode();
        });
        
        // Password strength checker
        document.getElementById('new-password').addEventListener('input', (e) => {
            this.checkPasswordStrength(e.target.value);
        });
        
        // Auto-format verification code
        document.getElementById('verification-code').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
        });

        // Ensure indicators reflect current step on load
        this.updateStepIndicators(this.currentStep);
    }
    
    async handleEmailSubmit() {
        const form = document.getElementById('email-form');
        const email = document.getElementById('email').value.trim();
        
        if (!this.validateEmail(email)) {
            this.showAlert('Please enter a valid email address', 'danger');
            return;
        }
        
        this.setLoading(form, true);
        
        try {
            const apiUrl = this.getApiPath('request_reset.php');
            console.log('Sending password reset request to:', apiUrl);
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success && data.warning) {
                // Backend chose not to reveal existence; show gentle warning and do not advance
                this.showAlert(data.message || 'If this email is registered, you will receive a password reset code shortly.', 'warning');
                return;
            } else if (data.success) {
                this.resetToken = data.reset_token;
                this.timeLeft = data.expires_in || 900;
                this.showAlert(data.message, 'success');
                this.nextStep();
                this.startTimer();
            } else {
                this.showAlert(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Network error. Please try again.', 'danger');
        } finally {
            this.setLoading(form, false);
        }
    }
    
    async handleVerificationSubmit() {
        const form = document.getElementById('verification-form');
        const code = document.getElementById('verification-code').value.trim();
        
        if (!/^\d{6}$/.test(code)) {
            this.showAlert('Please enter a valid 6-digit code', 'danger');
            return;
        }
        
        this.setLoading(form, true);
        
        try {
            const response = await fetch(this.getApiPath('verify_code.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reset_token: this.resetToken,
                    reset_code: code
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.sessionToken = data.session_token;
                this.showAlert(data.message, 'success');
                this.nextStep();
                this.stopTimer();
                this.timeLeft = data.expires_in || 600; // 10 minutes for password reset
                this.startTimer();
            } else {
                this.showAlert(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Network error. Please try again.', 'danger');
        } finally {
            this.setLoading(form, false);
        }
    }
    
    async handlePasswordSubmit() {
        const form = document.getElementById('password-form');
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        
        // Validate passwords
        if (newPassword !== confirmPassword) {
            this.showAlert('Passwords do not match', 'danger');
            return;
        }
        
        if (!this.isPasswordStrong(newPassword)) {
            this.showAlert('Password does not meet security requirements', 'danger');
            return;
        }
        
        this.setLoading(form, true);
        
        try {
            const response = await fetch(this.getApiPath('reset_password.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_token: this.sessionToken,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.stopTimer();
                
                // Redirect to login after 3 seconds
                setTimeout(() => {
                    window.location.href = data.redirect || '/auth/login.php';
                }, 3000);
            } else {
                this.showAlert(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Network error. Please try again.', 'danger');
        } finally {
            this.setLoading(form, false);
        }
    }
    
    async resendCode() {
        const email = document.getElementById('email').value;
        
        try {
            const response = await fetch(this.getApiPath('request_reset.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.resetToken = data.reset_token;
                this.timeLeft = data.expires_in || 900;
                this.showAlert('New verification code sent to your email', 'success');
                this.startTimer();
            } else {
                this.showAlert(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Failed to resend code. Please try again.', 'danger');
        }
    }
    
    nextStep() {
        // Hide current step and mark indicator completed
        document.getElementById(`step${this.currentStep}`).classList.remove('active');
        document.getElementById(`step${this.currentStep}-indicator`).classList.remove('active');
        document.getElementById(`step${this.currentStep}-indicator`).classList.add('completed');

        // Advance
        this.currentStep++;

        // Show next step and refresh indicators/connectors comprehensively
        document.getElementById(`step${this.currentStep}`).classList.add('active');
        this.updateStepIndicators(this.currentStep);
    }

    // Force indicators 1..3 and connectors 1..2 into correct state for a given step
    updateStepIndicators(step) {
        const total = 3;
        for (let i = 1; i <= total; i++) {
            const ind = document.getElementById(`step${i}-indicator`);
            if (!ind) continue;
            ind.classList.remove('active', 'completed', 'inactive');
            if (i < step) ind.classList.add('completed');
            else if (i === step) ind.classList.add('active');
            else ind.classList.add('inactive');
        }
        // connectors between 1-2 and 2-3
        for (let i = 1; i <= total - 1; i++) {
            const conn = document.getElementById(`connector${i}`);
            if (!conn) continue;
            if (i < step) conn.classList.add('active');
            else conn.classList.remove('active');
        }
    }
    
    startTimer() {
        this.stopTimer(); // Clear any existing timer
        
        this.timer = setInterval(() => {
            this.timeLeft--;
            
            const minutes = Math.floor(this.timeLeft / 60);
            const seconds = this.timeLeft % 60;
            const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            const timerElement = document.getElementById('timer');
            if (timerElement) {
                timerElement.textContent = `Expires in ${timeString}`;
            }
            
            if (this.timeLeft <= 0) {
                this.stopTimer();
                this.showAlert('Verification code has expired. Please request a new one.', 'warning');
            }
        }, 1000);
    }
    
    stopTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        let strength = 0;
        let feedback = [];

        // Length check
        if (password.length >= 8) strength++; else feedback.push('at least 8 characters');
        // Uppercase check
        if (/[A-Z]/.test(password)) strength++; else feedback.push('uppercase letter');
        // Lowercase check
        if (/[a-z]/.test(password)) strength++; else feedback.push('lowercase letter');
        // Number check
        if (/\d/.test(password)) strength++; else feedback.push('number');
        // No special character requirement anymore

        // Map to status label and styling
        const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
        const palette = {
            weak: '#dc3545',       // red
            fair: '#fd7e14',       // orange
            good: '#ffc107',       // yellow
            strong: '#28a745',     // green
            excellent: '#20c997'   // teal
        };

        let label = 'Weak';
        let color = palette.weak;
        if (strength <= 1) { label = 'Weak'; color = palette.weak; }
        else if (strength === 2) { label = 'Fair'; color = palette.fair; }
        else if (strength === 3) { label = 'Good'; color = palette.good; }
        else if (strength === 4) { label = 'Strong'; color = palette.strong; }
        else if (strength === 5) { label = 'Excellent'; color = palette.excellent; }

        // Update bar
        strengthBar.style.width = widths[strength];
        strengthBar.style.backgroundColor = color;

        // Update helper text
        if (password.length === 0) {
            strengthText.textContent = 'Password must contain at least 8 characters, including uppercase, lowercase, and number.';
            strengthText.className = 'form-text text-muted';
        } else if (feedback.length > 0) {
            strengthText.textContent = `Strength: ${label}. Needs: ${feedback.join(', ')}`;
            strengthText.className = 'form-text ' + (strength <= 2 ? 'text-danger' : 'text-warning');
        } else {
            strengthText.textContent = `Strength: ${label}`;
            strengthText.className = 'form-text ' + (strength >= 4 ? 'text-success' : 'text-warning');
        }
    }
    
    isPasswordStrong(password) {
        return password.length >= 8 &&
               /[A-Z]/.test(password) &&
               /[a-z]/.test(password) &&
               /\d/.test(password);
    }
    
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    setLoading(form, loading) {
        const button = form.querySelector('button[type="submit"]');
        const btnText = button.querySelector('.btn-text');
        const loadingSpan = button.querySelector('.loading');
        
        if (loading) {
            button.disabled = true;
            btnText.style.display = 'none';
            loadingSpan.style.display = 'inline';
        } else {
            button.disabled = false;
            btnText.style.display = 'inline';
            loadingSpan.style.display = 'none';
        }
    }
    
    showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = `alert-${type}`;
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         type === 'danger' ? 'fa-exclamation-circle' : 
                         type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        alertContainer.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Auto-dismiss success alerts after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
}

// Initialize password reset when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PasswordReset();
});

// Prevent back button after successful reset
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
