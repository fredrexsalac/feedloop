<?php
session_start();
require '../../db.php';

header('Content-Type: application/json');

// Check if user is logged in admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $analytics = [];
    
    // Basic Statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_feedback FROM feedback_submissions");
    $stmt->execute();
    $analytics['total_feedback'] = $stmt->fetchColumn();
    
    // Feedback by Category
    $stmt = $pdo->prepare("SELECT 
        feedback_category,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM feedback_submissions)), 2) as percentage
        FROM feedback_submissions 
        GROUP BY feedback_category 
        ORDER BY count DESC");
    $stmt->execute();
    $analytics['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Response Rate
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN admin_response IS NOT NULL THEN 1 ELSE 0 END) as responded,
        ROUND((SUM(CASE WHEN admin_response IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as response_rate
        FROM feedback_submissions");
    $stmt->execute();
    $response_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $analytics['response_rate'] = $response_stats;
    
    // Monthly Trends (Last 6 months)
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(CASE WHEN admin_response IS NOT NULL THEN 1 ELSE 0 END) as responded
        FROM feedback_submissions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC");
    $stmt->execute();
    $analytics['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily Activity (Last 30 days)
    $stmt = $pdo->prepare("SELECT 
        DATE(created_at) as date,
        COUNT(*) as submissions,
        SUM(CASE WHEN admin_response IS NOT NULL THEN 1 ELSE 0 END) as responses
        FROM feedback_submissions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30");
    $stmt->execute();
    $analytics['daily_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Average Response Time
    $stmt = $pdo->prepare("SELECT 
        AVG(TIMESTAMPDIFF(HOUR, created_at, admin_response_date)) as avg_response_hours,
        MIN(TIMESTAMPDIFF(HOUR, created_at, admin_response_date)) as min_response_hours,
        MAX(TIMESTAMPDIFF(HOUR, created_at, admin_response_date)) as max_response_hours
        FROM feedback_submissions 
        WHERE admin_response IS NOT NULL AND admin_response_date IS NOT NULL");
    $stmt->execute();
    $response_time = $stmt->fetch(PDO::FETCH_ASSOC);
    $analytics['response_time'] = $response_time;
    
    // Top Keywords in Feedback (Simple word frequency)
    $stmt = $pdo->prepare("SELECT subject, message FROM feedback_submissions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $feedback_texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $word_count = [];
    $common_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'must', 'shall', 'a', 'an', 'this', 'that', 'these', 'those'];
    
    foreach ($feedback_texts as $text) {
        $words = str_word_count(strtolower($text['subject'] . ' ' . $text['message']), 1);
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, $common_words)) {
                $word_count[$word] = ($word_count[$word] ?? 0) + 1;
            }
        }
    }
    
    arsort($word_count);
    $analytics['top_keywords'] = array_slice($word_count, 0, 10, true);
    
    // User Activity
    $stmt = $pdo->prepare("SELECT 
        COUNT(DISTINCT frontend_user_id) as active_users,
        COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN frontend_user_id END) as weekly_active_users,
        COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN frontend_user_id END) as daily_active_users
        FROM feedback_submissions 
        WHERE frontend_user_id IS NOT NULL");
    $stmt->execute();
    $analytics['user_activity'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Feedback Quality Metrics
    $stmt = $pdo->prepare("SELECT 
        AVG(LENGTH(message)) as avg_message_length,
        COUNT(CASE WHEN LENGTH(message) < 50 THEN 1 END) as short_messages,
        COUNT(CASE WHEN LENGTH(message) BETWEEN 50 AND 200 THEN 1 END) as medium_messages,
        COUNT(CASE WHEN LENGTH(message) > 200 THEN 1 END) as long_messages
        FROM feedback_submissions");
    $stmt->execute();
    $analytics['quality_metrics'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent Unresponded Feedback
    $stmt = $pdo->prepare("SELECT 
        id, subject, feedback_category, created_at,
        TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_pending
        FROM feedback_submissions 
        WHERE admin_response IS NULL 
        ORDER BY created_at ASC 
        LIMIT 10");
    $stmt->execute();
    $analytics['pending_feedback'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Admin Performance (if multiple admins)
    $stmt = $pdo->prepare("SELECT 
        u.username,
        a.position,
        COUNT(fs.id) as responses_given,
        AVG(TIMESTAMPDIFF(HOUR, fs.created_at, fs.admin_response_date)) as avg_response_time
        FROM users u
        JOIN admins a ON u.user_id = a.user_id
        LEFT JOIN feedback_submissions fs ON fs.admin_response IS NOT NULL
        WHERE u.role IN ('admin', 'super_admin')
        GROUP BY u.user_id
        ORDER BY responses_given DESC");
    $stmt->execute();
    $analytics['admin_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error generating analytics: ' . $e->getMessage()
    ]);
}
?>
