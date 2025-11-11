<?php
// Advanced Feedback Search Interface
?>

<div class="dashboard-header">
    <h1><i class="fas fa-search me-2"></i>Advanced Feedback Search</h1>
    <p>Search and filter feedback with advanced criteria and sorting options</p>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter me-2"></i>Search Filters</h5>
    </div>
    <div class="card-body">
        <form id="advanced-search-form">
            <div class="row">
                <div class="col-md-4">
                    <label for="search-query" class="form-label">Search Text</label>
                    <input type="text" class="form-control" id="search-query" name="q" 
                           placeholder="Search in subject and message...">
                </div>
                <div class="col-md-4">
                    <label for="category-filter" class="form-label">Category</label>
                    <select class="form-select" id="category-filter" name="category">
                        <option value="all">All Categories</option>
                        <option value="Department Feedback">Department Feedback</option>
                        <option value="Instructor Feedback">Instructor Feedback</option>
                        <option value="Event Feedback">Event Feedback</option>
                        <option value="Dean/Office Feedback">Dean/Office Feedback</option>
                        <option value="System Feedback">System Feedback</option>
                        <option value="Community-Based Issues">Community-Based Issues</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status-filter" class="form-label">Response Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="all">All Status</option>
                        <option value="pending">Pending Response</option>
                        <option value="responded">Responded</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-3">
                    <label for="date-from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date-from" name="date_from">
                </div>
                <div class="col-md-3">
                    <label for="date-to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date-to" name="date_to">
                </div>
                <div class="col-md-3">
                    <label for="sort-by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort-by" name="sort_by">
                        <option value="created_at">Date Created</option>
                        <option value="subject">Subject</option>
                        <option value="feedback_category">Category</option>
                        <option value="admin_response_date">Response Date</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort-order" class="form-label">Sort Order</label>
                    <select class="form-select" id="sort-order" name="sort_order">
                        <option value="DESC">Newest First</option>
                        <option value="ASC">Oldest First</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                    <button type="button" class="btn btn-info" onclick="saveSearch()">
                        <i class="fas fa-bookmark me-1"></i>Save Search
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportResults()">
                        <i class="fas fa-download me-1"></i>Export Results
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Search Results</h5>
        <div id="results-summary" class="text-muted">
            <!-- Populated by JavaScript -->
        </div>
    </div>
    <div class="card-body">
        <!-- Loading State -->
        <div id="search-loading" class="text-center py-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
            <p class="mt-2 text-muted">Searching feedback...</p>
        </div>
        
        <!-- Results Table -->
        <div id="search-results" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Feedback Details</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Search results pagination">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- Populated by JavaScript -->
                </ul>
            </nav>
        </div>
        
        <!-- No Results -->
        <div id="no-results" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No feedback found</h5>
            <p class="text-muted">Try adjusting your search criteria or filters.</p>
        </div>
        
        <!-- Initial State -->
        <div id="initial-state" class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Advanced Feedback Search</h5>
            <p class="text-muted">Use the filters above to search through feedback submissions with advanced criteria.</p>
        </div>
    </div>
</div>

<!-- Search Breakdown -->
<div class="row mt-4" id="search-breakdown" style="display: none;">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-pie me-2"></i>Category Breakdown</h6>
            </div>
            <div class="card-body">
                <div id="category-breakdown">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar me-2"></i>Status Breakdown</h6>
            </div>
            <div class="card-body">
                <div id="status-breakdown">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.priority-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.priority-high {
    background-color: #dc3545;
    color: white;
}

.priority-medium {
    background-color: #ffc107;
    color: #000;
}

.priority-low {
    background-color: #6c757d;
    color: white;
}

.urgency-indicator {
    width: 4px;
    height: 100%;
    position: absolute;
    left: 0;
    top: 0;
}

.urgency-high { background-color: #dc3545; }
.urgency-medium { background-color: #ffc107; }
.urgency-low { background-color: #198754; }

.feedback-row {
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.feedback-row:hover {
    background-color: #f8f9fa;
}

.breakdown-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-bar {
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    flex-grow: 1;
    margin: 0 10px;
}

.breakdown-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd, #0dcaf0);
    transition: width 0.3s ease;
}
</style>

<script>
let currentPage = 1;
let currentSearchParams = {};

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('advanced-search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('date-to').value = today.toISOString().split('T')[0];
    document.getElementById('date-from').value = thirtyDaysAgo.toISOString().split('T')[0];
});

async function performSearch(page = 1) {
    try {
        // Show loading state
        document.getElementById('search-loading').style.display = 'block';
        document.getElementById('search-results').style.display = 'none';
        document.getElementById('no-results').style.display = 'none';
        document.getElementById('initial-state').style.display = 'none';
        document.getElementById('search-breakdown').style.display = 'none';
        
        // Get form data
        const formData = new FormData(document.getElementById('advanced-search-form'));
        const params = new URLSearchParams(formData);
        params.set('page', page);
        params.set('limit', 20);
        
        currentSearchParams = Object.fromEntries(params);
        currentPage = page;
        
        const response = await fetch(`../api/search_feedback.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displaySearchResults(result);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('search-loading').style.display = 'none';
        document.getElementById('no-results').style.display = 'block';
        document.getElementById('no-results').innerHTML = `
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
            <h5 class="text-danger">Search Error</h5>
            <p class="text-muted">${error.message}</p>
        `;
    }
}

function displaySearchResults(result) {
    document.getElementById('search-loading').style.display = 'none';
    
    if (result.data.length === 0) {
        document.getElementById('no-results').style.display = 'block';
        return;
    }
    
    // Show results
    document.getElementById('search-results').style.display = 'block';
    document.getElementById('search-breakdown').style.display = 'block';
    
    // Update results summary
    document.getElementById('results-summary').innerHTML = `
        Showing ${result.data.length} of ${result.pagination.total_results} results
        (Page ${result.pagination.current_page} of ${result.pagination.total_pages})
    `;
    
    // Populate results table
    const tbody = document.getElementById('results-tbody');
    tbody.innerHTML = result.data.map(feedback => `
        <tr class="feedback-row" onclick="viewFeedback(${feedback.id})">
            <td>
                <div class="urgency-indicator urgency-${feedback.urgency}"></div>
                <span class="priority-badge priority-${feedback.priority}">${feedback.priority.toUpperCase()}</span>
                <br><small class="text-muted">Score: ${feedback.priority_score}</small>
            </td>
            <td>
                <strong>${feedback.subject}</strong>
                <br><small class="text-muted">${feedback.message_preview}</small>
                <br><span class="badge bg-light text-dark">${feedback.message_type} message</span>
            </td>
            <td>
                <span class="badge bg-primary">${feedback.feedback_category}</span>
            </td>
            <td>
                ${feedback.created_at_formatted}
                <br><small class="text-muted">${feedback.response_time_hours}h ${feedback.response_status === 'pending' ? 'pending' : 'to respond'}</small>
            </td>
            <td>
                ${feedback.response_status === 'responded' 
                    ? `<span class="badge bg-success">Responded</span><br><small class="text-muted">${feedback.admin_response_date_formatted}</small>`
                    : `<span class="badge bg-warning">Pending</span>`
                }
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="event.stopPropagation(); viewFeedback(${feedback.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${feedback.response_status === 'pending' 
                        ? `<button class="btn btn-outline-success" onclick="event.stopPropagation(); respondToFeedback(${feedback.id})" title="Respond">
                            <i class="fas fa-reply"></i>
                           </button>`
                        : `<button class="btn btn-outline-info" onclick="event.stopPropagation(); viewResponse(${feedback.id})" title="View Response">
                            <i class="fas fa-comment-dots"></i>
                           </button>`
                    }
                </div>
            </td>
        </tr>
    `).join('');
    
    // Update pagination
    updatePagination(result.pagination);
    
    // Update breakdown charts
    updateBreakdown(result.breakdown);
}

function updatePagination(pagination) {
    const paginationEl = document.getElementById('pagination');
    let paginationHTML = '';
    
    // Previous button
    if (pagination.has_prev) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="performSearch(${pagination.current_page - 1})">Previous</a>
        </li>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="performSearch(${i})">${i}</a>
        </li>`;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="performSearch(${pagination.current_page + 1})">Next</a>
        </li>`;
    }
    
    paginationEl.innerHTML = paginationHTML;
}

function updateBreakdown(breakdown) {
    // Category breakdown
    const categoryEl = document.getElementById('category-breakdown');
    const totalCategories = breakdown.categories.reduce((sum, cat) => sum + parseInt(cat.count), 0);
    
    categoryEl.innerHTML = breakdown.categories.map(cat => {
        const percentage = totalCategories > 0 ? (cat.count / totalCategories * 100) : 0;
        return `
            <div class="breakdown-item">
                <span>${cat.feedback_category}</span>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: ${percentage}%"></div>
                </div>
                <span class="fw-bold">${cat.count}</span>
            </div>
        `;
    }).join('');
    
    // Status breakdown
    const statusEl = document.getElementById('status-breakdown');
    const totalStatus = breakdown.status.reduce((sum, stat) => sum + parseInt(stat.count), 0);
    
    statusEl.innerHTML = breakdown.status.map(stat => {
        const percentage = totalStatus > 0 ? (stat.count / totalStatus * 100) : 0;
        const color = stat.status === 'responded' ? '#198754' : '#ffc107';
        return `
            <div class="breakdown-item">
                <span>${stat.status.charAt(0).toUpperCase() + stat.status.slice(1)}</span>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: ${percentage}%; background-color: ${color}"></div>
                </div>
                <span class="fw-bold">${stat.count}</span>
            </div>
        `;
    }).join('');
}

function clearSearch() {
    document.getElementById('advanced-search-form').reset();
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('no-results').style.display = 'none';
    document.getElementById('search-breakdown').style.display = 'none';
    document.getElementById('initial-state').style.display = 'block';
    
    // Reset date range
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('date-to').value = today.toISOString().split('T')[0];
    document.getElementById('date-from').value = thirtyDaysAgo.toISOString().split('T')[0];
}

function saveSearch() {
    const searchName = prompt('Enter a name for this search:');
    if (searchName) {
        const savedSearches = JSON.parse(localStorage.getItem('feedloop_saved_searches') || '[]');
        savedSearches.push({
            name: searchName,
            params: currentSearchParams,
            created: new Date().toISOString()
        });
        localStorage.setItem('feedloop_saved_searches', JSON.stringify(savedSearches));
        alert('Search saved successfully!');
    }
}

function exportResults() {
    alert('Export functionality will be implemented in the next update.');
}

// Feedback interaction functions (reuse from view_feedback_content.php)
async function viewFeedback(id) {
    try {
        const response = await fetch(`../api/get_feedback_details.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            // Create and show modal (you can reuse the modal from view_feedback_content.php)
            showFeedbackModal(result.html);
        } else {
            alert('Error loading feedback details: ' + result.message);
        }
    } catch (error) {
        alert('Network error loading feedback details');
    }
}

function showFeedbackModal(html) {
    // This would integrate with the existing modal system
    alert('Feedback details modal will be integrated with the existing system.');
}

async function respondToFeedback(id) {
    // This would integrate with the existing response system
    alert('Response functionality will be integrated with the existing system.');
}

async function viewResponse(id) {
    // This would integrate with the existing view response system
    alert('View response functionality will be integrated with the existing system.');
}
</script>
