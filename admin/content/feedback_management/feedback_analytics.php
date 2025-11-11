<?php
// Enhanced Feedback Analytics Dashboard
?>

<div class="dashboard-header">
    <h1><i class="fas fa-chart-line me-2"></i>Feedback Analytics</h1>
    <p>Comprehensive analytics and insights for feedback management</p>
</div>

<!-- Loading State -->
<div id="analytics-loading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading analytics...</span>
    </div>
    <p class="mt-3 text-muted">Generating comprehensive analytics...</p>
</div>

<!-- Analytics Content -->
<div id="analytics-content" style="display: none;">
    
    <!-- Key Metrics Row -->
    <div class="row mb-4" id="key-metrics">
        <div class="col-md-3">
            <div class="stat-card bg-primary text-white">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-number" id="total-feedback">0</div>
                <div class="stat-label">Total Feedback</div>
                <div class="stat-change" id="feedback-change">+0% this month</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="stat-number" id="response-rate">0%</div>
                <div class="stat-label">Response Rate</div>
                <div class="stat-change" id="response-change">Overall</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning text-white">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="avg-response-time">0h</div>
                <div class="stat-label">Avg Response Time</div>
                <div class="stat-change" id="response-time-change">Hours</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info text-white">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="active-users">0</div>
                <div class="stat-label">Active Users</div>
                <div class="stat-change" id="users-change">This month</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-chart-area me-2"></i>Feedback Trends</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="switchTrendView('monthly')">Monthly</button>
                        <button type="button" class="btn btn-outline-primary" onclick="switchTrendView('daily')">Daily</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-pie-chart me-2"></i>Feedback Categories</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detailed Analytics Row -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Urgent Feedback</h5>
                </div>
                <div class="card-body">
                    <div id="urgent-feedback-list">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tags me-2"></i>Top Keywords</h5>
                </div>
                <div class="card-body">
                    <div id="keywords-cloud">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tachometer-alt me-2"></i>Performance Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="metric-box">
                                <div class="metric-value" id="quality-score">0</div>
                                <div class="metric-label">Quality Score</div>
                                <div class="metric-description">Based on message length and detail</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-box">
                                <div class="metric-value" id="satisfaction-rate">0%</div>
                                <div class="metric-label">Satisfaction Rate</div>
                                <div class="metric-description">Estimated user satisfaction</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-box">
                                <div class="metric-value" id="efficiency-score">0%</div>
                                <div class="metric-label">Efficiency Score</div>
                                <div class="metric-description">Response time efficiency</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user-tie me-2"></i>Admin Performance</h5>
                </div>
                <div class="card-body">
                    <div id="admin-performance-list">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export and Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-download me-2"></i>Export & Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" onclick="exportAnalytics('pdf')">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF Report
                        </button>
                        <button class="btn btn-success" onclick="exportAnalytics('excel')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button class="btn btn-info" onclick="refreshAnalytics()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh Data
                        </button>
                        <button class="btn btn-warning" onclick="scheduleReport()">
                            <i class="fas fa-calendar me-1"></i>Schedule Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, rgba(255,255,255,0.3), rgba(255,255,255,0.7), rgba(255,255,255,0.3));
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 1rem;
    margin-bottom: 5px;
    opacity: 0.9;
}

.stat-change {
    font-size: 0.85rem;
    opacity: 0.8;
}

.metric-box {
    text-align: center;
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.metric-box:hover {
    border-color: #0d6efd;
    background-color: #f8f9ff;
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: #0d6efd;
    margin-bottom: 5px;
}

.metric-label {
    font-weight: 600;
    margin-bottom: 5px;
}

.metric-description {
    font-size: 0.85rem;
    color: #6c757d;
}

.urgent-item {
    padding: 10px;
    border-left: 4px solid #dc3545;
    background-color: #fff5f5;
    margin-bottom: 10px;
    border-radius: 5px;
}

.keyword-tag {
    display: inline-block;
    padding: 5px 12px;
    margin: 3px;
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    color: white;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.admin-performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.admin-performance-item:last-child {
    border-bottom: none;
}

.performance-score {
    font-weight: bold;
    color: #198754;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let analyticsData = null;
let trendsChart = null;
let categoryChart = null;

// Load analytics on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAnalytics();
});

async function loadAnalytics() {
    try {
        document.getElementById('analytics-loading').style.display = 'block';
        document.getElementById('analytics-content').style.display = 'none';
        
        const response = await fetch('../api/get_feedback_analytics.php');
        const result = await response.json();
        
        if (result.success) {
            analyticsData = result.analytics;
            displayAnalytics();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        document.getElementById('analytics-loading').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading analytics: ${error.message}
            </div>`;
    }
}

function displayAnalytics() {
    // Hide loading and show content
    document.getElementById('analytics-loading').style.display = 'none';
    document.getElementById('analytics-content').style.display = 'block';
    
    // Update key metrics
    document.getElementById('total-feedback').textContent = analyticsData.total_feedback || 0;
    document.getElementById('response-rate').textContent = (analyticsData.response_rate?.response_rate || 0) + '%';
    document.getElementById('avg-response-time').textContent = Math.round(analyticsData.response_time?.avg_response_hours || 0) + 'h';
    document.getElementById('active-users').textContent = analyticsData.user_activity?.active_users || 0;
    
    // Create charts
    createTrendsChart();
    createCategoryChart();
    
    // Display urgent feedback
    displayUrgentFeedback();
    
    // Display keywords
    displayKeywords();
    
    // Display performance metrics
    displayPerformanceMetrics();
    
    // Display admin performance
    displayAdminPerformance();
}

function createTrendsChart() {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    
    if (trendsChart) {
        trendsChart.destroy();
    }
    
    const monthlyData = analyticsData.monthly_trends || [];
    const labels = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    }).reverse();
    
    const submissionsData = monthlyData.map(item => item.count).reverse();
    const responsesData = monthlyData.map(item => item.responded).reverse();
    
    trendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Feedback Submissions',
                data: submissionsData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Admin Responses',
                data: responsesData,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    if (categoryChart) {
        categoryChart.destroy();
    }
    
    const categoryData = analyticsData.by_category || [];
    const labels = categoryData.map(item => item.feedback_category);
    const data = categoryData.map(item => item.count);
    const colors = [
        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
    ];
    
    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

function displayUrgentFeedback() {
    const urgentList = document.getElementById('urgent-feedback-list');
    const pendingFeedback = analyticsData.pending_feedback || [];
    
    if (pendingFeedback.length === 0) {
        urgentList.innerHTML = '<p class="text-muted text-center">No urgent feedback pending</p>';
        return;
    }
    
    const urgentItems = pendingFeedback
        .filter(item => item.hours_pending > 24)
        .slice(0, 5);
    
    if (urgentItems.length === 0) {
        urgentList.innerHTML = '<p class="text-success text-center"><i class="fas fa-check-circle me-1"></i>All feedback is being handled promptly</p>';
        return;
    }
    
    urgentList.innerHTML = urgentItems.map(item => `
        <div class="urgent-item">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${item.subject}</strong>
                    <br><small class="text-muted">${item.feedback_category}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-danger">${item.hours_pending}h pending</span>
                </div>
            </div>
        </div>
    `).join('');
}

function displayKeywords() {
    const keywordsContainer = document.getElementById('keywords-cloud');
    const keywords = analyticsData.top_keywords || {};
    
    if (Object.keys(keywords).length === 0) {
        keywordsContainer.innerHTML = '<p class="text-muted text-center">No keywords data available</p>';
        return;
    }
    
    const maxCount = Math.max(...Object.values(keywords));
    
    keywordsContainer.innerHTML = Object.entries(keywords)
        .slice(0, 15)
        .map(([word, count]) => {
            const size = Math.max(0.8, (count / maxCount) * 1.5);
            return `<span class="keyword-tag" style="font-size: ${size}rem">${word} (${count})</span>`;
        }).join('');
}

function displayPerformanceMetrics() {
    const qualityMetrics = analyticsData.quality_metrics || {};
    const avgLength = qualityMetrics.avg_message_length || 0;
    const qualityScore = Math.min(100, Math.round((avgLength / 200) * 100));
    
    document.getElementById('quality-score').textContent = qualityScore;
    
    const responseRate = analyticsData.response_rate?.response_rate || 0;
    document.getElementById('satisfaction-rate').textContent = Math.round(responseRate) + '%';
    
    const avgResponseTime = analyticsData.response_time?.avg_response_hours || 0;
    const efficiencyScore = Math.max(0, Math.min(100, 100 - (avgResponseTime / 24) * 50));
    document.getElementById('efficiency-score').textContent = Math.round(efficiencyScore) + '%';
}

function displayAdminPerformance() {
    const performanceList = document.getElementById('admin-performance-list');
    const adminPerformance = analyticsData.admin_performance || [];
    
    if (adminPerformance.length === 0) {
        performanceList.innerHTML = '<p class="text-muted text-center">No admin performance data available</p>';
        return;
    }
    
    performanceList.innerHTML = adminPerformance.map(admin => `
        <div class="admin-performance-item">
            <div>
                <strong>${admin.username}</strong>
                <br><small class="text-muted">${admin.position}</small>
            </div>
            <div class="text-end">
                <div class="performance-score">${admin.responses_given || 0} responses</div>
                <small class="text-muted">${Math.round(admin.avg_response_time || 0)}h avg</small>
            </div>
        </div>
    `).join('');
}

function switchTrendView(view) {
    // Update button states
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update chart based on view
    if (view === 'daily') {
        updateTrendsChartDaily();
    } else {
        createTrendsChart(); // Monthly view
    }
}

function updateTrendsChartDaily() {
    const dailyData = analyticsData.daily_activity || [];
    const labels = dailyData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }).reverse();
    
    const submissionsData = dailyData.map(item => item.submissions).reverse();
    const responsesData = dailyData.map(item => item.responses).reverse();
    
    trendsChart.data.labels = labels;
    trendsChart.data.datasets[0].data = submissionsData;
    trendsChart.data.datasets[1].data = responsesData;
    trendsChart.update();
}

function refreshAnalytics() {
    loadAnalytics();
}

function exportAnalytics(format) {
    alert(`Export to ${format.toUpperCase()} functionality will be implemented in the next update.`);
}

function scheduleReport() {
    alert('Report scheduling functionality will be implemented in the next update.');
}
</script>
