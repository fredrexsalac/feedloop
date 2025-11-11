<?php
require_once '../db.php';
require_once '../includes/activity_logger.php';

session_start();

// Check if user is logged in and is admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: ../login/unified_login.php');
    exit();
}

$pageTitle = 'Activity Log';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .activity-log {
            max-height: 70vh;
            overflow-y: auto;
            padding: 15px;
        }
        .activity-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .activity-item:hover {
            transform: translateX(5px);
        }
        .activity-item.login { border-left-color: #28a745; }
        .activity-item.logout { border-left-color: #dc3545; }
        .activity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .activity-user {
            font-weight: 600;
            color: #333;
        }
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .activity-details {
            font-size: 0.9rem;
            color: #495057;
        }
        .activity-meta {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 8px;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .last-updated {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-bottom: 10px;
        }
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history me-2"></i>Activity Logs</h2>
            <div class="last-updated" id="lastUpdated">Last updated: Just now</div>
        </div>
        
        <div class="card">
            <div class="card-body p-0">
                <div class="activity-log" id="activityLog">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading activities...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="btn btn-primary refresh-btn" onclick="loadActivities()" title="Refresh" id="refreshButton">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <script>
        // Load activities when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadActivities();
            // Refresh every 30 seconds
            setInterval(loadActivities, 30000);
        });

        function loadActivities() {
            const refreshBtn = document.getElementById('refreshButton');
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            fetch('api/get_activities.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'error') {
                        throw new Error(data.message);
                    }
                    
                    const activityLog = document.getElementById('activityLog');
                    
                    if (data.data.length === 0) {
                        activityLog.innerHTML = '<div class="p-4 text-center text-muted">No activities found</div>';
                        return;
                    }
                    
                    let html = '';
                    data.data.forEach(activity => {
                        const actionClass = activity.action === 'login' ? 'login' : 'logout';
                        const actionIcon = activity.action === 'login' ? 'sign-in-alt' : 'sign-out-alt';
                        
                        html += `
                        <div class="activity-item ${actionClass}">
                            <div class="activity-header">
                                <div class="activity-user">
                                    <i class="fas fa-${actionIcon} me-2"></i>
                                    ${activity.username} <span class="badge bg-secondary">${activity.role}</span>
                                </div>
                                <div class="activity-time">${activity.timestamp}</div>
                            </div>
                            <div class="activity-details">
                                ${activity.details || 'No additional details'}
                            </div>
                            <div class="activity-meta">
                                <small>
                                    <i class="fas fa-user-tag me-1"></i> ${activity.position} • 
                                    <i class="fas fa-globe me-1"></i> ${activity.ip} • 
                                    <i class="fas fa-desktop me-1"></i> ${activity.device}
                                </small>
                            </div>
                        </div>`;
                    });
                    
                    activityLog.innerHTML = html;
                    document.getElementById('lastUpdated').textContent = 
                        `Last updated: ${new Date().toLocaleTimeString()}`;
                })
                .catch(error => {
                    console.error('Error loading activities:', error);
                    document.getElementById('activityLog').innerHTML = `
                        <div class="alert alert-danger m-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load activities. ${error.message}
                        </div>`;
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                });
        }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
