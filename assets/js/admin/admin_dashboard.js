/**
 * Admin Dashboard JavaScript
 * Handles theme initialization and settings form submission
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme if settings.js is loaded
    if (typeof window.initializeTheme === 'function') {
        window.initializeTheme();
    }
});

// Regular Admin specific settings form handler
function initializeRegularAdminSettings() {
    setTimeout(function() {
        const generalForm = document.getElementById('generalSettingsForm');
        if (generalForm && !generalForm.hasAttribute('data-admin-initialized')) {
            console.log('Regular Admin: Attaching form handler');
            generalForm.setAttribute('data-admin-initialized', 'true');
            
            generalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('Regular Admin: Form submitted');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                if (submitBtn.disabled) return;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
                
                try {
                    const formData = {
                        type: 'general',
                        admin_email: document.getElementById('adminEmail').value,
                        session_timeout: document.getElementById('sessionTimeout').value,
                        theme_mode: document.getElementById('themeMode').value
                    };
                    
                    const response = await fetch('/admin/api/save_settings.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Settings saved successfully!');
                        
                        // Apply theme manually for regular admin with full theme functions
                        const theme = formData.theme_mode;
                        const body = document.body;
                        const html = document.documentElement;
                        
                        console.log('Regular Admin: Applying theme:', theme);
                        
                        // Remove existing theme classes
                        body.classList.remove('theme-light', 'theme-dark', 'theme-animated', 'theme-auto');
                        html.classList.remove('theme-light', 'theme-dark', 'theme-animated', 'theme-auto');
                        
                        // Apply new theme
                        body.classList.add(`theme-${theme}`);
                        html.classList.add(`theme-${theme}`);
                        
                        // Apply theme-specific styles like Super Admin does
                        switch(theme) {
                            case 'dark':
                                // Apply dark theme styles
                                body.style.backgroundColor = '#1a1a1a';
                                body.style.color = '#ffffff';
                                break;
                            case 'animated':
                                // Apply animated theme styles
                                body.style.background = 'linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab)';
                                body.style.backgroundSize = '400% 400%';
                                body.style.animation = 'gradient 15s ease infinite';
                                body.style.color = '#ffffff';
                                break;
                            case 'auto':
                                // Apply auto theme (system preference)
                                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                                    body.style.backgroundColor = '#1a1a1a';
                                    body.style.color = '#ffffff';
                                } else {
                                    body.style.backgroundColor = '';
                                    body.style.color = '';
                                }
                                break;
                            default: // light
                                body.style.backgroundColor = '';
                                body.style.color = '';
                                body.style.background = '';
                                body.style.animation = '';
                        }
                        
                        // Store theme preference
                        localStorage.setItem('admin_theme', theme);
                        
                        console.log('Regular Admin: Theme applied successfully');
                    } else {
                        alert('Error: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Regular Admin: Error:', error);
                    alert('Error saving settings: ' + error.message);
                } finally {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        }
    }, 1000);
}

// Call this when settings content is loaded
document.addEventListener('click', function(e) {
    if (e.target.getAttribute('onclick') && e.target.getAttribute('onclick').includes('settings')) {
        setTimeout(initializeRegularAdminSettings, 500);
    }
});
