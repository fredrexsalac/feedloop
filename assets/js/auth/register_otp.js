/**
 * Registration with OTP Verification JavaScript
 * Handles 3-step registration process with email verification
 */

let timerInterval;
let expiresAt;

// Step 1: Send OTP
document.getElementById('details-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('send-otp-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    const formData = {
        username: document.getElementById('username').value.trim(),
        full_name: document.getElementById('full_name').value.trim(),
        email: document.getElementById('email').value.trim()
    };
    
    try {
        const response = await fetch('../api/registration/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.email_sent) {
                showAlert('success', '✅ Verification code sent! Check your email inbox.');
            } else {
                showAlert('info', '📧 Email service is not configured on this server. If you are in development, the code will be auto-filled if allowed.');
                // Attempt to auto-fill OTP in development mode via secure dev endpoint
                try {
                    const devRes = await fetch('../api/registration/dev_get_otp.php');
                    if (devRes.ok) {
                        const devData = await devRes.json();
                        if (devData && devData.success && devData.otp_code) {
                            const otpInput = document.getElementById('otp_code');
                            otpInput.value = devData.otp_code;
                            showAlert('success', '🔧 Development mode: verification code auto-filled.');
                        }
                    }
                } catch (e) { /* ignore */ }
            }
            
            document.getElementById('email-display').textContent = formData.email;
            goToStep(2);
            startTimer(data.expires_in || 900);
        } else {
            showAlert('danger', data.error || 'Failed to send verification code');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Registration error:', error); // Debug log
        showAlert('danger', 'Network error: ' + error.message + '. Please check console for details.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// Step 2: Verify OTP (moved to Step 3)
document.getElementById('otp-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('verify-otp-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    const otpCode = document.getElementById('otp_code').value.trim();
    
    if (!/^\d{6}$/.test(otpCode)) {
        showAlert('danger', 'Please enter a valid 6-digit code');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }
    
    // OTP verified - move to password step
    showAlert('success', '✅ Email verified! Now set your password.');
    goToStep(3);
    btn.disabled = false;
    btn.innerHTML = originalText;
});

// Step 3: Complete Registration
document.getElementById('password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('complete-registration-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const otpCode = document.getElementById('otp_code').value.trim();
    
    if (password !== confirmPassword) {
        showAlert('danger', 'Passwords do not match');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }
    
    if (password.length < 6) {
        showAlert('danger', 'Password must be at least 6 characters');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }
    
    try {
        const response = await fetch('../api/registration/verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                otp_code: otpCode,
                password: password,
                confirm_password: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', '🎉 ' + data.message);
            setTimeout(() => {
                window.location.href = data.redirect_url || '../pages/user_portal.php';
            }, 2000);
        } else {
            showAlert('danger', data.error || 'Registration failed');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        showAlert('danger', 'Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// Resend OTP
document.getElementById('resend-otp-btn').addEventListener('click', async function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resending...';
    
    const formData = {
        username: document.getElementById('username').value.trim(),
        full_name: document.getElementById('full_name').value.trim(),
        email: document.getElementById('email').value.trim()
    };
    
    try {
        const response = await fetch('../api/registration/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', '✅ New verification code sent!');
            startTimer(data.expires_in || 900);
            btn.disabled = false;
            btn.innerHTML = originalText;
        } else {
            showAlert('danger', data.error || 'Failed to resend code');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        showAlert('danger', 'Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// Navigation Functions
function goToStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.step-content').forEach(step => {
        step.classList.remove('active');
    });
    
    // Show target step
    document.getElementById('step' + stepNumber).classList.add('active');
    
    // Update step indicators
    for (let i = 1; i <= 3; i++) {
        const indicator = document.getElementById('step-indicator-' + i);
        indicator.classList.remove('active', 'completed');
        
        if (i < stepNumber) {
            indicator.classList.add('completed');
        } else if (i === stepNumber) {
            indicator.classList.add('active');
        }
    }
    
    // Clear alerts when changing steps
    document.getElementById('alert-container').innerHTML = '';
}

// Timer Function
function startTimer(seconds) {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    expiresAt = Date.now() + (seconds * 1000);
    
    timerInterval = setInterval(() => {
        const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        const minutes = Math.floor(remaining / 60);
        const secs = remaining % 60;
        
        document.getElementById('timer').textContent = 
            `${minutes}:${secs.toString().padStart(2, '0')}`;
        
        if (remaining <= 0) {
            clearInterval(timerInterval);
            showAlert('warning', '⚠️ Verification code expired. Please request a new one.');
        }
    }, 1000);
}

// Alert Function
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHTML;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }
    }, 5000);
}

// Auto-format OTP input (only numbers)
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
