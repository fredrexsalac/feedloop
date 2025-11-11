// Admin Login JavaScript Functions

// Check if coming from logout (green background transition)
window.addEventListener('load', function() {
    // Check if there's a logout flag in sessionStorage or URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from') === 'logout' || sessionStorage.getItem('fromLogout')) {
        // Start with green background immediately
        document.body.classList.add('green-entrance');
        
        // Transition back to normal background after showing green
        setTimeout(() => {
            document.body.classList.remove('green-entrance');
        }, 2500);
        
        // Clear the flag
        sessionStorage.removeItem('fromLogout');
    }
});

// Login success modal and redirect
function showLoginSuccess(position) {
    // Show success modal
    document.getElementById('successModal').style.display = 'flex';
    
    // Set welcome message based on position
    const welcomeMessage = document.getElementById('welcomeMessage');
    welcomeMessage.textContent = `Welcome to the ${position} Account!`;
    
    // Countdown and redirect
    let countdown = 3;
    const countdownElement = document.getElementById('countdown');
    
    function updateCountdown() {
        countdownElement.textContent = countdown;
        if (countdown > 0) {
            countdown--;
            setTimeout(updateCountdown, 1000);
        } else {
            // Redirect based on position
            if (position === 'Super Admin') {
                window.location.href = '../admin/super_admin/super_admin_dashboard.php';
            } else {
                window.location.href = '../admin/dashboard.php';
            }
        }
    }
    
    updateCountdown();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin login JavaScript loaded');
});
