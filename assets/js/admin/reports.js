// Reports JavaScript Functions

function generateReport(type) {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    // Open report generation in new tab
    const url = `../generate_report.php?type=${type}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}

function applyDateRange() {
    // You can add any client-side filtering logic here
    // For now, we'll just show a toast notification
    const toast = new bootstrap.Toast(document.getElementById('filterToast'));
    toast.show();
}

function exportToCSV() {
    // Simple CSV export functionality
    const table = document.querySelector('.table');
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download CSV
    const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "report.csv");
    document.body.appendChild(link);
    link.click();
}

// Initialize tooltips and other Bootstrap components when content is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    
    if (startDateEl) startDateEl.value = today;
    if (endDateEl) endDateEl.value = today;
    
    // Initialize any tooltips or popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    console.log('Reports JavaScript loaded');
});
