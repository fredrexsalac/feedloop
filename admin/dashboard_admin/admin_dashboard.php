<?php
// Set session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging
function log_debug($message) {
    $log_file = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0755, true);
}

// Log session data for debugging
log_debug('Session data: ' . print_r($_SESSION, true));
log_debug('User agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));

require '../../db.php'; // Include database connection
require '../../includes/update_activity.php'; // Include activity tracker

// Check if user is logged in and is an admin (any admin role)
if (!isset($_SESSION['user_id'])) {
    log_debug('User not logged in, redirecting to login');
    header("Location: ../unified_login.php?error=not_logged_in");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    log_debug('Unauthorized access attempt - User ID: ' . ($_SESSION['user_id'] ?? 'unknown'));
    header("Location: ../unified_login.php?error=unauthorized");
    exit();
}

// Update session with current timestamp to prevent early timeout
$_SESSION['last_activity'] = time();

// Admin display name for personalization
$admin_display_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');

// Get admin statistics
$total_admins = 0;
$total_feedback = 0;
$active_sessions = 0;

try {
    // Count total admins
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins");
    $stmt->execute();
    $total_admins = $stmt->fetchColumn();
    
    // FeedLoop v2.0: Count feedback by category instead of total
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN feedback_category = 'Department Feedback' THEN 1 ELSE 0 END) as department_feedback,
        SUM(CASE WHEN feedback_category = 'Instructor Feedback' THEN 1 ELSE 0 END) as instructor_feedback,
        SUM(CASE WHEN feedback_category = 'Event Feedback' THEN 1 ELSE 0 END) as event_feedback,
        SUM(CASE WHEN feedback_category = 'Dean/Office Feedback' THEN 1 ELSE 0 END) as dean_feedback,
        SUM(CASE WHEN feedback_category = 'System Feedback' THEN 1 ELSE 0 END) as system_feedback,
        SUM(CASE WHEN feedback_category = 'Community-Based Issues' THEN 1 ELSE 0 END) as community_feedback,
        COUNT(*) as total_feedback
        FROM feedback_submissions");
    $stmt->execute();
    $feedback_stats = $stmt->fetch();
    $total_feedback = $feedback_stats['total_feedback'] ?? 0;
    
    // Count active sessions from today's activity logs
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE DATE(timestamp) = CURDATE()");
    $stmt->execute();
    $active_sessions = $stmt->fetchColumn();
} catch (Exception $e) {
    // Tables might not exist yet
}

// Get active user sessions (users active in the last 15 minutes)
$active_sessions_list = [];
try {
    // First, update the current user's activity
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_activity = NOW(),
                session_id = ?
            WHERE user_id = ?
        ");
        $stmt->execute([session_id(), $_SESSION['user_id']]);
    }
    
    // Get active sessions (users active in the last 15 minutes)
    $stmt = $pdo->prepare("SELECT 
        u.user_id,
        u.username,
        u.email,
        u.last_activity,
        u.session_id = ? as is_current_session,
        a.full_name,
        'admin' as user_type,
        a.position as admin_position,
        u.last_activity as last_activity_timestamp
        FROM users u
        JOIN admins a ON u.user_id = a.user_id
        WHERE u.role = 'admin' AND u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY 
            CASE 
                WHEN u.user_id = ? THEN 0 
                ELSE 1 
            END, -- Current user first, then other admins
            u.last_activity DESC");
    
    $stmt->execute([session_id(), $_SESSION['user_id']]);
    
    // Debug log
    error_log('Active users in last 5 minutes: ' . count($active_sessions_list));
    $active_sessions_list = $stmt->fetchAll();
    
    // Debug: Log the number of active users found
    error_log('Active users found: ' . count($active_sessions_list));
} catch (Exception $e) {
    // Activity table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FeedLoop</title>
    <?php include '../../includes/favicon.php'; ?>
    <link rel="stylesheet" href="../../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../../assets/css/admin/super_admin_modern.css">
    <link rel="stylesheet" href="../../assets/css/admin/dashboard_icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin/pdf_preview.css">
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/admin/dashboard.js"></script>
    <script src="../../assets/js/admin/settings.js?v=<?php echo time(); ?>"></script>
    
    <!-- Session Guard for Multi-Tab Protection -->
    <script src="../../assets/js/universal_session_guard.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="closeSidebar()"></div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo-container">
                <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo">
            </div>
            <!-- FeedLoop v2.0: Streamlined Admin Menu -->
            <ul class="sidebar-menu">
                <li><a href="#" onclick="loadSuperAdminDashboard()" class="active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li><a href="#" onclick="loadSuperAdminContent('manage_admins')"><i class="fas fa-user-shield me-2"></i>Manage Admins</a></li>
                <li><a href="#" onclick="loadSuperAdminContent('custom_forms')"><i class="fas fa-clipboard-list me-2"></i>Custom Forms</a></li>
                <li><a href="#" onclick="loadSuperAdminContent('view_feedback')"><i class="fas fa-comments me-2"></i>All Feedback</a></li>
                <li><a href="#" onclick="loadSuperAdminContent('settings')"><i class="fas fa-cog me-2"></i>Personal Settings</a></li>
                <li class="menu-divider"></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div id="dynamic-content">
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="d-flex align-items-center">
                            <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop" width="36" height="36" class="me-2 rounded" style="object-fit: cover;">
                            Admin Dashboard
                        </h1>
                        <div class="btn-group">
                            <button class="btn btn-primary" id="exportReportBtn">
                                <i class="fas fa-download me-2"></i>Export Report
                            </button>
                        </div>
                    </div>
                    <div class="welcome-admin">
                        <h2>Welcome, <?php echo $_SESSION['full_name']; ?></h2>
                        <p>You are logged in as: <strong><?php echo $_SESSION['position']; ?></strong></p>
                    </div>
                </div>

                <!-- FeedLoop v2.0: Categorized Feedback Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_admins; ?></div>
                        <div class="stat-label">Total Admins</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_feedback; ?></div>
                        <div class="stat-label">Total Feedback</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_sessions; ?></div>
                        <div class="stat-label">Active Sessions</div>
                    </div>
                </div>

                <!-- Categorized Feedback Breakdown -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Feedback by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-building fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['department_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">Department</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-chalkboard-teacher fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['instructor_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">Instructor</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-calendar-alt fa-2x text-info mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['event_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">Events</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-user-tie fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['dean_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">Dean/Office</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-cog fa-2x text-secondary mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['system_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">System</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-users fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-1"><?php echo $feedback_stats['community_feedback'] ?? 0; ?></h4>
                                    <small class="text-muted">Community</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active User Sessions -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Currently Logged In Users</h5>
                        <div class="last-updated small text-muted">Updating...</div>
                    </div>
                    <div class="card-body p-0">
                        <div id="activeUsersContainer">
                            <!-- Content will be loaded via AJAX -->
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading active users...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Report Button -->
                <div class="text-center mt-4">
                    <button onclick="loadSuperAdminContent('activity_report')" class="btn btn-primary">
                        <i class="fas fa-chart-line me-2"></i>View Activity Report
                    </button>
                </div>
            </div>
        </div>
    </div>

        <!-- PDF Preview Modal - Professional Portfolio Design -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white;">
                    <div>
                        <h4 class="modal-title mb-1">
                            <i class="fas fa-file-pdf me-2"></i>Professional Report Preview
                        </h4>
                        <p class="mb-0 small" style="opacity: 0.9;">Review before downloading</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="background: #e9ecef;">
                    <div id="pdfPreviewContent" style="background: white; margin: 30px; padding: 50px; min-height: 700px;">
                        <div id="loadingPreview" class="text-center py-5">
                            <div class="spinner-border text-warning" style="width: 4rem; height: 4rem;" role="status"></div>
                            <h5 class="mt-4"><strong>Generating Professional Report</strong></h5>
                            <p class="text-muted">Preparing your dashboard summary...</p>
                        </div>
                        <div id="previewDocument" style="display: none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadPdfBtn" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); border: none;">
                        <i class="fas fa-download me-2"></i>Download PDF Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include jsPDF and other required libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
    // Function to update active users table
    function updateActiveUsers() {
        fetch('../api/get_active_users.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                const container = document.getElementById('activeUsersContainer');
                const table = document.getElementById('activeUsersTable');
                
                if (!container || !table) {
                    console.error('Required DOM elements not found');
                    return;
                }
                
                if (data.count === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            No active user sessions found.
                        </div>
                    `;
                    return;
                }
                
                // Clear existing table body
                const tbody = table.querySelector('tbody');
                if (tbody) tbody.innerHTML = '';
                


                // Add each user to the table
                data.users.forEach((user, index) => {
                    const row = document.createElement('tr');
                    
                    // Highlight current user
                    if (user.is_current_session) {
                        row.classList.add('table-primary');
                    }
                    
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${escapeHtml(user.username)}</td>
                        <td>${escapeHtml(user.role)}</td>
                        <td>${new Date(user.last_activity).toLocaleString()}</td>
                    `;
                    
                    if (tbody) tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error fetching active users:', error);
                const errorMsg = `Error: ${error.message}`;
                console.error('Full error:', error);
                const container = document.getElementById('activeUsersContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            ${errorMsg}
                        </div>
                    `;
                }
            });
    }

    // Global error handler for undefined functions
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('Global error:', { message, source, lineno, colno, error });
        return false; // Let the default error handler also run
    };

    // Catch undefined function calls
    window.onunhandledrejection = function(event) {
        console.error('Unhandled rejection (promise): ', event.reason);
    };


    // Initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
                // Attach export button handler directly
        const exportBtn = document.getElementById('exportReportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', async function() {
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
                modal.show();
                
                // Generate preview
                setTimeout(async () => {
                    await generatePdfPreview();
                }, 500);
            });
        }
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
                        // Professional PDF Export with Preview
                        async function exportDashboardReport() {
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
                    modal.show();
                    
                    // Generate preview
                    setTimeout(async () => {
                        await generatePdfPreview();
                    }, 500);
                }

                async function generatePdfPreview() {
                    const previewDoc = document.getElementById('previewDocument');
                    const loading = document.getElementById('loadingPreview');
                    
                    try {
                        const stats = {
                            admins: document.querySelector('.stats-grid .stat-card:nth-child(1) .stat-number')?.textContent || '0',
                            feedback: document.querySelector('.stats-grid .stat-card:nth-child(2) .stat-number')?.textContent || '0',
                            sessions: document.querySelector('.stats-grid .stat-card:nth-child(3) .stat-number')?.textContent || '0'
                        };
                        
                        // Build preview HTML
                        const previewHTML = `
                            <div class="preview-header text-center">
                                <img src="../../assets/img/logo/feedloop.jpg" class="preview-logo mb-3" alt="FeedLoop Logo">
                                <h2 class="preview-title">FeedLoop System Report</h2>
                                <p class="preview-subtitle">Admin Dashboard Summary | Generated: ${new Date().toLocaleString()}</p>
                            </div>
                            
                            <div class="preview-section">
                                <h3 class="preview-section-title"><i class="fas fa-chart-bar me-2"></i>System Overview</h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-box">
                                            <div class="stat-box-label">Total Administrators</div>
                                            <div class="stat-box-value">${stats.admins}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-box">
                                            <div class="stat-box-label">Feedback Submissions</div>
                                            <div class="stat-box-value">${stats.feedback}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-box">
                                            <div class="stat-box-label">Active Sessions</div>
                                            <div class="stat-box-value">${stats.sessions}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="preview-section">
                                <h3 class="preview-section-title"><i class="fas fa-users me-2"></i>Active User Sessions</h3>
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewActiveUsers">
                                        <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="preview-footer">
                                <p><strong>© ${new Date().getFullYear()} FeedLoop</strong> - Feedback Management System</p>
                                <p class="small">This is an automated system report generated for administrative purposes.</p>
                            </div>
                        `;
                        
                        previewDoc.innerHTML = previewHTML;
                        
                        // Load active users
                        const activeUsersBody = document.getElementById('previewActiveUsers');
                        const tableRows = document.querySelectorAll('#activeUsersTable tbody tr');
                        if (tableRows.length > 0) {
                            activeUsersBody.innerHTML = '';
                            tableRows.forEach((row, index) => {
                                const cells = row.cells;
                                activeUsersBody.innerHTML += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${cells[1]?.textContent || 'N/A'}</td>
                                        <td>${cells[2]?.textContent || 'N/A'}</td>
                                        <td>${cells[3]?.textContent || 'N/A'}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            activeUsersBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No active sessions</td></tr>';
                        }
                        
                        // Show preview
                        loading.style.display = 'none';
                        previewDoc.style.display = 'block';
                        
                    } catch (error) {
                        console.error('Error generating preview:', error);
                        previewDoc.innerHTML = '<div class="alert alert-danger">Error generating preview</div>';
                        loading.style.display = 'none';
                        previewDoc.style.display = 'block';
                    }
                }

                // Download PDF from preview
                document.getElementById('downloadPdfBtn')?.addEventListener('click', async function() {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4'
                    });

                    try {
                        // Add logo
                        const logoUrl = '../../assets/img/logo/feedloop.jpg';
                        const logoResponse = await fetch(logoUrl);
                        const logoBlob = await logoResponse.blob();
                        const logoDataUrl = await new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.readAsDataURL(logoBlob);
                        });
                        doc.addImage(logoDataUrl, 'JPEG', 85, 10, 40, 20);
                    } catch (e) {
                        console.error('Error loading logo:', e);
                    }

                    // Add title
                    doc.setFontSize(24);
                    doc.setTextColor(253, 126, 20);
                    doc.setFont('helvetica', 'bold');
                    doc.text('FeedLoop System Report', 105, 40, { align: 'center' });
                    
                    doc.setFontSize(11);
                    doc.setTextColor(127, 140, 141);
                    doc.setFont('helvetica', 'normal');
                    doc.text('Admin Dashboard Summary', 105, 47, { align: 'center' });
                    doc.text(`Generated: ${new Date().toLocaleString()}`, 105, 53, { align: 'center' });

                    // Add line separator
                    doc.setDrawColor(253, 126, 20);
                    doc.setLineWidth(0.5);
                    doc.line(20, 58, 190, 58);

                    const stats = {
                        admins: document.querySelector('.stats-grid .stat-card:nth-child(1) .stat-number')?.textContent || '0',
                        feedback: document.querySelector('.stats-grid .stat-card:nth-child(2) .stat-number')?.textContent || '0',
                        sessions: document.querySelector('.stats-grid .stat-card:nth-child(3) .stat-number')?.textContent || '0'
                    };

                    // Add System Overview section
                    doc.setFontSize(16);
                    doc.setTextColor(52, 73, 94);
                    doc.setFont('helvetica', 'bold');
                    doc.text('System Overview', 20, 70);

                    // Add statistics boxes
                    let y = 80;
                    const statsData = [
                        { label: 'Total Administrators', value: stats.admins },
                        { label: 'Feedback Submissions', value: stats.feedback },
                        { label: 'Active Sessions', value: stats.sessions }
                    ];

                    statsData.forEach((stat, index) => {
                        const x = index % 2 === 0 ? 20 : 110;
                        if (index % 2 === 0 && index > 0) y += 35;

                        // Draw stat box
                        doc.setFillColor(255, 245, 230);
                        doc.roundedRect(x, y, 85, 30, 3, 3, 'F');
                        doc.setDrawColor(253, 126, 20);
                        doc.setLineWidth(0.5);
                        doc.line(x, y, x, y + 30);

                        // Add label
                        doc.setFontSize(9);
                        doc.setTextColor(127, 140, 141);
                        doc.setFont('helvetica', 'normal');
                        doc.text(stat.label, x + 5, y + 10);

                        // Add value
                        doc.setFontSize(20);
                        doc.setTextColor(253, 126, 20);
                        doc.setFont('helvetica', 'bold');
                        doc.text(stat.value, x + 5, y + 23);
                    });

                    // Add Active Users section
                    y += 50;
                    doc.setFontSize(16);
                    doc.setTextColor(52, 73, 94);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Active User Sessions', 20, y);

                    // Get active users data
                    const activeUsers = [];
                    const activeUsersContainer = document.getElementById('activeUsersContainer');
                    if (activeUsersContainer) {
                        const tableRows = activeUsersContainer.querySelectorAll('table tbody tr');
                        tableRows.forEach(row => {
                            const cells = row.cells;
                            if (cells && cells.length >= 4) {
                                activeUsers.push([
                                    cells[0]?.textContent?.trim() || '',
                                    cells[1]?.textContent?.trim() || '',
                                    cells[2]?.textContent?.trim() || '',
                                    cells[3]?.textContent?.trim() || ''
                                ]);
                            }
                        });
                    }

                    // Add table
                    doc.autoTable({
                        head: [['Users', 'Username', 'Role', 'Last Activity']],
                        body: activeUsers.length > 0 ? activeUsers : [['', 'No active sessions', '', '']],
                        startY: y + 5,
                        theme: 'grid',
                        headStyles: {
                            fillColor: [253, 126, 20],
                            textColor: 255,
                            fontStyle: 'bold',
                            fontSize: 10
                        },
                        bodyStyles: {
                            fontSize: 9,
                            cellPadding: 3
                        },
                        alternateRowStyles: {
                            fillColor: [248, 249, 250]
                        }
                    });

                    // Add footer
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(9);
                        doc.setTextColor(149, 165, 166);
                        doc.text(
                            `© ${new Date().getFullYear()} FeedLoop - Feedback Management System`,
                            105,
                            285,
                            { align: 'center' }
                        );
                        doc.text(
                            `Page ${i} of ${pageCount}`,
                            190,
                            285,
                            { align: 'right' }
                        );
                    }

                    // Save the PDF
                    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
                    doc.save(`SuperAdmin_Report_${timestamp}.pdf`);
                    
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('pdfPreviewModal')).hide();
                    
                    // Show success message
                    alert('PDF Report downloaded successfully!');
                });
        // Update active users immediately and then every 10 seconds
        updateActiveUsers();
        setInterval(updateActiveUsers, 10000);

        // Log all buttons with click handlers
        document.querySelectorAll('button, a, input[type="button"], input[type="submit"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                console.log('Button clicked:', {
                    id: this.id,
                    className: this.className,
                    text: this.textContent.trim(),
                    onclick: this.onclick ? this.onclick.toString() : null,
                    element: this.outerHTML
                });
            }, true);
        });
    });
    
    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Real-time active users update
    function updateActiveUsers() {
        const container = document.getElementById('activeUsersContainer');
        const lastUpdatedElement = document.querySelector('.last-updated');
        
        if (!container) {
            return;
        }
        
        // Show loading state
        container.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading active users...</p>
            </div>`;
            
        if (lastUpdatedElement) {
            lastUpdatedElement.textContent = 'Updating...';
        }
        
        fetch('../api/get_active_users.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                
                if (lastUpdatedElement) {
                    lastUpdatedElement.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
                }
                
                if (!data || !data.success) {
                    throw new Error(data?.message || 'Failed to load active users');
                }
                
                if (data.count === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active user sessions found.</p>
                        </div>`;
                    return;
                }
                
                // Create table structure
                let html = `
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody id="activeUsersList"></tbody>
                        </table>
                    </div>`;
                
                container.innerHTML = html;
                const tbody = document.getElementById('activeUsersList');
                
                if (!tbody) {
                    throw new Error('Failed to create table body');
                }
                
                // Add each user
                data.users.forEach(user => {
                    try {
                        const row = document.createElement('tr');
                        
                        // Highlight current user
                        if (user.is_current_session) {
                            row.classList.add('table-primary');
                        }
                        
                        // User info cell
                        const userCell = document.createElement('td');
                        userCell.innerHTML = `
                            <div class="d-flex align-items-center">
                                ${user.is_current_session ? '<span class="badge bg-primary me-2">You</span>' : ''}
                                <div>
                                    <div class="fw-bold">${escapeHtml(user.full_name || user.username || 'Unknown User')}</div>
                                    <div class="text-muted small">@${escapeHtml(user.username || 'unknown')}</div>
                                </div>
                            </div>
                        `;
                        
                        // User type cell
                        const typeCell = document.createElement('td');
                        const typeBadge = document.createElement('span');
                        const userType = user.user_type || 'unknown';
                        typeBadge.className = `badge bg-${userType === 'admin' ? 'primary' : 'info'}`;
                        typeBadge.textContent = user.admin_position || 'Admin';
                        typeCell.appendChild(typeBadge);
                        
                        // Status cell
                        const statusCell = document.createElement('td');
                        const lastActivity = user.last_activity ? new Date(user.last_activity).getTime() : Date.now();
                        const minutesAgo = Math.floor((Date.now() - lastActivity) / (1000 * 60));
                        
                        let statusClass = 'secondary';
                        let statusText = userType === 'admin' ? (user.admin_position || 'Admin') : 'User';
                        
                        if (userType === 'admin') {
                            if (user.admin_position === 'Super Admin') {
                                statusClass = 'warning';
                                statusText = 'Admin';
                            } else {
                                statusClass = 'success';
                            }
                            
                            if (minutesAgo < 5) statusText += ' (Active)';
                            else if (minutesAgo < 15) statusText += ' (Away)';
                            else statusText += ' (Offline)';
                        } else if (minutesAgo < 5) {
                            statusText += ' (Active)';
                        }
                        
                        const statusBadge = document.createElement('span');
                        statusBadge.className = `badge bg-${statusClass}`;
                        statusBadge.textContent = statusText;
                        statusCell.appendChild(statusBadge);
                        
                        // Role cell
                        const roleCell = document.createElement('td');
                        roleCell.textContent = 'Administrator';
                        
                        // Append cells to row
                        row.appendChild(userCell);
                        row.appendChild(typeCell);
                        row.appendChild(statusCell);
                        row.appendChild(roleCell);
                        
                        tbody.appendChild(row);
                    } catch (userError) {
                        console.error('Error rendering user row:', userError);
                    }
                });
                
            })
            .catch(error => {
                console.error('Error fetching active users:', error);
                const errorMsg = `Error: ${error.message || 'Failed to load active users'}`;
                
                container.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${errorMsg}
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateActiveUsers()">
                                <i class="fas fa-sync-alt me-1"></i> Retry
                            </button>
                        </div>
                    </div>`;
                
                if (lastUpdatedElement) {
                    lastUpdatedElement.textContent = 'Update failed';
                }
            });
    }
    
        // Update active users if container exists
        let activeUsersInterval = null;

        function initActiveUsers() {
            const container = document.getElementById('activeUsersContainer');
            if (container) {
                updateActiveUsers();
                // Clear any existing interval first
                if (activeUsersInterval) {
                    clearInterval(activeUsersInterval);
                }
                // Set new interval
                activeUsersInterval = setInterval(() => {
                    // Check if container still exists before updating
                    if (document.getElementById('activeUsersContainer')) {
                        updateActiveUsers();
                    } else {
                        // Container gone, clear interval
                        clearInterval(activeUsersInterval);
                        activeUsersInterval = null;
                    }
                }, 10000);
            } else {
                // Not on dashboard page or container not available - stop trying
                console.log('activeUsersContainer not found - likely on different page, stopping retries');
                return;
            }
        }
    
    // Initialize active users
    initActiveUsers();
    
    // FeedLoop v2.0: Admin Navigation State Management
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof loadNavigationState === 'function') {
                const savedState = loadNavigationState();
                if (savedState && savedState.page !== 'dashboard') {
                    console.log('Admin: Restoring navigation state:', savedState);
                    
                    // Restore Admin page
                    const pageLinks = document.querySelectorAll('.sidebar-menu a');
                    pageLinks.forEach(link => {
                        const onclick = link.getAttribute('onclick');
                        if (onclick && onclick.includes(savedState.page)) {
                            link.classList.add('active');
                            if (onclick.includes('loadSuperAdminContent')) {
                                loadSuperAdminContent(savedState.page);
                            }
                        }
                    });
                }
            }
        }, 500);
    });
    </script>
    <div class="modal fade" id="adminTutorialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Welcome, <?php echo htmlspecialchars($admin_display_name); ?> 👋</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Here’s a quick tour of the Admin Dashboard to help you get started.</p>
                    <ol class="ms-3">
                        <li class="mb-2"><strong>Dashboard Overview</strong>: View system stats and recent activity.</li>
                        <li class="mb-2"><strong>Manage Users</strong>: Add or edit admins and manage access.</li>
                        <li class="mb-2"><strong>Create Forms</strong>: Build surveys, feedback forms, and announcements.</li>
                        <li class="mb-2"><strong>Analytics & Reports</strong>: Generate insights and export reports.</li>
                        <li class="mb-2"><strong>Active Sessions</strong>: Monitor who’s currently active.</li>
                        <li class="mb-2"><strong>Settings</strong>: Configure preferences and system options.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="adminTutorialGotIt">Got it</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/admin/user_management.js"></script>
    <script>
    (function(){
        try {
            var adminId = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
            var key = 'tutorial_shown_admin_' + adminId;
            if (!adminId) return;
            if (localStorage.getItem(key)) return;
            var el = document.getElementById('adminTutorialModal');
            if (!el) return;
            var modal = new bootstrap.Modal(el);
            modal.show();
            var btn = document.getElementById('adminTutorialGotIt');
            if (btn) {
                btn.addEventListener('click', function(){
                    localStorage.setItem(key, '1');
                    modal.hide();
                });
            }
        } catch (e) { /* noop */ }
    })();
    </script>
    </body>
    </html>
