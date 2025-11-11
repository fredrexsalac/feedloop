<?php
// Get all feedback submissions
$feedback_list = [];
$form_responses = [];
$form_responses_limit = 10;
$total_feedback = 0;
$pending_feedback = 0;
$resolved_feedback = 0;

try {
    // Count total feedback
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions");
    $stmt->execute();
    $total_feedback = $stmt->fetchColumn();
    
    // FeedLoop v2.0: Count by category instead of status
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN feedback_category = 'Department Feedback' THEN 1 ELSE 0 END) as department_feedback,
        SUM(CASE WHEN feedback_category = 'Instructor Feedback' THEN 1 ELSE 0 END) as instructor_feedback,
        SUM(CASE WHEN feedback_category = 'Event Feedback' THEN 1 ELSE 0 END) as event_feedback,
        SUM(CASE WHEN feedback_category = 'Dean/Office Feedback' THEN 1 ELSE 0 END) as dean_feedback,
        SUM(CASE WHEN feedback_category = 'System Feedback' THEN 1 ELSE 0 END) as system_feedback,
        SUM(CASE WHEN feedback_category = 'Community-Based Issues' THEN 1 ELSE 0 END) as community_feedback,
        COUNT(*) as total_feedback
        FROM feedback_submissions");
    $stmt->execute();
    $category_counts = $stmt->fetch();
    
    // FeedLoop v2.0: Categorized Feedback System with Privacy Protection
    // Get feedback with category filtering and anonymized data
    $category_filter = $_GET['category'] ?? 'all';
    $date_filter = $_GET['date_filter'] ?? 'all';
    $page = $_GET['feedback_page'] ?? 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($category_filter !== 'all') {
        $where_conditions[] = "feedback_category = ?";
        $params[] = $category_filter;
    }
    
    if ($date_filter !== 'all') {
        switch ($date_filter) {
            case 'today':
                $where_conditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Select anonymized data only - no personal identifiers
    $stmt = $pdo->prepare("SELECT submission_id, subject, message, feedback_category, created_at, admin_response, admin_response_date,
                          name, email, user_type
                          FROM feedback_submissions 
                          $where_clause
                          ORDER BY created_at DESC 
                          LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $feedback_list = $stmt->fetchAll();

    // FeedLoop v2.0: Surface latest custom form responses with submitter details
    $form_query = $pdo->prepare("SELECT
            fr.response_id,
            fr.form_id,
            fr.respondent_name,
            fr.respondent_email,
            fr.respondent_type,
            fr.submitted_at,
            fr.is_complete,
            cf.title AS form_title,
            cf.allow_anonymous
        FROM form_responses fr
        INNER JOIN custom_forms cf ON fr.form_id = cf.form_id
        WHERE fr.is_complete = 1
        ORDER BY fr.submitted_at DESC
        LIMIT ?");
    $form_query->execute([$form_responses_limit]);
    $form_responses = $form_query->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading feedback: " . $e->getMessage();
}
?>

<div class="dashboard-header">
    <h1><i class="fas fa-comments me-2"></i>View Feedback</h1>
    <p>View and manage all feedback submissions from users</p>
</div>

<!-- FeedLoop v2.0: Categorized Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['department_feedback'] ?? 0; ?></div>
            <div class="stat-label">Department</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['instructor_feedback'] ?? 0; ?></div>
            <div class="stat-label">Instructor</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['event_feedback'] ?? 0; ?></div>
            <div class="stat-label">Events</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['dean_feedback'] ?? 0; ?></div>
            <div class="stat-label">Dean/Office</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['system_feedback'] ?? 0; ?></div>
            <div class="stat-label">System</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $category_counts['community_feedback'] ?? 0; ?></div>
            <div class="stat-label">Community</div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- FeedLoop v2.0: Category and Date Filters -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-filter me-2"></i>Filter Feedback</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label for="category" class="form-label">Category</label>
                <select name="category" id="category" class="form-select">
                    <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="Department Feedback" <?php echo $category_filter === 'Department Feedback' ? 'selected' : ''; ?>>Department Feedback</option>
                    <option value="Instructor Feedback" <?php echo $category_filter === 'Instructor Feedback' ? 'selected' : ''; ?>>Instructor Feedback</option>
                    <option value="Event Feedback" <?php echo $category_filter === 'Event Feedback' ? 'selected' : ''; ?>>Event Feedback</option>
                    <option value="Dean/Office Feedback" <?php echo $category_filter === 'Dean/Office Feedback' ? 'selected' : ''; ?>>Dean/Office Feedback</option>
                    <option value="System Feedback" <?php echo $category_filter === 'System Feedback' ? 'selected' : ''; ?>>System Feedback</option>
                    <option value="Community-Based Issues" <?php echo $category_filter === 'Community-Based Issues' ? 'selected' : ''; ?>>Community-Based Issues</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="date_filter" class="form-label">Date Range</label>
                <select name="date_filter" id="date_filter" class="form-select">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Feedback List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-list me-2"></i>Feedback Submissions</h3>
        <div class="text-muted">
            <small>Submitter details are shown to admins. Entries marked as Anonymous were submitted without personal info.</small>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($feedback_list)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No feedback submissions found</h5>
                <p class="text-muted">Feedback submissions will appear here once users start submitting feedback.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <!-- FeedLoop v2.0: Anonymized Feedback Table -->
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Feedback ID</th>
                            <th>Subject & Message</th>
                            <th>Submitted By</th>
                            <th>Category</th>
                            <th>Date Submitted</th>
                            <th>Response Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback_list as $feedback): ?>
                        <tr data-category="<?php echo htmlspecialchars($feedback['feedback_category'] ?? 'System Feedback'); ?>">
                            <td>
                                <strong class="text-primary">#<?php echo str_pad($feedback['submission_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars(substr($feedback['subject'] ?? 'No Subject', 0, 50)); ?></strong>
                                <?php if (strlen($feedback['subject'] ?? '') > 50): ?>...<?php endif; ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($feedback['message'] ?? '', 0, 100)); ?><?php if (strlen($feedback['message'] ?? '') > 100): ?>...<?php endif; ?></small>
                            </td>
                            <td>
                                <?php 
                                    $displayName = trim($feedback['name'] ?? '');
                                    $displayEmail = trim($feedback['email'] ?? '');
                                    $displayType = trim($feedback['user_type'] ?? '');
                                ?>
                                <?php if ($displayName !== ''): ?>
                                    <strong><?php echo htmlspecialchars($displayName); ?></strong>
                                <?php else: ?>
                                    <strong class="text-muted">Anonymous</strong>
                                <?php endif; ?>
                                <?php if ($displayType !== ''): ?>
                                    <br><small class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($displayType); ?></small>
                                <?php endif; ?>
                                <?php if ($displayEmail !== ''): ?>
                                    <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($displayEmail); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($feedback['feedback_category'] ?? 'System Feedback'); ?></span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($feedback['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if (!empty($feedback['admin_response'])): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-reply me-1"></i>Responded
                                    </span>
                                    <br><small class="text-muted"><?php echo date('M j, Y', strtotime($feedback['admin_response_date'])); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-clock me-1"></i>No Response
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- FeedLoop v2.0: Simplified Actions - No Delete/Alter Based on Type -->
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewFeedback(<?php echo $feedback['submission_id']; ?>)" title="View Full Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (empty($feedback['admin_response'])): ?>
                                    <button class="btn btn-outline-success" onclick="respondToFeedback(<?php echo $feedback['submission_id']; ?>)" title="Send Response">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-info" onclick="viewResponse(<?php echo $feedback['submission_id']; ?>)" title="View Response">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- Custom Form Responses Overview -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-file-alt me-2"></i>Custom Form Responses</h3>
        <div class="text-muted">
            <small>Showing latest <?php echo $form_responses_limit; ?> completed responses</small>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($form_responses)): ?>
            <div class="text-center py-4">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No form responses available</h5>
                <p class="text-muted">Completed custom form submissions will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Response ID</th>
                            <th>Form</th>
                            <th>Submitted By</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($form_responses as $response): ?>
                        <tr>
                            <td>
                                <strong class="text-purple">FR-<?php echo str_pad($response['response_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($response['form_title'] ?? 'Untitled Form'); ?></strong>
                                <br><small class="text-muted">Form ID: <?php echo htmlspecialchars($response['form_id']); ?></small>
                            </td>
                            <td>
                                <?php 
                                    $formName = trim($response['respondent_name'] ?? '');
                                    $formEmail = trim($response['respondent_email'] ?? '');
                                    $formType = trim($response['respondent_type'] ?? '');
                                ?>
                                <?php if ($formName !== ''): ?>
                                    <strong><?php echo htmlspecialchars($formName); ?></strong>
                                <?php else: ?>
                                    <strong class="text-muted">Anonymous</strong>
                                <?php endif; ?>
                                <?php if ($formType !== '' && $formType !== 'anonymous'): ?>
                                    <br><small class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($formType); ?></small>
                                <?php elseif ($formType === 'anonymous'): ?>
                                    <br><small class="badge bg-light text-muted border">ANONYMOUS</small>
                                <?php endif; ?>
                                <?php if ($formEmail !== ''): ?>
                                    <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($formEmail); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($response['submitted_at'])); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($response['submitted_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ((int)$response['is_complete'] === 1): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Complete</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>In Progress</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../content/custom_forms/view_responses.php?form_id=<?php echo $response['form_id']; ?>" class="btn btn-outline-primary" target="_blank" rel="noopener" title="View form responses">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
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

<!-- Feedback Detail Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="feedbackModalBody">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="resolveBtn" onclick="markResolvedFromModal()">Mark as Resolved</button>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>Send Response to User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="responseModalContent">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="mt-3">
                    <label for="responseMessage" class="form-label">Your Response:</label>
                    <textarea class="form-control" id="responseMessage" rows="4" 
                              placeholder="Type your response to the user here..."></textarea>
                    <small class="text-muted">This response will be sent as a notification to the user.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendResponse()">
                    <i class="fas fa-paper-plane me-1"></i>Send Response
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Filter feedback by status
function filterFeedback(status) {
    const rows = document.querySelectorAll('tbody tr[data-status]');
    
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update button states
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

// View feedback details
async function viewFeedback(id) {
    try {
        const response = await fetch(`../api/get_feedback_details.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('feedbackModalBody').innerHTML = result.html;
            const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            modal.show();
            
            // Update resolve button
            const resolveBtn = document.getElementById('resolveBtn');
            if (result.feedback.status === 'resolved') {
                resolveBtn.style.display = 'none';
            } else {
                resolveBtn.style.display = 'inline-block';
                resolveBtn.setAttribute('data-id', id);
            }
        } else {
            alert('Error loading feedback details: ' + result.message);
        }
    } catch (error) {
        alert('Network error loading feedback details');
    }
}

// Mark feedback as resolved
async function markResolved(id) {
    if (!confirm('Mark this feedback as resolved?')) return;
    
    try {
        const response = await fetch('../api/update_feedback_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: 'resolved' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload(); // Refresh to show updated status
        } else {
            alert('Error updating feedback status: ' + result.message);
        }
    } catch (error) {
        alert('Network error updating feedback status');
    }
}

// Mark resolved from modal
function markResolvedFromModal() {
    const id = document.getElementById('resolveBtn').getAttribute('data-id');
    if (id) {
        markResolved(id);
    }
}

// Delete feedback
async function deleteFeedback(id) {
    if (!confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('../api/delete_feedback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload(); // Refresh to remove deleted item
        } else {
            alert('Error deleting feedback: ' + result.message);
        }
    } catch (error) {
        alert('Network error deleting feedback');
    }
}

// Respond to feedback
let currentFeedbackId = null;

async function respondToFeedback(id) {
    currentFeedbackId = id;
    
    try {
        const response = await fetch(`../api/get_feedback_details.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('responseModalContent').innerHTML = result.html;
            document.getElementById('responseMessage').value = '';
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();
        } else {
            alert('Error loading feedback details: ' + result.message);
        }
    } catch (error) {
        alert('Network error loading feedback details');
    }
}

// Send response
async function sendResponse() {
    if (!currentFeedbackId) return;
    
    const responseMessage = document.getElementById('responseMessage').value.trim();
    
    if (!responseMessage) {
        alert('Please enter a response message');
        return;
    }
    
    try {
        const response = await fetch('../api/respond_feedback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                feedback_id: currentFeedbackId, 
                response_message: responseMessage 
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Response sent successfully! The user will receive a notification.');
            bootstrap.Modal.getInstance(document.getElementById('responseModal')).hide();
            location.reload(); // Refresh to show updated status
        } else {
            alert('Error sending response: ' + result.message);
        }
    } catch (error) {
        alert('Network error sending response');
    }
}

// View response for resolved feedback
async function viewResponse(id) {
    try {
        const response = await fetch(`../api/get_feedback_details.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('feedbackModalBody').innerHTML = result.html;
            const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            modal.show();
            
            // Hide resolve button for resolved feedback
            document.getElementById('resolveBtn').style.display = 'none';
        } else {
            alert('Error loading feedback details: ' + result.message);
        }
    } catch (error) {
        alert('Network error loading feedback details');
    }
}
</script>
