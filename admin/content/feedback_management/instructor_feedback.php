<?php
/**
 * FeedLoop v2.0 - Instructor Feedback View
 * 
 * This page displays only Instructor Feedback submissions with anonymized data
 * for privacy protection. Admins can view and respond to instructor-specific feedback.
 */

session_start();
require '../../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$error = '';
$feedback_list = [];
$total_instructor_feedback = 0;

try {
    // Count total instructor feedback
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE feedback_category = 'Instructor Feedback'");
    $stmt->execute();
    $total_instructor_feedback = $stmt->fetchColumn();
    
    // Get instructor feedback with pagination
    $page = $_GET['page'] ?? 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Date filter
    $date_filter = $_GET['date_filter'] ?? 'all';
    $where_conditions = ["feedback_category = 'Instructor Feedback'"];
    $params = [];
    
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
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Select anonymized data only
    $stmt = $pdo->prepare("SELECT id, subject, message, feedback_category, created_at, admin_response, admin_response_date
                          FROM feedback_submissions 
                          $where_clause
                          ORDER BY created_at DESC 
                          LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $feedback_list = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading instructor feedback: " . $e->getMessage();
}
?>

<div class="dashboard-header">
    <h1><i class="fas fa-chalkboard-teacher me-2"></i>Instructor Feedback</h1>
    <p>View and manage feedback about instructors, teaching methods, and academic performance</p>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-number"><?php echo $total_instructor_feedback; ?></div>
            <div class="stat-label">Total Instructor Feedback</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number">
                <?php 
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE feedback_category = 'Instructor Feedback' AND admin_response IS NULL");
                $stmt->execute();
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Awaiting Response</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-number">
                <?php 
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE feedback_category = 'Instructor Feedback' AND admin_response IS NOT NULL");
                $stmt->execute();
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Responded</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-number">
                <?php 
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE feedback_category = 'Instructor Feedback' AND DATE(created_at) = CURDATE()");
                $stmt->execute();
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Today</div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Date Filter -->
<div class="card mb-3">
    <div class="card-header">
        <h5><i class="fas fa-filter me-2"></i>Filter Instructor Feedback</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
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
            <div class="col-md-6 text-end">
                <label class="form-label">&nbsp;</label>
                <div>
                    <a href="../feedback_management/view_feedback_content.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to All Feedback
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Instructor Feedback List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-list me-2"></i>Instructor Feedback Submissions</h3>
        <div class="text-muted">
            <small>Anonymized data - Privacy protected</small>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($feedback_list)): ?>
            <div class="text-center py-4">
                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No instructor feedback found</h5>
                <p class="text-muted">Instructor feedback submissions will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Feedback ID</th>
                            <th>Subject & Message</th>
                            <th>Date Submitted</th>
                            <th>Response Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback_list as $feedback): ?>
                        <tr>
                            <td>
                                <strong class="text-primary">#<?php echo str_pad($feedback['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                <br><small class="text-muted">Instructor Feedback</small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars(substr($feedback['subject'] ?? 'No Subject', 0, 60)); ?></strong>
                                <?php if (strlen($feedback['subject'] ?? '') > 60): ?>...<?php endif; ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($feedback['message'] ?? '', 0, 120)); ?><?php if (strlen($feedback['message'] ?? '') > 120): ?>...<?php endif; ?></small>
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
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Awaiting Response
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewFeedback(<?php echo $feedback['id']; ?>)" title="View Full Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (empty($feedback['admin_response'])): ?>
                                    <button class="btn btn-outline-success" onclick="respondToFeedback(<?php echo $feedback['id']; ?>)" title="Send Response">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-info" onclick="viewResponse(<?php echo $feedback['id']; ?>)" title="View Response">
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
