// Analytics Dashboard Chart Scripts

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFeedbackTrendsChart();
    initializeFeedbackCategoryChart();
});

// Feedback Trends Bar Chart
function initializeFeedbackTrendsChart() {
    const ctx = document.getElementById('feedbackTrendsChart');
    if (!ctx) return;
    
    const feedbackTrendsCtx = ctx.getContext('2d');
    
    // Get data from PHP (passed via data attributes or global variables)
    const labels = window.feedbackTrendsLabels || [];
    const data = window.feedbackTrendsData || [];
    
    const feedbackTrendsData = {
        labels: labels,
        datasets: [{
            label: 'Feedback Submissions',
            data: data,
            backgroundColor: 'rgba(255, 152, 0, 0.8)',
            borderColor: 'rgba(255, 152, 0, 1)',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false,
        }]
    };

    new Chart(feedbackTrendsCtx, {
        type: 'bar',
        data: feedbackTrendsData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Daily Feedback Submissions',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#666'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#666'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

// Feedback Category Pie Chart
function initializeFeedbackCategoryChart() {
    const ctx = document.getElementById('feedbackCategoryChart');
    if (!ctx) return;
    
    const feedbackCategoryCtx = ctx.getContext('2d');
    
    // Get data from PHP (passed via data attributes or global variables)
    const labels = window.feedbackCategoryLabels || [];
    const data = window.feedbackCategoryData || [];
    
    const categoryData = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: [
                '#FF6384',
                '#36A2EB', 
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384',
                '#36A2EB'
            ],
            borderWidth: 2,
            borderColor: '#fff',
            hoverBorderWidth: 3,
            hoverOffset: 10
        }]
    };

    new Chart(feedbackCategoryCtx, {
        type: 'pie',
        data: categoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Feedback Distribution by Category',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#333'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutBounce'
            }
        }
    });
}

// Utility function to update chart data dynamically
function updateChartData(chartId, newLabels, newData) {
    const chart = Chart.getChart(chartId);
    if (chart) {
        chart.data.labels = newLabels;
        chart.data.datasets[0].data = newData;
        chart.update();
    }
}
