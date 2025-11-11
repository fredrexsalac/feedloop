<?php
/**
 * Get Form Report Data API
 * Fetches comprehensive form data for PDF report generation
 * Author: Fredrex Salac
 * Date: November 5, 2025
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../../db.php';

$form_id = $_GET['form_id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid form ID']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get form details
    $stmt = $pdo->prepare("
        SELECT cf.*, a.position, u.username
        FROM custom_forms cf
        LEFT JOIN admins a ON a.user_id = cf.created_by
        LEFT JOIN users u ON u.user_id = cf.created_by
        WHERE cf.form_id = ? AND cf.created_by = ?
    ");
    $stmt->execute([$form_id, $user_id]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        echo json_encode(['success' => false, 'message' => 'Form not found or access denied']);
        exit();
    }
    
    // Get form questions
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order
    ");
    $stmt->execute([$form_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get form responses
    $stmt = $pdo->prepare("
        SELECT * FROM form_responses 
        WHERE form_id = ? 
        ORDER BY submitted_at DESC
    ");
    $stmt->execute([$form_id]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get form analytics
    $stmt = $pdo->prepare("
        SELECT * FROM form_analytics 
        WHERE form_id = ?
    ");
    $stmt->execute([$form_id]);
    $analytics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Analyze responses for each question
    $question_analysis = [];
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        
        // Get all answers for this question
        $stmt = $pdo->prepare("
            SELECT fa.answer_value, fr.respondent_type, fr.submitted_at
            FROM form_answers fa
            JOIN form_responses fr ON fa.response_id = fr.response_id
            WHERE fa.question_id = ?
        ");
        $stmt->execute([$question_id]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $analysis = [
            'question_id' => $question_id,
            'question_text' => $question['question_text'],
            'question_type' => $question['question_type'],
            'total_responses' => count($answers),
            'answers' => $answers
        ];
        
        // Perform type-specific analysis
        if (in_array($question['question_type'], ['radio', 'checkbox', 'dropdown'])) {
            // Multiple choice analysis
            $value_counts = [];
            foreach ($answers as $answer) {
                $values = json_decode($answer['answer_value'], true);
                if (!is_array($values)) {
                    $values = [$answer['answer_value']];
                }
                foreach ($values as $value) {
                    $value_counts[$value] = ($value_counts[$value] ?? 0) + 1;
                }
            }
            arsort($value_counts);
            $analysis['distribution'] = $value_counts;
            $analysis['most_common'] = array_key_first($value_counts) ?? 'N/A';
        } elseif (in_array($question['question_type'], ['rating_stars', 'rating_scale', 'slider', 'number'])) {
            // Numeric analysis
            $numeric_values = array_map(function($a) {
                return floatval($a['answer_value']);
            }, $answers);
            
            if (!empty($numeric_values)) {
                $analysis['average'] = array_sum($numeric_values) / count($numeric_values);
                $analysis['min'] = min($numeric_values);
                $analysis['max'] = max($numeric_values);
                $analysis['median'] = $numeric_values[floor(count($numeric_values) / 2)];
                
                // Distribution for ratings
                $value_counts = array_count_values(array_map('intval', $numeric_values));
                ksort($value_counts);
                $analysis['distribution'] = $value_counts;
            }
        } elseif (in_array($question['question_type'], ['text', 'textarea', 'email'])) {
            // Text analysis
            $analysis['sample_responses'] = array_slice(array_column($answers, 'answer_value'), 0, 5);
            $analysis['response_lengths'] = array_map('strlen', array_column($answers, 'answer_value'));
            $analysis['avg_length'] = !empty($analysis['response_lengths']) 
                ? array_sum($analysis['response_lengths']) / count($analysis['response_lengths']) 
                : 0;
        }
        
        $question_analysis[] = $analysis;
    }
    
    // Get response trends (daily)
    $stmt = $pdo->prepare("
        SELECT DATE(submitted_at) as date, COUNT(*) as count
        FROM form_responses
        WHERE form_id = ?
        GROUP BY DATE(submitted_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute([$form_id]);
    $daily_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get respondent type distribution
    $stmt = $pdo->prepare("
        SELECT respondent_type, COUNT(*) as count
        FROM form_responses
        WHERE form_id = ?
        GROUP BY respondent_type
    ");
    $stmt->execute([$form_id]);
    $respondent_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate completion time statistics
    $stmt = $pdo->prepare("
        SELECT TIMESTAMPDIFF(SECOND, started_at, submitted_at) as duration
        FROM form_responses
        WHERE form_id = ? AND started_at IS NOT NULL AND submitted_at IS NOT NULL
    ");
    $stmt->execute([$form_id]);
    $durations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $completion_stats = [
        'avg_time' => !empty($durations) ? array_sum($durations) / count($durations) : 0,
        'min_time' => !empty($durations) ? min($durations) : 0,
        'max_time' => !empty($durations) ? max($durations) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'form' => $form,
            'questions' => $questions,
            'responses' => $responses,
            'analytics' => $analytics,
            'question_analysis' => $question_analysis,
            'daily_trends' => array_reverse($daily_trends),
            'respondent_distribution' => $respondent_distribution,
            'completion_stats' => $completion_stats,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $_SESSION['username'] ?? 'Admin'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching report data: ' . $e->getMessage()
    ]);
}
