/**
 * Public Form Interactions JavaScript
 * Handles form interactions for public form submission interface
 * Extracted from inline scripts in PHP files
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeFormInteractions();
});

function initializeFormInteractions() {
    initializeStarRatings();
    initializeFormValidation();
}

// Star rating functionality
function initializeStarRatings() {
    document.querySelectorAll('.rating-stars').forEach(function(ratingContainer) {
        const stars = ratingContainer.querySelectorAll('.star');
        const hiddenInput = document.querySelector(`input[name="${ratingContainer.dataset.field}"]`);
        
        if (!hiddenInput) return;
        
        stars.forEach(function(star, index) {
            star.addEventListener('click', function() {
                const value = parseInt(star.dataset.value);
                hiddenInput.value = value;
                
                // Update star display
                stars.forEach(function(s, i) {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const value = parseInt(star.dataset.value);
                stars.forEach(function(s, i) {
                    if (i < value) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        ratingContainer.addEventListener('mouseleave', function() {
            const currentValue = parseInt(hiddenInput.value) || 0;
            stars.forEach(function(s, i) {
                if (i < currentValue) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
}

// Slider value update
function updateSliderValue(slider) {
    const valueDisplay = document.getElementById(slider.name + '_value');
    if (valueDisplay) {
        valueDisplay.textContent = slider.value;
    }
}

// Form validation
function initializeFormValidation() {
    const feedbackForm = document.getElementById('feedback-form');
    if (!feedbackForm) return;
    
    feedbackForm.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(function(field) {
            if (!field.value || (field.type === 'checkbox' && !field.checked)) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
}
