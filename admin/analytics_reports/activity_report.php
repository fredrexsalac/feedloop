<?php
session_start();
require '../db.php';

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['position'] !== 'Super Admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Get filter parameters
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_date = $_GET['filter_date'] ?? '';
$search_user = $_GET['search_user'] ?? '';

// Build query based on filters
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "user_type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(login_time) = ?";
    $params[] = $filter_date;
}

if (!empty($search_user)) {
    $where_conditions[] = "(username LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search_user%";
    $params[] = "%$search_user%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get activity data
$sql = "SELECT * FROM user_activity $where_clause ORDER BY login_time DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get summary statistics
$stats_sql = "SELECT 
    COUNT(*) as total_logins,
    COUNT(DISTINCT user_id, user_type) as unique_users,
    SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admin_logins,
    SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as student_logins,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_sessions
    FROM user_activity $where_clause";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Report - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_regular.css">
    <style>
        .activity-filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .activity-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .table {
            margin-bottom: 0;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .user-type-admin {
            background-color: #fff3cd;
            color: #856404;
        }
        .user-type-student {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <img src="../../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo">
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_feedback.php">Manage Feedback</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="analytics.php">View Analytics</a></li>
                <li><a href="activity_report.php" style="background-color: rgba(255, 255, 255, 0.2);">Activity Report</a></li>
                <li><a href="reports.php">Generate Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1>User Activity Report</h1>
                <p>Monitor user login activity and session tracking</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_logins'] ?? 0; ?></div>
                    <div class="stat-label">Total Logins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['unique_users'] ?? 0; ?></div>
                    <div class="stat-label">Unique Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['admin_logins'] ?? 0; ?></div>
                    <div class="stat-label">Admin Logins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['student_logins'] ?? 0; ?></div>
                    <div class="stat-label">Student Logins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_sessions'] ?? 0; ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="activity-filters">
                <h3>Filter Activity</h3>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label">User Type</label>
                        <select class="form-select" id="filter_type" name="filter_type">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="admin" <?php echo $filter_type === 'admin' ? 'selected' : ''; ?>>Admins Only</option>
                            <option value="student" <?php echo $filter_type === 'student' ? 'selected' : ''; ?>>Students Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="search_user" class="form-label">Search User</label>
                        <input type="text" class="form-control" id="search_user" name="search_user" 
                               placeholder="Username or Full Name" value="<?php echo htmlspecialchars($search_user); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Activity Table -->
            <div class="activity-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Duration</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <em>No activity records found for the selected filters.</em>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong><br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge user-type-<?php echo $activity['user_type']; ?>">
                                                <?php echo ucfirst($activity['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($activity['login_time'])); ?></td>
                                        <td>
                                            <?php if ($activity['logout_time']): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($activity['logout_time'])); ?>
                                            <?php else: ?>
                                                <em class="text-muted">Still logged in</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($activity['session_duration'] > 0) {
                                                $hours = floor($activity['session_duration'] / 3600);
                                                $minutes = floor(($activity['session_duration'] % 3600) / 60);
                                                echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                            } else {
                                                echo '<em class="text-muted">Active</em>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $activity['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $activity['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (count($activities) >= 100): ?>
                <div class="alert alert-info mt-3">
                    <strong>Note:</strong> Showing the latest 100 records. Use filters to narrow down results.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
