<?php
session_start();
require '../../db.php'; // Include database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Get analytics data
$total_feedback = 0;
$pending_feedback = 0;
$resolved_feedback = 0;
$feedback_by_category = [];
$feedback_by_status = [];
$feedback_by_course = [];
$feedback_trends = [];

try {
    // Total feedback count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions");
    $stmt->execute();
    $total_feedback = $stmt->fetchColumn();
    
    // Pending feedback count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE status = 'pending'");
    $stmt->execute();
    $pending_feedback = $stmt->fetchColumn();
    
    // Resolved feedback count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_submissions WHERE status = 'resolved'");
    $stmt->execute();
    $resolved_feedback = $stmt->fetchColumn();
    
    // Feedback by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM feedback_submissions GROUP BY category");
    $stmt->execute();
    $feedback_by_category = $stmt->fetchAll();
    
    // Feedback by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM feedback_submissions GROUP BY status");
    $stmt->execute();
    $feedback_by_status = $stmt->fetchAll();
    
    // Feedback by course
    $stmt = $pdo->prepare("
        SELECT s.course, COUNT(*) as count 
        FROM feedback_submissions fs 
        JOIN students s ON fs.student_id = s.student_id 
        WHERE s.course IS NOT NULL 
        GROUP BY s.course 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $feedback_by_course = $stmt->fetchAll();
    
    // Feedback trends (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM feedback_submissions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $stmt->execute();
    $feedback_trends = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Tables might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - FeedLoop</title>
    <link rel="stylesheet" href="../../assets/css/homepage/bootstrap.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Analytics Dashboard</h1>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_feedback; ?></h5>
                        <p class="card-text">Total Feedback</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $pending_feedback; ?></h5>
                        <p class="card-text">Pending Feedback</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $resolved_feedback; ?></h5>
                        <p class="card-text">Resolved Feedback</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo count($feedback_by_course); ?></h5>
                        <p class="card-text">Courses with Feedback</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Feedback by Course Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Feedback by Course</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="courseChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Feedback by Category Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Feedback by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Charts Row -->
        <div class="row mb-4">
            <!-- Feedback by Status Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Feedback by Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Trends Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Feedback Trends (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Course Feedback Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Detailed Course Feedback Statistics</h5>
            </div>
            <div class="card-body">
                <?php if (count($feedback_by_course) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Total Feedback</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedback_by_course as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course']); ?></td>
                                    <td><?php echo $course['count']; ?></td>
                                    <td><?php echo $total_feedback > 0 ? round(($course['count'] / $total_feedback) * 100, 2) : 0; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No course feedback data available.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Back to Dashboard -->
        <a href="dashboard.php" class="btn btn-secondary mb-4">Back to Dashboard</a>
    </div>

    <script>
    // Course Chart
    const courseCtx = document.getElementById('courseChart').getContext('2d');
    const courseChart = new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(', ', array_map(function($course) { return "'" . addslashes($course['course']) . "'"; }, $feedback_by_course)); ?>],
            datasets: [{
                label: 'Feedback Count',
                data: [<?php echo implode(', ', array_map(function($course) { return $course['count']; }, $feedback_by_course)); ?>],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Feedback by Course'
                }
            }
        }
    });

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: [<?php echo implode(', ', array_map(function($cat) { return "'" . ucfirst($cat['category']) . "'"; }, $feedback_by_category)); ?>],
            datasets: [{
                data: [<?php echo implode(', ', array_map(function($cat) { return $cat['count']; }, $feedback_by_category)); ?>],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
                ]
            }]
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(', ', array_map(function($status) { return "'" . ucfirst(str_replace('_', ' ', $status['status'])) . "'"; }, $feedback_by_status)); ?>],
            datasets: [{
                data: [<?php echo implode(', ', array_map(function($status) { return $status['count']; }, $feedback_by_status)); ?>],
                backgroundColor: [
                    '#FFCE56', '#36A2EB', '#4BC0C0', '#FF6384'
                ]
            }]
        }
    });

    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const trendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(', ', array_map(function($trend) { return "'" . $trend['date'] . "'"; }, $feedback_trends)); ?>],
            datasets: [{
                label: 'Daily Feedback',
                data: [<?php echo implode(', ', array_map(function($trend) { return $trend['count']; }, $feedback_trends)); ?>],
                borderColor: '#36A2EB',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Feedback Trends Over Time'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html>
