// Mobile Navigation JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeMobileNavigation();
});

function initializeMobileNavigation() {
    // Create mobile toggle button
    createMobileToggle();
    
    // Create mobile overlay
    createMobileOverlay();
    
    // Add event listeners
    setupMobileEventListeners();
}

function createMobileToggle() {
    // Check if toggle already exists
    if (document.querySelector('.mobile-toggle')) return;
    
    const toggleButton = document.createElement('button');
    toggleButton.className = 'mobile-toggle';
    toggleButton.innerHTML = '☰';
    toggleButton.setAttribute('aria-label', 'Toggle Navigation');
    
    document.body.appendChild(toggleButton);
}

function createMobileOverlay() {
    // Check if overlay already exists
    if (document.querySelector('.mobile-overlay')) return;
    
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    
    document.body.appendChild(overlay);
}

function setupMobileEventListeners() {
    const toggleButton = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (!toggleButton || !sidebar || !overlay) return;
    
    // Toggle sidebar on button click
    toggleButton.addEventListener('click', function() {
        toggleSidebar();
    });
    
    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        closeSidebar();
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
    // Close sidebar when clicking sidebar links (for better UX)
    const sidebarLinks = sidebar.querySelectorAll('.sidebar-menu a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Small delay to allow navigation to complete
            setTimeout(() => {
                closeSidebar();
            }, 200);
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        
        // Prevent body scroll when sidebar is open
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function openSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.add('active');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}
