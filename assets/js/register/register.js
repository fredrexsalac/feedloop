// Registration JavaScript Functions

// Student registration redirect
function redirectToLogin() {
    setTimeout(function() {
        window.location.href = '../login/unified_login.php';
    }, 3000);
}

// Admin registration redirect  
function redirectToAdminLogin() {
    setTimeout(function() {
        window.location.href = '../login/unified_login.php';
    }, 3000);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Registration JavaScript loaded');
});
