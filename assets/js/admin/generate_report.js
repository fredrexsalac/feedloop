// Generate Report JavaScript Functions

// Auto-print when the page loads
window.onload = function() {
    // Small delay to ensure everything is loaded
    setTimeout(function() {
        window.print();
    }, 500);
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Generate report JavaScript loaded');
});
