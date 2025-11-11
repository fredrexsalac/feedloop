/**
 * User Portal JavaScript
 * Handles interactions for the user portal displaying admin announcements
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeUserPortal();
});

/**
 * Initialize user portal functionality
 */
function initializeUserPortal() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.announcement-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
    
    // Initialize tooltips if any
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Filter announcements by type
 */
function filterAnnouncements() {
    const filterValue = document.getElementById('filterType').value;
    const cards = document.querySelectorAll('.announcement-card');
    
    cards.forEach(card => {
        const cardType = card.closest('[data-type]').getAttribute('data-type');
        
        if (filterValue === 'all' || cardType === filterValue) {
            card.closest('.col-lg-6').style.display = 'block';
            setTimeout(() => {
                card.closest('.col-lg-6').classList.add('fade-in');
            }, 100);
        } else {
            card.closest('.col-lg-6').style.display = 'none';
            card.closest('.col-lg-6').classList.remove('fade-in');
        }
    });
    
    // Check if any cards are visible
    const visibleCards = document.querySelectorAll('.col-lg-6[style*="block"], .col-lg-6:not([style*="none"])');
    const emptyState = document.querySelector('.empty-state');
    const grid = document.getElementById('announcementsGrid');
    
    if (visibleCards.length === 0 && filterValue !== 'all') {
        if (!emptyState) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'empty-state text-center py-5';
            emptyDiv.innerHTML = `
                <i class="fas fa-filter fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">No ${filterValue} announcements found</h3>
                <p class="text-muted">Try selecting a different filter or check back later.</p>
            `;
            grid.parentNode.appendChild(emptyDiv);
        }
    } else {
        const existingEmptyState = grid.parentNode.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.remove();
        }
    }
}

/**
 * View announcement details
 */
async function viewAnnouncement(formId) {
    try {
        showLoadingModal();
        
        const response = await fetch(`../api/get_announcement_details.php?id=${formId}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('announcementModalBody').innerHTML = result.html;
            
            // Show participate button if it's a survey or feedback form
            const participateBtn = document.getElementById('participateBtn');
            if (result.can_participate) {
                participateBtn.style.display = 'inline-block';
                participateBtn.onclick = () => participateInForm(formId);
            } else {
                participateBtn.style.display = 'none';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
            modal.show();
        } else {
            showNotification('error', result.message || 'Failed to load announcement details');
        }
    } catch (error) {
        console.error('Error loading announcement:', error);
        showNotification('error', 'Network error loading announcement details');
    }
}

/**
 * Participate in a form (survey/feedback)
 */
function participateInForm(formId, formCode) {
    // Close the modal first
    const modal = bootstrap.Modal.getInstance(document.getElementById('announcementModal'));
    if (modal) {
        modal.hide();
    }
    
    // Redirect to the form participation page
    if (formCode) {
        window.location.href = `../public/form/index.php?code=${encodeURIComponent(formCode)}`;
    } else {
        window.location.href = `../public/form/index.php?form_id=${formId}`;
    }
}

function handleParticipateClick(button) {
    if (!button) return;
    const formId = parseInt(button.dataset.formId, 10);
    const formCode = button.dataset.formCode || '';
    participateInForm(formId, formCode);
}

/**
 * Show loading modal
 */
function showLoadingModal() {
    document.getElementById('announcementModalBody').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading announcement details...</p>
        </div>
    `;
}

/**
 * Show notification
 */
function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Search announcements (if search functionality is added later)
 */
function searchAnnouncements(query) {
    const cards = document.querySelectorAll('.announcement-card');
    const searchTerm = query.toLowerCase();
    
    cards.forEach(card => {
        const title = card.querySelector('.announcement-title').textContent.toLowerCase();
        const description = card.querySelector('.announcement-description')?.textContent.toLowerCase() || '';
        const adminName = card.querySelector('.admin-info').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm) || adminName.includes(searchTerm)) {
            card.closest('.col-lg-6').style.display = 'block';
        } else {
            card.closest('.col-lg-6').style.display = 'none';
        }
    });
}

/**
 * Dismiss announcement
 */
async function dismissAnnouncement(formId) {
    if (!confirm('Are you sure you want to dismiss this announcement? You won\'t see it again.')) {
        return;
    }
    
    try {
        const response = await fetch('../api/dismiss_announcement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ form_id: formId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove the announcement card with animation
            const announcementCard = document.getElementById(`announcement-${formId}`);
            if (announcementCard) {
                announcementCard.style.opacity = '0';
                announcementCard.style.transform = 'scale(0.9)';
                announcementCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    announcementCard.remove();
                    
                    // Check if there are any announcements left
                    const remainingCards = document.querySelectorAll('.announcement-card');
                    if (remainingCards.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 300);
            }
            
            showNotification('success', 'Announcement dismissed successfully');
        } else {
            showNotification('error', result.message || 'Failed to dismiss announcement');
        }
    } catch (error) {
        console.error('Error dismissing announcement:', error);
        showNotification('error', 'Network error. Please try again.');
    }
}

/**
 * Refresh announcements
 */
function refreshAnnouncements() {
    window.location.reload();
}

/**
 * Handle responsive navigation
 */
function handleResponsiveNav() {
    const navbar = document.querySelector('.navbar');
    const navbarToggler = document.querySelector('.navbar-toggler');
    
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            setTimeout(() => {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    navbar.classList.add('navbar-expanded');
                } else {
                    navbar.classList.remove('navbar-expanded');
                }
            }, 100);
        });
    }
}

// Initialize responsive navigation
handleResponsiveNav();

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Handle window resize for responsive adjustments
window.addEventListener('resize', function() {
    // Adjust card heights if needed
    const cards = document.querySelectorAll('.announcement-card');
    cards.forEach(card => {
        card.style.height = 'auto';
    });
});

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    // ESC key to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) {
                modal.hide();
            }
        }
    }
    
    // Ctrl+F to focus on filter (if search is added later)
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        const filterSelect = document.getElementById('filterType');
        if (filterSelect) {
            filterSelect.focus();
        }
    }
});
