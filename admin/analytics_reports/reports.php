<?php
session_start();
require '../db.php'; // Include database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/unified_login.php");
    exit();
}

// Handle report generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Generate report based on type
    switch ($report_type) {
        case 'feedback_summary':
            $report_title = "Feedback Summary Report";
            $report_data = generate_feedback_summary($pdo, $start_date, $end_date);
            break;
        case 'user_activity':
            $report_title = "User Activity Report";
            $report_data = generate_user_activity($pdo, $start_date, $end_date);
            break;
        case 'system_analytics':
            $report_title = "System Analytics Report";
            $report_data = generate_system_analytics($pdo, $start_date, $end_date);
            break;
        default:
            $report_title = "General Report";
            $report_data = [];
    }
}

// Report generation functions
function generate_feedback_summary($pdo, $start_date, $end_date) {
    $query = "
        SELECT 
            fs.category,
            fs.status,
            fs.priority,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, fs.created_at, fs.updated_at)) as avg_response_time
        FROM feedback_submissions fs
        WHERE fs.created_at BETWEEN ? AND ?
        GROUP BY fs.category, fs.status, fs.priority
        ORDER BY fs.category, fs.status
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

function generate_user_activity($pdo, $start_date, $end_date) {
    $query = "
        SELECT 
            u.username,
            u.role,
            COUNT(fs.submission_id) as feedback_submitted,
            COUNT(DISTINCT fs.student_id) as active_students,
            MAX(fs.created_at) as last_activity
        FROM users u
        LEFT JOIN feedback_submissions fs ON u.user_id = fs.student_id 
            AND fs.created_at BETWEEN ? AND ?
        WHERE u.role = 'student'
        GROUP BY u.user_id, u.username, u.role
        ORDER BY feedback_submitted DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

function generate_system_analytics($pdo, $start_date, $end_date) {
    $query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as daily_submissions,
            AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_time,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
        FROM feedback_submissions
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Generate Reports</h1>
        
        <!-- Report Generation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Generate New Report</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-control" id="report_type" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="feedback_summary">Feedback Summary</option>
                            <option value="user_activity">User Activity</option>
                            <option value="system_analytics">System Analytics</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="generate_report" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Generated Report Display -->
        <?php if (isset($report_data) && !empty($report_data)): ?>
        <div class="card">
            <div class="card-header">
                <h5><?php echo $report_title; ?> (<?php echo $start_date; ?> to <?php echo $end_date; ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <?php 
                                // Generate table headers based on report type
                                $headers = array_keys((array)$report_data[0]);
                                foreach ($headers as $header): ?>
                                    <th><?php echo ucwords(str_replace('_', ' ', $header)); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php foreach ((array)$row as $value): ?>
                                    <td>
                                        <?php 
                                        if (is_numeric($value)) {
                                            echo round($value, 2);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Export Options -->
                <div class="mt-3">
                    <button class="btn btn-success" onclick="exportToCSV()">Export to CSV</button>
                    <button class="btn btn-primary" onclick="window.print()">Print Report</button>
                </div>
            </div>
        </div>
        <?php elseif (isset($report_data) && empty($report_data)): ?>
            <div class="alert alert-info">
                No data found for the selected criteria.
            </div>
        <?php endif; ?>
        
        <!-- Back to Dashboard -->
        <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
    </div>

    <script src="../assets/js/admin/reports.js"></script>
</body>
</html>
