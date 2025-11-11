// Logout JavaScript Functions

// Countdown and logout process
let countdown = 3;
const countdownElement = document.getElementById('countdown');
const modal = document.getElementById('logoutModal');

const timer = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    
    // Change background to black when countdown reaches 1
    if (countdown === 1) {
        document.body.classList.add('background-black');
    }
    
    if (countdown <= 0) {
        clearInterval(timer);
        
        // Add fade out animation
        modal.classList.add('fade-out');
        
        // Perform logout after animation
        setTimeout(() => {
            fetch('logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'confirm_logout=true'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Change background to green before redirect
                    document.body.classList.remove('background-black');
                    document.body.classList.add('background-green');
                    
                    // Set flag for login page to show green entrance
                    sessionStorage.setItem('fromLogout', 'true');
                    
                    // Redirect after green background transition completes
                    setTimeout(() => {
                        window.location.href = data.redirect + '?from=logout';
                    }, 2000);
                }
            })
            .catch(error => {
                // Fallback redirect if AJAX fails
                document.body.classList.remove('background-black');
                document.body.classList.add('background-green');
                sessionStorage.setItem('fromLogout', 'true');
                setTimeout(() => {
                    window.location.href = '../login/unified_login.php?from=logout';
                }, 2000);
            });
        }, 500);
    }
}, 1000);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Logout JavaScript loaded');
});
