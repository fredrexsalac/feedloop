<?php
// Get filter parameters
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_date = $_GET['filter_date'] ?? '';
$search_user = $_GET['search_user'] ?? '';

// Build query based on filters
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "al.action = ?";
    $params[] = $filter_type . '_login';
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(al.timestamp) = ?";
    $params[] = $filter_date;
}

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
            u.role,
            COALESCE(a.full_name, s.full_name) as full_name,
            al.timestamp as login_time,
            CASE 
                WHEN al.action = 'admin_login' THEN 'admin'
                WHEN al.action = 'student_login' THEN 'student'
                ELSE u.role
            END as user_type
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        LEFT JOIN admins a ON al.user_id = a.user_id AND al.action = 'admin_login'
        LEFT JOIN students s ON al.user_id = s.user_id AND al.action = 'student_login'
        $where_clause 
        ORDER BY al.timestamp DESC 
        LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get summary statistics
$stats_sql = "SELECT 
    COUNT(*) as total_logins,
    COUNT(DISTINCT al.user_id) as unique_users,
    SUM(CASE WHEN al.action = 'admin_login' THEN 1 ELSE 0 END) as admin_logins,
    SUM(CASE WHEN al.action = 'student_login' THEN 1 ELSE 0 END) as student_logins,
    (SELECT COUNT(DISTINCT user_id) 
     FROM activity_logs 
     WHERE action IN ('admin_login', 'student_login') 
     AND timestamp > DATE_SUB(NOW(), INTERVAL 4 HOUR)
    ) as active_sessions
    FROM activity_logs al
    WHERE al.action IN ('admin_login', 'student_login')"
    . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : "");
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();
?>

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
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Filter Activity</h3>
        <button onclick="generateReport()" class="btn btn-success">
            <i class="fas fa-file-pdf"></i> Generate Report
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" onsubmit="filterActivity(event)">
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
</div>

<!-- Activity Table -->
<div class="card">
    <div class="card-header">
        <h3>Recent Activity</h3>
    </div>
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
                                <span class="badge <?php echo $activity['user_type'] === 'admin' ? 'bg-warning' : 'bg-info'; ?>">
                                    <?php echo ucfirst($activity['user_type']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($activity['login_time'])); ?></td>
                            <td>
                                <?php if (isset($activity['logout_time']) && $activity['logout_time']): ?>
                                    <?php echo date('M j, Y g:i A', strtotime($activity['logout_time'])); ?>
                                <?php else: ?>
                                    <em class="text-muted">Still logged in</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($activity['session_duration']) && $activity['session_duration'] > 0) {
                                    $hours = floor($activity['session_duration'] / 3600);
                                    $minutes = floor(($activity['session_duration'] % 3600) / 60);
                                    echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                } else {
                                    echo '<em class="text-muted">Active</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <span class="badge bg-success">Active</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($activities) >= 100): ?>
        <div class="card-footer">
            <div class="alert alert-info mb-0">
                <strong>Note:</strong> Showing the latest 100 records. Use filters to narrow down results.
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="../../assets/js/admin/activity_reports.js"></script>
