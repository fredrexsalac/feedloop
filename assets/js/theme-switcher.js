document.addEventListener('DOMContentLoaded', function() {
    // Get theme from localStorage or default to light
    const currentTheme = localStorage.getItem('feedloop-theme') || 'light';
    
    // Apply the saved theme on page load
    applyTheme(currentTheme);
    
    // Set the correct radio button as checked
    const themeRadio = document.querySelector(`input[name="themeOption"][value="${currentTheme}"]`);
    if (themeRadio) {
        themeRadio.checked = true;
    }
    
    // Add event listeners to theme options
    const themeOptions = document.querySelectorAll('.theme-option');
    themeOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.checked) {
                const selectedTheme = this.value;
                localStorage.setItem('feedloop-theme', selectedTheme);
                applyTheme(selectedTheme);
            }
        });
    });
});

function applyTheme(theme) {
    const body = document.body;
    
    // Remove all theme classes
    body.classList.remove('theme-light', 'theme-dark', 'theme-animated');
    
    // Apply selected theme
    switch (theme) {
        case 'dark':
            body.classList.add('theme-dark');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            break;
        case 'animated':
            body.classList.add('theme-animated');
            startAnimatedTheme();
            document.documentElement.setAttribute('data-bs-theme', 'light');
            break;
        default: // light
            body.classList.add('theme-light');
            document.documentElement.setAttribute('data-bs-theme', 'light');
            stopAnimatedTheme();
            break;
    }
}

let animationInterval;

function startAnimatedTheme() {
    stopAnimatedTheme(); // Clear any existing interval
    
    // Create an array of colors for animation
    const colors = [
        { primary: '#007bff', secondary: '#6610f2', bg: 'rgba(0, 123, 255, 0.1)' }, // Blue/Indigo
        { primary: '#6f42c1', secondary: '#e83e8c', bg: 'rgba(111, 66, 193, 0.1)' }, // Purple/Pink
        { primary: '#dc3545', secondary: '#fd7e14', bg: 'rgba(220, 53, 69, 0.1)' }, // Red/Orange
        { primary: '#28a745', secondary: '#20c997', bg: 'rgba(40, 167, 69, 0.1)' }  // Green/Teal
    ];
    
    let colorIndex = 0;
    
    // Apply initial color immediately
    applyAnimatedColor(colors[colorIndex]);
    
    // Update colors every 5 seconds
    animationInterval = setInterval(() => {
        colorIndex = (colorIndex + 1) % colors.length;
        applyAnimatedColor(colors[colorIndex]);
    }, 5000);
}

function stopAnimatedTheme() {
    if (animationInterval) {
        clearInterval(animationInterval);
        // Reset to default Bootstrap colors
        document.documentElement.style.removeProperty('--bs-primary');
        document.documentElement.style.removeProperty('--bs-link-color');
        document.documentElement.style.removeProperty('--bs-link-hover-color');
        document.body.style.backgroundColor = '';
    }
}

function applyAnimatedColor(colors) {
    document.documentElement.style.setProperty('--bs-primary', colors.primary);
    document.documentElement.style.setProperty('--bs-link-color', colors.primary);
    document.documentElement.style.setProperty('--bs-link-hover-color', colors.secondary);
    document.body.style.backgroundColor = colors.bg;
}