<?php
session_start();
require '../db.php';

// Check if user is logged in (unified session variables)
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$user_id = $is_logged_in ? ($_SESSION['user_id'] ?? 0) : 0;
$user_name = $is_logged_in ? ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') : 'Guest';
$user_email = $is_logged_in ? ($_SESSION['email'] ?? '') : '';
$profile_pic = '';

// Fetch user profile picture from database if logged in
if ($is_logged_in && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, profile_pic FROM users WHERE user_id = ? AND role = 'user'");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        if ($user_data) {
            // Use database full_name if available, otherwise fall back to session
            $user_name = !empty($user_data['full_name']) ? $user_data['full_name'] : $user_name;
            $profile_pic = $user_data['profile_pic'] ?? '';
        }
    } catch (Exception $e) {
        // Keep session values if database query fails
    }
}

// Get unread notifications count
$unread_notifications = 0;
if ($is_logged_in && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_notifications = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Table might not exist yet
        $unread_notifications = 0;
    }
}

// Get published forms/announcements from admins
$announcements = [];
$total_announcements = 0;
$page = $_GET['page'] ?? 1;
$limit = 6; // Show 6 announcements per page
$offset = ($page - 1) * $limit;

try {
    // Build WHERE clause to exclude dismissed announcements for logged-in users
    $dismissed_clause = "";
    $count_params = [];
    $query_params = [];
    
    if ($is_logged_in && $user_id > 0) {
        $dismissed_clause = " AND cf.form_id NOT IN (
            SELECT form_id FROM user_dismissed_announcements WHERE user_id = ?
        )";
        $count_params = [$user_id];
        $query_params = [$user_id];
    }
    
    // Get total count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        WHERE cf.is_active = 1 
        AND cf.visibility IN ('public', 'department')
        $dismissed_clause
    ");
    $stmt->execute($count_params);
    $total_announcements = $stmt->fetchColumn();
    
    // Get announcements with admin details
    $query_params[] = $limit;
    $query_params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            cf.form_id,
            cf.title,
            cf.description,
            cf.created_at,
            cf.updated_at,
            CASE 
                WHEN cf.description LIKE '%survey%' OR cf.title LIKE '%survey%' THEN 'survey'
                WHEN cf.description LIKE '%feedback%' OR cf.title LIKE '%feedback%' THEN 'feedback'
                WHEN cf.description LIKE '%event%' OR cf.title LIKE '%event%' THEN 'event'
                ELSE 'announcement'
            END as form_type,
            cf.max_responses,
            u.username as admin_username,
            COALESCE(a.full_name, u.username) as admin_name,
            a.position as admin_position,
            cf.response_count
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE cf.is_active = 1 
        AND cf.visibility IN ('public', 'department')
        $dismissed_clause
        ORDER BY cf.updated_at DESC, cf.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($query_params);
    $announcements = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Error loading announcements: " . $e->getMessage();
    $total_announcements = 0;
    $announcements = [];
}

$total_pages = ceil($total_announcements / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FeedLoop - Forms</title>
    <?php include '../includes/favicon.php'; ?>
    
    <!-- Bootstrap CSS -->
    <link href="../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link rel="stylesheet" href="../assets/css/themes.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/public/user_portal.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop" height="40" class="me-2">
                <span class="fw-bold text-primary">FeedLoop</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">
                            <i class="fas fa-info-circle me-1"></i>Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="user_portal.php">
                            <i class="fas fa-clipboard-list me-1"></i>Forms
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifications
                            <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                    <!-- Settings Dropdown -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">User Settings</h6></li>
                            <li><a class="dropdown-item" href="user_profile.php"><i class="fas fa-user me-2"></i>Profile Settings</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Theme Settings</h6></li>
                            <li>
                                <div class="px-3 py-2">
                                    <div class="form-check">
                                        <input class="form-check-input theme-option" type="radio" name="themeOption" id="lightTheme" value="light" checked>
                                        <label class="form-check-label" for="lightTheme">Light Mode</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input theme-option" type="radio" name="themeOption" id="darkTheme" value="dark">
                                        <label class="form-check-label" for="darkTheme">Dark Mode</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input theme-option" type="radio" name="themeOption" id="animatedTheme" value="animated">
                                        <label class="form-check-label" for="animatedTheme">Animated Mix</label>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- User Dropdown with Profile Picture -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (!empty($profile_pic)): ?>
                                <?php $clean_pic = str_replace('../', '', $profile_pic); ?>
                                <img src="../<?php echo $clean_pic; ?>" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #007bff;">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user_profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success' && $is_logged_in): ?>
        <!-- Welcome Alert for New Users -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Welcome to FeedLoop, <?php echo htmlspecialchars($user_name); ?>!</h4>
            <p class="mb-0">Your account has been successfully created and verified. You can now access all announcements and participate in surveys.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-header">
                    <h1 class="display-6 fw-bold text-primary">
                        <i class="fas fa-clipboard-list me-3"></i>
                        Available Forms<?php echo $is_logged_in ? ' for ' . htmlspecialchars($user_name) : ''; ?>
                    </h1>
                    <p class="lead text-muted">
                        Participate in surveys, feedback forms, and other forms published by administrators.
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_announcements; ?></h3>
                        <p>Available Forms</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $is_logged_in ? 'Registered' : 'Guest'; ?></h3>
                        <p>Account Status</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo date('M j'); ?></h3>
                        <p>Today's Date</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Section -->
        <div class="row">
            <div class="col-12">
                <div class="section-header d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        Published Forms
                    </h2>
                    <div class="filter-options">
                        <select class="form-select form-select-sm" id="filterType" onchange="filterAnnouncements()">
                            <option value="all">All Types</option>
                            <option value="announcement">Announcements</option>
                            <option value="survey">Surveys</option>
                            <option value="feedback">Feedback Forms</option>
                            <option value="event">Events</option>
                        </select>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <?php if (empty($announcements)): ?>
                <div class="empty-state text-center py-5">
                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">No Forms Available</h3>
                    <p class="text-muted">Check back later for new forms from administrators.</p>
                </div>
                <?php else: ?>
                
                <!-- Announcements Grid -->
                <div class="row" id="announcementsGrid">
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="col-lg-6 col-xl-4 mb-4" data-type="<?php echo htmlspecialchars($announcement['form_type']); ?>" id="announcement-<?php echo $announcement['form_id']; ?>">
                        <div class="announcement-card position-relative">
                            <?php if ($is_logged_in): ?>
                            <button class="btn-close position-absolute top-0 end-0 m-2" 
                                    onclick="dismissAnnouncement(<?php echo $announcement['form_id']; ?>)"
                                    title="Dismiss this announcement"
                                    style="z-index: 10;"></button>
                            <?php endif; ?>
                            
                            <div class="announcement-header">
                                <div class="announcement-type">
                                    <?php
                                    $type_icons = [
                                        'announcement' => 'fas fa-bullhorn',
                                        'survey' => 'fas fa-poll',
                                        'feedback' => 'fas fa-comments',
                                        'event' => 'fas fa-calendar-alt'
                                    ];
                                    $icon = $type_icons[$announcement['form_type']] ?? 'fas fa-file-alt';
                                    ?>
                                    <i class="<?php echo $icon; ?> me-2"></i>
                                    <?php echo ucfirst($announcement['form_type']); ?>
                                </div>
                                <div class="announcement-date">
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="announcement-content">
                                <h5 class="announcement-title">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </h5>
                                
                                <?php if ($announcement['description']): ?>
                                <p class="announcement-description">
                                    <?php echo htmlspecialchars(substr($announcement['description'], 0, 120)); ?>
                                    <?php if (strlen($announcement['description']) > 120): ?>...<?php endif; ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="announcement-meta">
                                    <div class="admin-info">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <small>
                                            <?php echo htmlspecialchars($announcement['admin_name']); ?>
                                            <?php if ($announcement['admin_position']): ?>
                                                <span class="text-muted">(<?php echo htmlspecialchars($announcement['admin_position']); ?>)</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($announcement['response_count'] > 0): ?>
                                    <div class="response-count">
                                        <i class="fas fa-users me-1"></i>
                                        <small><?php echo $announcement['response_count']; ?> responses</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="announcement-actions">
                                <button class="btn btn-primary btn-sm" onclick="viewAnnouncement(<?php echo $announcement['form_id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                                
                                <?php if (in_array($announcement['form_type'], ['survey', 'feedback'])): ?>
                                <button class="btn btn-outline-success btn-sm" onclick="participateInForm(<?php echo $announcement['form_id']; ?>)">
                                    <i class="fas fa-hand-point-right me-1"></i>Participate
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Announcements pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Announcement Detail Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Announcement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="announcementModalBody">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="participateBtn" style="display: none;">
                        <i class="fas fa-hand-point-right me-1"></i>Participate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- First-Time Tutorial Modal -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Welcome, <?php echo htmlspecialchars($user_name); ?> 👋</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Here’s a quick tour of your FeedLoop portal. You can revisit these features anytime from the navigation bar.</p>
                    <ol class="ms-3">
                        <li class="mb-2"><strong>Announcements</strong>: View updates from admins about events, surveys, and news.</li>
                        <li class="mb-2"><strong>Forms</strong>: See all available surveys and feedback forms. Click View Details to participate.</li>
                        <li class="mb-2"><strong>Profile</strong>: Update your name, email, and profile picture.</li>
                        <li class="mb-2"><strong>Notifications</strong>: Track form invitations and important updates.</li>
                        <li class="mb-2"><strong>Theme</strong>: Switch between Light, Dark, or Animated themes from Settings.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="tutorialGotIt">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Switcher JS -->
    <script src="../assets/js/theme-switcher.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/public/user_portal.js"></script>
    <script>
    (function(){
        var isLogged = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        if (!isLogged) return;
        var userId = <?php echo (int)$user_id; ?>;
        var role = 'user';
        var key = 'tutorial_shown_' + role + '_' + userId;
        if (localStorage.getItem(key)) return;
        var modalEl = document.getElementById('tutorialModal');
        if (!modalEl) return;
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
        var btn = document.getElementById('tutorialGotIt');
        if (btn) {
            btn.addEventListener('click', function(){
                localStorage.setItem(key, '1');
                modal.hide();
            });
        }
    })();
    </script>
</body>
</html>
