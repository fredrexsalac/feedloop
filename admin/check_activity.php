<?php
require_once __DIR__ . '/../db.php';

// Get all users with their activity status
$stmt = $pdo->query("
    SELECT 
        u.user_id,
        u.username,
        u.last_activity,
        u.session_id,
        COALESCE(a.full_name, s.full_name) as full_name,
        CASE 
            WHEN a.user_id IS NOT NULL THEN 'admin' 
            WHEN s.user_id IS NOT NULL THEN 'student' 
            ELSE 'unknown' 
        END as user_type,
        CASE 
            WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'active'
            ELSE 'inactive'
        END as status
    FROM users u
    LEFT JOIN admins a ON u.user_id = a.user_id
    LEFT JOIN students s ON u.user_id = s.user_id
    ORDER BY u.last_activity DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Activity Status</title>
    <link href="../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>User Activity Status</h2>
        <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <div class="mb-3">
            <a href="check_activity.php" class="btn btn-primary">Refresh</a>
        </div>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Last Activity</th>
                    <th>Session ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                    <td><?php echo $user['last_activity'] ? date('Y-m-d H:i:s', strtotime($user['last_activity'])) : 'Never'; ?></td>
                    <td style="max-width: 100px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($user['session_id']); ?>">
                        <?php echo $user['session_id'] ? substr($user['session_id'], 0, 10) . '...' : 'None'; ?>
                    </td>
                    <td>
                        <?php 
                        $statusClass = 'secondary';
                        $statusText = 'Inactive';
                        
                        if ($user['last_activity']) {
                            $lastActive = strtotime($user['last_activity']);
                            $minutesAgo = floor((time() - $lastActive) / 60);
                            
                            if ($minutesAgo < 5) {
                                $statusClass = 'success';
                                $statusText = 'Active now';
                            } elseif ($minutesAgo < 15) {
                                $statusClass = 'info';
                                $statusText = 'Recently active';
                            } elseif ($minutesAgo < 60) {
                                $statusClass = 'warning';
                                $statusText = 'Inactive (' . $minutesAgo . 'm ago)';
                            } else {
                                $statusClass = 'secondary';
                                $statusText = 'Offline';
                            }
                        }
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>" title="Last activity: <?php echo $user['last_activity'] ?? 'Never'; ?>">
                            <?php echo $statusText; ?>
                        </span>
                        <?php if ($user['last_activity']): ?>
                            <div class="small text-muted"><?php echo date('M j, g:i a', strtotime($user['last_activity'])); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
