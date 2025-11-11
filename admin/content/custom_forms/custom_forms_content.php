<?php
// Custom Forms Management Interface
// Google Forms-inspired feedback form builder for FeedLoop
// Author: Cascade AI Assistant
// Date: October 19, 2025

// Authentication and database connection are handled by load_content.php

// Get user's forms
$user_forms = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            fa.total_views,
            fa.total_completions,
            fa.completion_rate,
            fa.last_response_at
        FROM custom_forms cf
        LEFT JOIN form_analytics fa ON cf.form_id = fa.form_id
        WHERE cf.created_by = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_forms = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle gracefully if tables don't exist yet
    $user_forms = [];
}

// Get form statistics
$total_forms = count($user_forms);
$active_forms = count(array_filter($user_forms, function($form) { return $form['is_active']; }));
$total_responses = array_sum(array_column($user_forms, 'response_count'));
$avg_completion_rate = $total_forms > 0 ? array_sum(array_column($user_forms, 'completion_rate')) / $total_forms : 0;
?>

<div class="custom-forms-container">
    <!-- Header Section -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-clipboard-list me-2"></i>Custom Forms</h2>
                <p class="text-muted">Create and manage customizable feedback forms with QR codes and shareable links</p>
            </div>
            <button class="btn btn-primary" onclick="showCreateFormModal()">
                <i class="fas fa-plus me-2"></i>Create New Form
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_forms; ?></h3>
                    <p>Total Forms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $active_forms; ?></h3>
                    <p>Active Forms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-reply-all"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_responses; ?></h3>
                    <p>Total Responses</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($avg_completion_rate, 1); ?>%</h3>
                    <p>Avg Completion</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Your Forms</h5>
        </div>
        <div class="card-body">
            <?php if (empty($user_forms)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h4>No forms created yet</h4>
                    <p class="text-muted">Create your first custom feedback form to get started</p>
                    <button class="btn btn-primary" onclick="showCreateFormModal()">
                        <i class="fas fa-plus me-2"></i>Create Your First Form
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Form Title</th>
                                <th>Status</th>
                                <th>Visibility</th>
                                <th>Responses</th>
                                <th>Completion Rate</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_forms as $form): ?>
                                <tr data-form-id="<?php echo $form['form_id']; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($form['title']); ?></strong>
                                                <?php if ($form['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($form['description'], 0, 60)) . (strlen($form['description']) > 60 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $form['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $form['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst($form['visibility']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo $form['response_count']; ?></strong>
                                        <?php if ($form['max_responses']): ?>
                                            <small class="text-muted">/ <?php echo $form['max_responses']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($form['completion_rate']): ?>
                                            <div class="progress" style="width: 60px; height: 8px;">
                                                <div class="progress-bar" style="width: <?php echo $form['completion_rate']; ?>%"></div>
                                            </div>
                                            <small><?php echo number_format($form['completion_rate'], 1); ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($form['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $form['form_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $form['form_id']; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="viewFormResponses(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-chart-bar me-2 text-primary"></i>View Responses
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="shareForm(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-share-alt me-2 text-info"></i>Share Form
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="editForm(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-edit me-2 text-secondary"></i>Edit Form
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="duplicateForm(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-copy me-2 text-success"></i>Duplicate Form
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="exportFormData(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-download me-2 text-warning"></i>Export Data
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="generateFormReport(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-file-pdf me-2 text-danger"></i>Generate Report
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="toggleFormStatus(<?php echo $form['form_id']; ?>, <?php echo $form['is_active'] ? 'false' : 'true'; ?>)">
                                                        <i class="fas fa-<?php echo $form['is_active'] ? 'pause' : 'play'; ?> me-2 text-<?php echo $form['is_active'] ? 'warning' : 'success'; ?>"></i>
                                                        <?php echo $form['is_active'] ? 'Deactivate' : 'Activate'; ?> Form
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteForm(<?php echo $form['form_id']; ?>)">
                                                        <i class="fas fa-trash me-2"></i>Delete Form
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Form Modal -->
<div class="modal fade" id="createFormModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="form-builder-container">
                    <!-- Form Builder will be loaded here -->
                    <div class="row">
                        <!-- Form Settings Panel -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Form Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form id="form-settings">
                                        <div class="mb-3">
                                            <label class="form-label">Form Title *</label>
                                            <input type="text" class="form-control" id="form-title" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" id="form-description" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Visibility</label>
                                            <select class="form-select" id="form-visibility">
                                                <option value="public">Public - Anyone can access</option>
                                                <option value="private">Private - Link only</option>
                                                <option value="department">Department Specific</option>
                                                <option value="event">Event Specific</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="target-audience-group" style="display: none;">
                                            <label class="form-label">Target Audience</label>
                                            <input type="text" class="form-control" id="target-audience" placeholder="e.g., Computer Science Department">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="allow-anonymous" checked>
                                                <label class="form-check-label" for="allow-anonymous">
                                                    Allow anonymous responses
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Response Limit (optional)</label>
                                            <input type="number" class="form-control" id="max-responses" min="1">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expiration Date (optional)</label>
                                            <input type="datetime-local" class="form-control" id="expires-at">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Question Builder Panel -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Questions</h6>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary" onclick="addQuestion()">
                                            <i class="fas fa-plus me-1"></i>Add Question
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="questions-container">
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-question-circle fa-2x mb-2"></i>
                                            <p>No questions added yet. Click "Add Question" to get started.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveForm()">
                    <i class="fas fa-save me-2"></i>Create Form
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Question Template (Hidden) -->
<div id="question-template" style="display: none;">
    <div class="question-item card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="question-number badge bg-primary me-2">1</span>
                <input type="text" class="form-control question-text" placeholder="Enter your question here..." style="border: none; background: transparent;">
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-danger" onclick="removeQuestion(this)" title="Remove">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Question Type</label>
                    <select class="form-select question-type" onchange="updateQuestionOptions(this)">
                        <option value="text">Short Text</option>
                        <option value="textarea">Long Text</option>
                        <option value="radio">Multiple Choice (Single)</option>
                        <option value="checkbox">Multiple Choice (Multiple)</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="rating_stars">Star Rating</option>
                        <option value="rating_scale">Rating Scale</option>
                        <option value="slider">Slider</option>
                        <option value="email">Email</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="time">Time</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input question-required" type="checkbox">
                        <label class="form-check-label">Required</label>
                    </div>
                </div>
            </div>
            <div class="question-options mt-3" style="display: none;">
                <!-- Dynamic options will be added here based on question type -->
            </div>
        </div>
    </div>
</div>

<!-- PDF Report Preview Modal -->
<div class="modal fade" id="reportPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-pdf me-2"></i>Form Results Report Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="background: #f5f5f5;">
                <div id="reportPreviewContent" class="report-preview-container">
                    <!-- Report preview will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Generating report...</span>
                        </div>
                        <p class="mt-3 text-muted">Generating comprehensive report with charts and analysis...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close Preview
                </button>
                <button type="button" class="btn btn-danger" id="downloadReportBtn">
                    <i class="fas fa-download me-2"></i>Download PDF Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Custom Forms JavaScript -->
<script src="/feedloop/assets/js/admin/custom_forms.js?v=<?php echo time(); ?>"></script>

<!-- Include CSS for Custom Forms -->
<link rel="stylesheet" href="/feedloop/assets/css/admin/custom_forms.css?v=<?php echo time(); ?>">

<!-- Include jsPDF and Chart.js for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

