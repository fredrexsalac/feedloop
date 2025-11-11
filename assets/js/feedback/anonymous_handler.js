/**
 * Anonymous Checkbox Handler
 * Handles anonymous checkbox functionality for feedback forms
 * Extracted from inline scripts in feedback/index.php
 */

// Handle anonymous checkbox
document.addEventListener('DOMContentLoaded', function() {
    const anonymousCheckbox = document.getElementById('anonymous');
    const nameInput = document.getElementById('name');
    
    if (!anonymousCheckbox || !nameInput) return;
    
    anonymousCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Save the current name value
            nameInput.dataset.originalValue = nameInput.value;
            // Disable and update placeholder
            nameInput.disabled = true;
            nameInput.placeholder = "Will be submitted as anonymous";
        } else {
            // Restore original value
            nameInput.disabled = false;
            nameInput.value = nameInput.dataset.originalValue || '';
            nameInput.placeholder = "Enter your full name";
        }
    });
});
