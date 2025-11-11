/**
 * Feedback Form JavaScript
 * Handles dynamic background color changes based on user type selection
 */

document.addEventListener('DOMContentLoaded', function() {
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
    const body = document.body;
    
    // Function to change background based on user type
    function changeBackground(userType) {
        // Remove existing theme classes
        body.classList.remove('student-theme', 'teacher-theme');
        
        // Add appropriate theme class
        if (userType === 'student') {
            body.classList.add('student-theme');
        } else if (userType === 'teacher') {
            body.classList.add('teacher-theme');
        }
    }
    
    // Add event listeners to radio buttons
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                changeBackground(this.value);
            }
        });
    });
    
    // Set initial background based on checked radio button
    const checkedRadio = document.querySelector('input[name="user_type"]:checked');
    if (checkedRadio) {
        changeBackground(checkedRadio.value);
    }
});
