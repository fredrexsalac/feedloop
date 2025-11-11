<?php
// Include database connection if not already included
if (!isset($pdo)) {
    require_once '../../../db.php';
}

// Get all users (students and admins) for Super Admin
$users = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email, u.role, u.created_at,
               s.full_name as student_name, s.course, s.year_level, s.section,
               a.full_name as admin_name, a.position
        FROM users u 
        LEFT JOIN students s ON u.user_id = s.user_id 
        LEFT JOIN admins a ON u.user_id = a.user_id 
        WHERE NOT (u.role = 'admin' AND a.position = 'Super Admin')
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>

<div class="dashboard-header">
    <h1>Manage Users</h1>
    <p>View and manage all system users (Admins only)</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo $error; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3>All System Accounts</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <em>No users found.</em>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['student_name'] ?? $user['admin_name'] ?? 'N/A'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small><br>
                                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-student'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'student'): ?>
                                    <strong>Course:</strong> <?php echo htmlspecialchars($user['course'] ?? 'N/A'); ?><br>
                                    <strong>Year:</strong> <?php echo htmlspecialchars($user['year_level'] ?? 'N/A'); ?><br>
                                    <strong>Section:</strong> <?php echo htmlspecialchars($user['section'] ?? 'N/A'); ?>
                                <?php else: ?>
                                    <strong>Position:</strong> <?php echo htmlspecialchars($user['position'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            </td>
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
                                <span class="status-badge <?php echo $is_suspended ? 'status-suspended' : 'status-active'; ?>">
                                    <?php echo $is_suspended ? 'Suspended' : 'Active'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="action-btn view-btn" 
                                            data-user-id="<?php echo $user['user_id']; ?>" 
                                            data-user-name="<?php echo htmlspecialchars($user['student_name'] ?? $user['admin_name'] ?? 'N/A'); ?>" 
                                            data-user-role="<?php echo $user['role']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($is_suspended): ?>
                                        <button type="button" class="action-btn unsuspend-btn" 
                                                data-user-id="<?php echo $user['user_id']; ?>" 
                                                data-user-name="<?php echo htmlspecialchars($user['student_name'] ?? $user['admin_name'] ?? 'N/A'); ?>">
                                            <i class="fas fa-user-check"></i> Unsuspend
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="action-btn suspend-btn" 
                                                data-user-id="<?php echo $user['user_id']; ?>" 
                                                data-user-name="<?php echo htmlspecialchars($user['student_name'] ?? $user['admin_name'] ?? 'N/A'); ?>">
                                            <i class="fas fa-ban"></i> Suspend
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmSuspendBtn">
                    <i class="fas fa-ban me-1"></i> Suspend User
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/admin/user_management.js"></script>
