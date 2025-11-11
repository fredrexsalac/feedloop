<?php
/**
 * View Custom Form Responses
 * Displays all responses for a specific custom form with analytics
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../admin_login.php');
    exit();
}

// Include database connection
require_once '../../../db.php';

// Get form ID
$form_id = $_GET['form_id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    header('Location: ../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms');
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get form details and verify permissions
    $stmt = $pdo->prepare("
        SELECT cf.*, a.position AS creator_position,
               COALESCE(a.full_name, u.username) as creator_name
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions
    $current_user_stmt = $pdo->prepare("SELECT a.position FROM users u LEFT JOIN admins a ON u.user_id = a.user_id WHERE u.user_id = ?");
    $current_user_stmt->execute([$user_id]);
    $current_user = $current_user_stmt->fetch();
    
    if ($form['created_by'] != $user_id && $current_user['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to view responses for this form');
    }
    
    // Get form questions
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$form_id]);
    $questions = $stmt->fetchAll();
    
    // Get form responses
    $stmt = $pdo->prepare("
        SELECT fr.*, 
               GROUP_CONCAT(
                   CONCAT(fq.question_text, ':|:', COALESCE(fa.answer_text, 'No answer'))
                   ORDER BY fq.question_order 
                   SEPARATOR '|~|'
               ) as all_answers
        FROM form_responses fr
        LEFT JOIN form_answers fa ON fr.response_id = fa.response_id
        LEFT JOIN form_questions fq ON fa.question_id = fq.question_id
        WHERE fr.form_id = ?
        GROUP BY fr.response_id
        ORDER BY fr.submitted_at DESC
    ");
    $stmt->execute([$form_id]);
    $responses = $stmt->fetchAll();
    
    // Calculate statistics
    $total_responses = count($responses);
    $completion_rate = $form['max_responses'] ? min(100, ($total_responses / $form['max_responses']) * 100) : 0;
    
    // Get response analytics by date
    $stmt = $pdo->prepare("
        SELECT DATE(submitted_at) as response_date, COUNT(*) as daily_count
        FROM form_responses 
        WHERE form_id = ?
        GROUP BY DATE(submitted_at)
        ORDER BY response_date DESC
        LIMIT 30
    ");
    $stmt->execute([$form_id]);
    $daily_analytics = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Responses - <?php echo htmlspecialchars($form['title'] ?? 'Unknown Form'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="../../../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .response-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .question-answer {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Forms
            </a>
        <?php else: ?>
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">
                                <i class="fas fa-chart-bar me-2 text-primary"></i>
                                Form Responses
                            </h1>
                            <h2 class="h5 text-muted mb-0"><?php echo htmlspecialchars($form['title']); ?></h2>
                            <small class="text-muted">Created by <?php echo htmlspecialchars($form['creator_name']); ?></small>
                        </div>
                        <div>
                            <a href="../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Forms
                            </a>
                            <button class="btn btn-primary" onclick="exportResponses()">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3 class="mb-0"><?php echo $total_responses; ?></h3>
                            <small>Total Responses</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-questions fa-2x mb-2"></i>
                            <h3 class="mb-0"><?php echo count($questions); ?></h3>
                            <small>Total Questions</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-2"></i>
                            <h3 class="mb-0"><?php echo number_format($completion_rate, 1); ?>%</h3>
                            <small>Completion Rate</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar fa-2x mb-2"></i>
                            <h3 class="mb-0"><?php echo date('M j', strtotime($form['created_at'])); ?></h3>
                            <small>Created Date</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Chart -->
            <?php if (!empty($daily_analytics)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-line me-2"></i>
                            Response Trends (Last 30 Days)
                        </h5>
                        <canvas id="responseChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Responses List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Individual Responses (<?php echo $total_responses; ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($responses)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Responses Yet</h5>
                                    <p class="text-muted">This form hasn't received any responses yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($responses as $index => $response): ?>
                                    <div class="response-card card">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user me-2"></i>
                                                    Response #<?php echo $response['response_id']; ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($response['submitted_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php 
                                            if ($response['all_answers']) {
                                                $answers = explode('|~|', $response['all_answers']);
                                                foreach ($answers as $answer) {
                                                    if (strpos($answer, ':|:') !== false) {
                                                        list($question, $answer_text) = explode(':|:', $answer, 2);
                                                        echo '<div class="question-answer">';
                                                        echo '<strong>' . htmlspecialchars($question) . '</strong><br>';
                                                        echo '<span class="text-muted">' . htmlspecialchars($answer_text) . '</span>';
                                                        echo '</div>';
                                                    }
                                                }
                                            } else {
                                                echo '<p class="text-muted">No answers recorded for this response.</p>';
                                            }
                                            ?>
                                            
                                            <div class="mt-3 pt-3 border-top">
                                                <small class="text-muted">
                                                    <i class="fas fa-globe me-1"></i>
                                                    IP: <?php echo htmlspecialchars($response['ip_address'] ?? 'Unknown'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../../../assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart for response trends
        <?php if (!empty($daily_analytics)): ?>
        const ctx = document.getElementById('responseChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('M j', strtotime($item['response_date'])) . '"'; }, array_reverse($daily_analytics))); ?>],
                datasets: [{
                    label: 'Daily Responses',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['daily_count']; }, array_reverse($daily_analytics))); ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        function exportResponses() {
            // Trigger export functionality
            const formId = <?php echo $form_id; ?>;
            const exportUrl = `../../api/custom_forms/export_form_data.php?form_id=${formId}&format=csv&include_responses=true&include_timestamps=true`;
            window.open(exportUrl, '_blank');
        }
    </script>
</body>
</html>
