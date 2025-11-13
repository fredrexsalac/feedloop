<?php
require_once '../../db.php';

// Get filter parameters
$report_type = $_GET['type'] ?? 'activity';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'html';

// Set report title based on type
$report_titles = [
    'activity' => 'Activity Report',
    'student_registration' => 'Student Registration Report',
    'admin_activity' => 'Admin Activity Report',
    'user_logins' => 'User Login Statistics',
    'inactive_users' => 'Inactive Users Report',
    'feedback_summary' => 'Feedback Summary Report',
    'category_analysis' => 'Category Analysis Report',
    'response_time' => 'Response Time Report',
    'satisfaction' => 'Satisfaction Metrics'
];

$report_title = $report_titles[$report_type] ?? 'Activity Report';

// Build query based on filters
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "al.action = ?";
    $params[] = $filter_type . '_login';
}

// Add date range filter
$where_conditions[] = "DATE(al.timestamp) BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;

if (!empty($search_user)) {
    $where_conditions[] = "(u.username LIKE ? OR a.full_name LIKE ? OR s.full_name LIKE ?)";
    $params = array_merge($params, ["%$search_user%", "%$search_user%", "%$search_user%"]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get activity data
$sql = "SELECT 
            al.*,
            u.username,
            u.email,
            COALESCE(a.full_name, s.full_name) as full_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        LEFT JOIN admins a ON al.user_id = a.user_id AND al.action = 'admin_login'
        LEFT JOIN students s ON al.user_id = s.user_id AND al.action = 'student_login'
        $where_clause 
        ORDER BY al.timestamp DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get summary statistics
$stats_sql = "SELECT 
    COUNT(*) as total_logins,
    COUNT(DISTINCT al.user_id) as unique_users,
    SUM(CASE WHEN al.action = 'admin_login' THEN 1 ELSE 0 END) as admin_logins,
    SUM(CASE WHEN al.action = 'student_login' THEN 1 ELSE 0 END) as student_logins
    FROM activity_logs al
    WHERE al.action IN ('admin_login', 'student_login')"
    . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : "");
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Set headers based on format
if ($format === 'pdf') {
    // This is a placeholder - you'll need a PDF library like TCPDF or mPDF
    // header('Content-Type: application/pdf');
    // For now, we'll force HTML format
    $format = 'html';
    header('Content-Type: text/html; charset=utf-8');
} else {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FeedLoop - Activity Report</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        .report-title {
            color: #2c3e50;
            margin: 10px 0;
        }
        .report-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin: 5px;
            flex: 1;
            min-width: 120px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 12px;
            }
            .stat-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <?php
        $logoPath = '../../assets/img/logo/logo.jpg';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = mime_content_type($logoPath);
            echo '<img src="data:' . $logoMime . ';base64,' . $logoData . '" alt="FeedLoop Logo" class="logo">';
        } else {
            echo '<h2>FeedLoop</h2>';
        }
        ?>
        <h1 class="report-title">Activity Report</h1>
        <div class="report-date">
            Generated on: <?php echo date('F j, Y, g:i a'); ?>
            <div class="report-period">
                Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?>
            </div>
            <?php if($filter_type !== 'all'): ?>
                <div class="report-filter">
                    User Type: <?php echo ucfirst($filter_type); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-card">
        <h3>Summary</h3>
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['total_logins']; ?></div>
                <div class="stat-label">Total Logins</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['unique_users']; ?></div>
                <div class="stat-label">Unique Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['admin_logins']; ?></div>
                <div class="stat-label">Admin Logins</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['student_logins']; ?></div>
                <div class="stat-label">Student Logins</div>
            </div>
        </div>
    </div>

    <h3>Activity Logs</h3>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($activities as $activity): ?>
                <tr>
                    <td><?php echo date('M j, Y g:i a', strtotime($activity['timestamp'])); ?></td>
                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                    <td><?php echo htmlspecialchars($activity['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($activity['email']); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></td>
                    <td><?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" class="btn btn-primary me-2">
            <i class="fas fa-print me-2"></i>Print Report
        </button>
        <a href="#" onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times me-2"></i>Close Window
        </a>
        <p class="mt-3 text-muted">
            <i class="fas fa-info-circle me-1"></i> Generated on <?php echo date('F j, Y \a\t g:i A'); ?>
        </p>
    </div>

    <script src="../assets/js/admin/generate_report.js"></script>
</body>
</html>
