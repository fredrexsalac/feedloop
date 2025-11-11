<?php
// Get analytics data
$analytics = [];
$registration_trends = [];
$feedback_trends = [];
$feedback_stats = ['total_feedback' => 0];

try {
    // User registration trends (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, role, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at), role 
        ORDER BY date DESC
    ");
    $stmt->execute();
    $registration_trends = $stmt->fetchAll();
    
    // Feedback submission trends (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(submitted_at) as date, COUNT(*) as count 
        FROM feedback_submissions 
        WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(submitted_at) 
        ORDER BY date DESC
    ");
    $stmt->execute();
    $feedback_trends = $stmt->fetchAll();
    
    // Feedback statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_feedback FROM feedback_submissions");
    $stmt->execute();
    $feedback_stats = $stmt->fetch();
    
    // Feedback by category
    $stmt = $pdo->prepare("
        SELECT category, COUNT(*) as count 
        FROM feedback_submissions 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $feedback_by_category = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error fetching analytics: " . $e->getMessage();
    $feedback_by_category = [];
}
?>

<div class="dashboard-header">
    <h1>Analytics Dashboard</h1>
    <p>System usage statistics and trends</p>
</div>

<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo count($registration_trends); ?></div>
        <div class="stat-label">Registration Days (30d)</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($feedback_trends); ?></div>
        <div class="stat-label">Feedback Days (7d)</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $feedback_stats['total_feedback'] ?? 0; ?></div>
        <div class="stat-label">Total Feedback</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($feedback_by_category ?? []); ?></div>
        <div class="stat-label">Feedback Categories</div>
    </div>
</div>

<!-- Registration Trends -->
<div class="card mb-4">
    <div class="card-header">
        <h3>User Registration Trends (Last 30 Days)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Students</th>
                        <th>Admins</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grouped_registrations = [];
                    foreach ($registration_trends as $trend) {
                        $date = $trend['date'];
                        if (!isset($grouped_registrations[$date])) {
                            $grouped_registrations[$date] = ['student' => 0, 'admin' => 0];
                        }
                        $grouped_registrations[$date][$trend['role']] = $trend['count'];
                    }
                    ?>
                    <?php if (empty($grouped_registrations)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <em>No registration data available for the last 30 days.</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grouped_registrations as $date => $data): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                                <td><?php echo $data['student']; ?></td>
                                <td><?php echo $data['admin']; ?></td>
                                <td><strong><?php echo $data['student'] + $data['admin']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Feedback Submission Trends -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Student Feedback Trends (Last 7 Days)</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <canvas id="feedbackTrendsChart" width="400" height="200"></canvas>
            </div>
            <div class="col-md-4">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($feedback_trends)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4">
                                        <em>No data</em>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($feedback_trends as $trend): ?>
                                    <tr>
                                        <td><?php echo date('M j', strtotime($trend['date'])); ?></td>
                                        <td><strong><?php echo $trend['count']; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback by Category -->
<div class="card">
    <div class="card-header">
        <h3>Feedback by Category</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="feedbackCategoryChart" width="300" height="300"></canvas>
            </div>
            <div class="col-md-6">
                <?php if (empty($feedback_by_category)): ?>
                    <div class="text-center py-4">
                        <em>No feedback submissions to categorize yet.</em>
                    </div>
                <?php else: ?>
                    <div class="category-legend">
                        <?php foreach ($feedback_by_category as $index => $category): ?>
                            <?php 
                            $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
                            $color = $colors[$index % count($colors)];
                            $total_feedback = array_sum(array_column($feedback_by_category, 'count'));
                            $percentage = $total_feedback > 0 ? ($category['count'] / $total_feedback) * 100 : 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div style="width: 20px; height: 20px; background-color: <?php echo $color; ?>; border-radius: 3px; margin-right: 10px;"></div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($category['category'] ?? 'Uncategorized'); ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary"><?php echo $category['count']; ?></span>
                                    <br>
                                    <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- External CSS and JS -->
<link rel="stylesheet" href="../../assets/css/admin/analytics.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart Data for JavaScript -->
<script>
// Pass PHP data to JavaScript
window.feedbackTrendsLabels = [
    <?php 
    foreach ($feedback_trends as $trend) {
        echo "'" . $trend['date'] . "',";
    }
    ?>
];

window.feedbackTrendsData = [
    <?php 
    foreach ($feedback_trends as $trend) {
        echo $trend['count'] . ",";
    }
    ?>
];

window.feedbackCategoryLabels = [
    <?php 
    foreach ($feedback_by_category as $category) {
        echo "'" . $category['category'] . "',";
    }
    ?>
];

window.feedbackCategoryData = [
    <?php 
    foreach ($feedback_by_category as $category) {
        echo $category['count'] . ",";
    }
    ?>
];
</script>
<script src="../../assets/js/admin/analytics.js"></script>
