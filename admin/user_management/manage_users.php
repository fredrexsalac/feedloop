<?php
session_start();
require '../../db.php'; // Include database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters - exclude admin and super admin users
$query = "SELECT u.*, s.course, s.year_level 
          FROM users u 
          LEFT JOIN students s ON u.user_id = s.user_id 
          WHERE u.role != 'admin'";

$params = [];

if (!empty($search)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter) && $role_filter != 'admin') {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM ($query) as total";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Add pagination and ordering
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deleted successfully!";
            
            // Refresh the page
            header("Location: manage_users.php?" . http_build_query($_GET));
            exit();
        } catch (Exception $e) {
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FeedLoop</title>
    <link rel="stylesheet" href="../../assets/css/homepage/bootstrap.css">
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Manage Users</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Search & Filter Users</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Search username or email">
                    </div>
                    <div class="col-md-4">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $role_filter == 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="manage_users.php" class="btn btn-secondary">Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5>User List (<?php echo $total_records; ?> total)</h5>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Course</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-primary' : 'bg-success'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($user['course'] . ' - Year ' . $user['year_level']); ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        // Check if user is suspended by looking at activity logs
                                        $is_suspended = false;
                                        try {
                                            $suspend_check = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND action = 'user_suspended' AND timestamp > COALESCE((SELECT MAX(timestamp) FROM activity_logs WHERE user_id = ? AND action = 'user_unsuspended'), '1970-01-01')");
                                            $suspend_check->execute([$user['user_id'], $user['user_id']]);
                                            $is_suspended = $suspend_check->fetchColumn() > 0;
                                        } catch (Exception $e) {
                                            // Ignore error, default to not suspended
                                        }
                                        ?>
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn view-btn" 
                                                    data-user-id="<?php echo $user['user_id']; ?>" 
                                                    data-user-name="<?php echo htmlspecialchars($user['username']); ?>" 
                                                    data-user-role="<?php echo $user['role']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($is_suspended): ?>
                                                <button type="button" class="action-btn unsuspend-btn" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        data-user-name="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="fas fa-user-check"></i> Unsuspend
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="action-btn suspend-btn" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        data-user-name="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="fas fa-ban"></i> Suspend
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        No users found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Back to Dashboard -->
        <a href="../dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
    </div>

    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <!-- User details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspend Confirmation Modal -->
    <div class="modal fade" id="suspendConfirmModal" tabindex="-1" aria-labelledby="suspendConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="suspendConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Suspend User Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                </div>
                <div class="modal-body pt-2">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-slash fa-3x text-danger mb-3"></i>
                        <h6 class="fw-bold mb-2">Are you sure you want to suspend this user?</h6>
                        <p class="text-muted mb-3" id="suspendUserName">This action will prevent the user from accessing the system.</p>
                    </div>
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>The user will be unable to log in until their account is unsuspended by an administrator.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmSuspendBtn">
                        <i class="fas fa-ban me-1"></i>Suspend User
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- User Management JavaScript -->
    <script src="../../assets/js/admin/user_management.js"></script>
    
    <style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 4px 8px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .view-btn {
        background-color: #17a2b8;
        color: white;
    }
    
    .view-btn:hover {
        background-color: #138496;
        color: white;
    }
    
    .suspend-btn {
        background-color: #dc3545;
        color: white;
    }
    
    .suspend-btn:hover {
        background-color: #c82333;
        color: white;
    }
    
    .unsuspend-btn {
        background-color: #28a745;
        color: white;
    }
    
    .unsuspend-btn:hover {
        background-color: #218838;
        color: white;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-suspended {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .user-detail-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .detail-header {
        color: #495057;
        font-weight: 600;
        margin-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 5px;
    }
    
    .detail-item {
        margin-bottom: 8px;
        padding: 5px 0;
    }
    
    .role-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .role-admin {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    
    .role-student {
        background-color: #e8f5e8;
        color: #2e7d32;
    }
    
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    }
    
    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .toast-success {
        background-color: #28a745;
    }
    
    .toast-error {
        background-color: #dc3545;
    }
    </style>
</body>
</html>
