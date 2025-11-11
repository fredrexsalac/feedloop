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
    // Get search parameters
    $search_query = $_GET['q'] ?? '';
    $category = $_GET['category'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $where_conditions = [];
    $params = [];
    
    // Search in subject and message
    if (!empty($search_query)) {
        $where_conditions[] = "(subject LIKE ? OR message LIKE ?)";
        $search_term = '%' . $search_query . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Filter by category
    if ($category !== 'all') {
        $where_conditions[] = "feedback_category = ?";
        $params[] = $category;
    }
    
    // Filter by response status
    if ($status === 'responded') {
        $where_conditions[] = "admin_response IS NOT NULL";
    } elseif ($status === 'pending') {
        $where_conditions[] = "admin_response IS NULL";
    }
    
    // Date range filter
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
    }
    
    // Build WHERE clause
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Validate sort parameters
    $valid_sort_columns = ['created_at', 'subject', 'feedback_category', 'admin_response_date'];
    $sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'created_at';
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM feedback_submissions $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_count = $stmt->fetchColumn();
    
    // Get feedback data with enhanced information
    $sql = "SELECT 
        id,
        subject,
        message,
        feedback_category,
        created_at,
        admin_response,
        admin_response_date,
        CASE 
            WHEN admin_response IS NOT NULL THEN 'responded'
            ELSE 'pending'
        END as response_status,
        CASE 
            WHEN admin_response IS NULL THEN TIMESTAMPDIFF(HOUR, created_at, NOW())
            ELSE TIMESTAMPDIFF(HOUR, created_at, admin_response_date)
        END as response_time_hours,
        LENGTH(message) as message_length,
        CASE 
            WHEN LENGTH(message) < 50 THEN 'short'
            WHEN LENGTH(message) BETWEEN 50 AND 200 THEN 'medium'
            ELSE 'long'
        END as message_type
        FROM feedback_submissions 
        $where_clause
        ORDER BY $sort_by $sort_order
        LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results for better display
    foreach ($feedback_list as &$feedback) {
        // Truncate long messages for list view
        $feedback['message_preview'] = strlen($feedback['message']) > 150 
            ? substr($feedback['message'], 0, 150) . '...' 
            : $feedback['message'];
        
        // Format dates
        $feedback['created_at_formatted'] = date('M j, Y g:i A', strtotime($feedback['created_at']));
        
        if ($feedback['admin_response_date']) {
            $feedback['admin_response_date_formatted'] = date('M j, Y g:i A', strtotime($feedback['admin_response_date']));
        }
        
        // Calculate urgency based on pending time
        if ($feedback['response_status'] === 'pending') {
            if ($feedback['response_time_hours'] > 72) {
                $feedback['urgency'] = 'high';
            } elseif ($feedback['response_time_hours'] > 24) {
                $feedback['urgency'] = 'medium';
            } else {
                $feedback['urgency'] = 'low';
            }
        } else {
            $feedback['urgency'] = 'none';
        }
        
        // Add priority score (combination of factors)
        $priority_score = 0;
        
        // Category priority
        $category_priorities = [
            'System Feedback' => 5,
            'Dean/Office Feedback' => 4,
            'Department Feedback' => 3,
            'Instructor Feedback' => 2,
            'Event Feedback' => 1,
            'Community-Based Issues' => 3
        ];
        $priority_score += $category_priorities[$feedback['feedback_category']] ?? 1;
        
        // Time priority
        if ($feedback['response_status'] === 'pending') {
            if ($feedback['response_time_hours'] > 72) $priority_score += 3;
            elseif ($feedback['response_time_hours'] > 24) $priority_score += 2;
            else $priority_score += 1;
        }
        
        // Message length priority (longer messages might be more detailed/important)
        if ($feedback['message_type'] === 'long') $priority_score += 1;
        
        $feedback['priority_score'] = $priority_score;
        
        // Determine priority level
        if ($priority_score >= 7) $feedback['priority'] = 'high';
        elseif ($priority_score >= 5) $feedback['priority'] = 'medium';
        else $feedback['priority'] = 'low';
    }
    
    // Calculate pagination info
    $total_pages = ceil($total_count / $limit);
    $has_next = $page < $total_pages;
    $has_prev = $page > 1;
    
    // Get summary statistics for current search
    $summary_stats = [
        'total_results' => $total_count,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'results_per_page' => $limit,
        'has_next' => $has_next,
        'has_prev' => $has_prev
    ];
    
    // Get category breakdown for current search
    $category_sql = "SELECT 
        feedback_category,
        COUNT(*) as count
        FROM feedback_submissions 
        $where_clause
        GROUP BY feedback_category
        ORDER BY count DESC";
    
    $stmt = $pdo->prepare($category_sql);
    $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset params
    $category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get response status breakdown
    $status_sql = "SELECT 
        CASE 
            WHEN admin_response IS NOT NULL THEN 'responded'
            ELSE 'pending'
        END as status,
        COUNT(*) as count
        FROM feedback_submissions 
        $where_clause
        GROUP BY status";
    
    $stmt = $pdo->prepare($status_sql);
    $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset params
    $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $feedback_list,
        'pagination' => $summary_stats,
        'breakdown' => [
            'categories' => $category_breakdown,
            'status' => $status_breakdown
        ],
        'search_params' => [
            'query' => $search_query,
            'category' => $category,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Search error: ' . $e->getMessage()
    ]);
}
?>
