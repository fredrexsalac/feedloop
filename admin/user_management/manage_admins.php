<?php
session_start();
require '../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Get admin details from session or database
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['position'])) {
    // Fetch admin details from database
    $stmt = $pdo->prepare("SELECT admin_id, position FROM admins WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_data = $stmt->fetch();
    
    if ($admin_data) {
        $_SESSION['admin_id'] = $admin_data['admin_id'];
        $_SESSION['position'] = $admin_data['position'];
    } else {
        header("Location: ../../login/unified_login.php");
        exit();
    }
}

// Check if user has permission to manage admins (only Super Admin)
if ($_SESSION['position'] !== 'Super Admin') {
    header("Location: ../dashboard.php?error=insufficient_permissions");
    exit();
}

// Handle admin actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_admin':
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $full_name = $_POST['full_name'];
                $position = $_POST['position'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Check if username or email exists
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                    $check_stmt->execute([$username, $email]);
                    if ($check_stmt->fetchColumn() > 0) {
                        throw new Exception("Username or email already exists");
                    }
                    
                    // Insert into users table
                    $user_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $user_stmt->execute([$username, $email, $password]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert into admins table
                    $admin_stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
                    $admin_stmt->execute([$user_id, $full_name, $position]);
                    
                    $pdo->commit();
                    $message = "Admin added successfully!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'edit_admin':
                $admin_id = $_POST['admin_id'];
                $full_name = $_POST['full_name'];
                $position = $_POST['position'];
                $email = $_POST['email'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Get user_id
                    $user_stmt = $pdo->prepare("SELECT user_id FROM admins WHERE admin_id = ?");
                    $user_stmt->execute([$admin_id]);
                    $user_id = $user_stmt->fetchColumn();
                    
                    // Update users table
                    $update_user = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                    $update_user->execute([$email, $user_id]);
                    
                    // Update admins table
                    $update_admin = $pdo->prepare("UPDATE admins SET full_name = ?, position = ? WHERE admin_id = ?");
                    $update_admin->execute([$full_name, $position, $admin_id]);
                    
                    $pdo->commit();
                    $message = "Admin updated successfully!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'delete_admin':
                $admin_id = $_POST['admin_id'];
                
                // Prevent deleting self
                if ($admin_id == $_SESSION['admin_id']) {
                    $message = "Cannot delete your own account!";
                    $message_type = "danger";
                    break;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Get user_id
                    $user_stmt = $pdo->prepare("SELECT user_id FROM admins WHERE admin_id = ?");
                    $user_stmt->execute([$admin_id]);
                    $user_id = $user_stmt->fetchColumn();
                    
                    // Delete from admins table
                    $delete_admin = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
                    $delete_admin->execute([$admin_id]);
                    
                    // Delete from users table
                    $delete_user = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $delete_user->execute([$user_id]);
                    
                    $pdo->commit();
                    $message = "Admin deleted successfully!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Fetch all admins with their details
$stmt = $pdo->prepare("SELECT a.admin_id, a.full_name, a.position, u.username, u.email, u.created_at 
                      FROM admins a 
                      JOIN users u ON a.user_id = u.user_id 
                      ORDER BY a.position DESC, a.full_name ASC");
$stmt->execute();
$admins = $stmt->fetchAll();

// Debug: Show count for troubleshooting
if (empty($admins)) {
    // Check if there's a database issue
    $debug_stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
    $debug_count = $debug_stmt->fetchColumn();
    error_log("Manage Admins: No admins found in JOIN query. Direct count: " . $debug_count);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - FeedLoop</title>
    <link rel="stylesheet" href="../../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../../assets/css/admin/admin_regular.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo">
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="../feedback_management/manage_feedback.php">Manage Feedback</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_admins.php" class="active">Manage Admins</a></li>
                <li><a href="../analytics_reports/analytics.php">View Analytics</a></li>
                <li><a href="../analytics_reports/activity_report.php">Activity Report</a></li>
                <li><a href="../analytics_reports/reports.php">Generate Reports</a></li>
                <li><a href="../settings.php">Settings</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Admin Management</h1>
                <p>Manage administrator accounts and permissions</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Admin Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Add New Admin</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_admin">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label for="position" class="form-label">Position</label>
                            <select class="form-control" name="position" required>
                                <option value="">Select Position</option>
                                <option value="Admin">Admin</option>
                                <option value="User Manager">User Manager</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Admin</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Admins List -->
            <div class="card">
                <div class="card-header">
                    <h5>Current Admins (<?php echo count($admins); ?> total)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-info">
                                            <h5>No administrators found</h5>
                                            <p>There are currently no administrators in the system. Use the form above to add the first admin.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['admin_id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $admin['position'] === 'Super Admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo $admin['position']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $admin['admin_id']; ?>">
                                            Edit
                                        </button>
                                        <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $admin['admin_id']; ?>">
                                            Delete
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $admin['admin_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Admin</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit_admin">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Full Name</label>
                                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Position</label>
                                                        <select class="form-control" name="position" required>
                                                            <option value="Admin" <?php echo $admin['position'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                            <option value="User Manager" <?php echo $admin['position'] === 'User Manager' ? 'selected' : ''; ?>>User Manager</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Admin</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $admin['admin_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Admin</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>?</p>
                                                <p class="text-danger">This action cannot be undone!</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_admin">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Delete Admin</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
