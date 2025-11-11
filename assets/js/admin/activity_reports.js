// Activity Reports JavaScript Functions

function generateReport() {
    const filterType = document.getElementById('filter_type').value;
    const filterDate = document.getElementById('filter_date').value;
    const filterUser = document.getElementById('filter_user').value;
    
    // Build URL with parameters
    let url = '../generate_report.php?type=activity';
    
    if (filterType) {
        url += '&filter_type=' + encodeURIComponent(filterType);
    }
    if (filterDate) {
        url += '&filter_date=' + encodeURIComponent(filterDate);
    }
    if (filterUser) {
        url += '&filter_user=' + encodeURIComponent(filterUser);
    }
    
    // Open in new tab
    window.open(url, '_blank');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Activity reports JavaScript loaded');
});
