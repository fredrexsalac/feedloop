<?php
// Get current date for default date input
$current_date = date('Y-m-d');
?>

<!-- Add this script to ensure Bootstrap JS is properly initialized -->

<!-- Toast Container -->
<div id="toastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Generate Reports</h1>
        <p class="text-muted mb-0">Create and download system reports</p>
    </div>
    <div>
        <button class="btn btn-primary">
            <i class="fas fa-download me-2"></i>Export Data
        </button>
    </div>
</div>

<!-- Date Range Picker -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="far fa-calendar-alt me-2"></i>Select Date Range</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="far fa-calendar me-2"></i>From</span>
                    <input type="date" class="form-control" id="startDate" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="far fa-calendar me-2"></i>To</span>
                    <input type="date" class="form-control" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-primary w-100" id="applyFilterBtn">
                    <i class="fas fa-sync-alt me-2"></i>Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- User Reports Card -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>User Reports</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary text-start" data-report-type="student_registration">
                        <i class="fas fa-user-graduate me-2"></i>Student Registration Report
                    </button>
                    <button type="button" class="btn btn-outline-primary text-start" data-report-type="admin_activity">
                        <i class="fas fa-user-shield me-2"></i>Admin Activity Report
                    </button>
                    <button type="button" class="btn btn-outline-primary text-start" data-report-type="user_logins">
                        <i class="fas fa-sign-in-alt me-2"></i>User Login Statistics
                    </button>
                    <button type="button" class="btn btn-outline-primary text-start" data-report-type="inactive_users">
                        <i class="fas fa-user-clock me-2"></i>Inactive Users Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Reports Card -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Feedback Reports</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success text-start" data-report-type="feedback_summary">
                        <i class="fas fa-chart-pie me-2"></i>Feedback Summary Report
                    </button>
                    <button type="button" class="btn btn-outline-success text-start" data-report-type="category_analysis">
                        <i class="fas fa-tags me-2"></i>Category Analysis Report
                    </button>
                    <button type="button" class="btn btn-outline-success text-start" data-report-type="response_time">
                        <i class="fas fa-stopwatch me-2"></i>Response Time Report
                    </button>
                    <button type="button" class="btn btn-outline-success text-start" data-report-type="satisfaction">
                        <i class="fas fa-smile me-2"></i>Satisfaction Metrics
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Activity Report Modal -->
<div class="modal fade" id="activityReportModal" tabindex="-1" aria-labelledby="activityReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityReportModalLabel">Generate Activity Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityReportForm" method="GET" target="_blank" action="/admin/generate_report.php">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="report_start_date" name="start_date" value="<?php echo $current_date; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="report_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="report_end_date" name="end_date" value="<?php echo $current_date; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="report_user_type" class="form-label">User Type</label>
                        <select class="form-select" id="report_user_type" name="user_type">
                            <option value="all">All Users</option>
                            <option value="admin">Admins Only</option>
                            <option value="student">Students Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="report_format" class="form-label">Report Format</label>
                        <select class="form-select" id="report_format" name="format">
                            <option value="html" selected>Web Page</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
<?php if (!isset($is_ajax)): ?>
<?php endif; ?>

<?php if (!isset($is_ajax)): ?>
<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
                    <div id="filterToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <i class="fas fa-filter text-primary me-2"></i>
                            <strong class="me-auto">Filter Applied</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Date range filter has been applied to all reports.
                        </div>
                    </div>
                </div>
        <div class="toast-header">
            <i class="fas fa-filter text-primary me-2"></i>
            <strong class="me-auto">Filter Applied</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Date range filter has been applied to all reports.
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../../assets/js/admin/reports.js"></script>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-control">
                            <option>User Activity</option>
                            <option>Feedback Analysis</option>
                            <option>System Usage</option>
                            <option>Performance Metrics</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-control">
                            <option>Last 7 days</option>
                            <option>Last 30 days</option>
                            <option>Last 3 months</option>
                            <option>Custom Range</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Format</label>
                        <select class="form-control">
                            <option>PDF</option>
                            <option>Excel (XLSX)</option>
                            <option>CSV</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-primary">Generate Custom Report</button>
        </form>
    </div>
</div>
