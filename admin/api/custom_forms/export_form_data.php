<?php
/**
 * Export Custom Form Data API Endpoint
 * Exports form responses in various formats (CSV, Excel, PDF)
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();

// Include database connection
require_once '../../../db.php';

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get parameters
$form_id = $_GET['form_id'] ?? null;
$format = $_GET['format'] ?? 'csv';
$include_responses = $_GET['include_responses'] === 'true';
$include_analytics = $_GET['include_analytics'] === 'true';
$include_timestamps = $_GET['include_timestamps'] === 'true';

if (!$form_id || !is_numeric($form_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid form ID provided']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Verify form exists and user has permission
    $stmt = $pdo->prepare("
        SELECT cf.*, u.position
        FROM custom_forms cf
        JOIN users u ON u.user_id = ?
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$user_id, $form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions
    if ($form['created_by'] != $user_id && $form['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to export this form data');
    }
    
    // Get form questions
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$form_id]);
    $questions = $stmt->fetchAll();
    
    // Get form responses if requested
    $responses = [];
    if ($include_responses) {
        $stmt = $pdo->prepare("
            SELECT fr.*, fa.question_id, fa.answer_text, fq.question_text
            FROM form_responses fr
            LEFT JOIN form_answers fa ON fr.response_id = fa.response_id
            LEFT JOIN form_questions fq ON fa.question_id = fq.question_id
            WHERE fr.form_id = ?
            ORDER BY fr.submitted_at DESC, fa.question_id ASC
        ");
        $stmt->execute([$form_id]);
        $raw_responses = $stmt->fetchAll();
        
        // Group responses by response_id
        foreach ($raw_responses as $row) {
            $response_id = $row['response_id'];
            if (!isset($responses[$response_id])) {
                $responses[$response_id] = [
                    'response_id' => $response_id,
                    'submitted_at' => $row['submitted_at'],
                    'ip_address' => $row['ip_address'],
                    'user_agent' => $row['user_agent'],
                    'answers' => []
                ];
            }
            
            if ($row['question_id']) {
                $responses[$response_id]['answers'][$row['question_id']] = [
                    'question_text' => $row['question_text'],
                    'answer_text' => $row['answer_text']
                ];
            }
        }
    }
    
    // Generate export based on format
    switch ($format) {
        case 'csv':
            exportCSV($form, $questions, $responses, $include_timestamps);
            break;
        case 'excel':
            exportExcel($form, $questions, $responses, $include_timestamps);
            break;
        case 'pdf':
            exportPDF($form, $questions, $responses, $include_analytics, $include_timestamps);
            break;
        default:
            throw new Exception('Invalid export format');
    }
    
} catch (Exception $e) {
    error_log("Export form data error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function exportCSV($form, $questions, $responses, $include_timestamps) {
    $filename = sanitizeFilename($form['title']) . '_responses_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Create header row
    $headers = ['Response ID'];
    foreach ($questions as $question) {
        $headers[] = $question['question_text'];
    }
    if ($include_timestamps) {
        $headers[] = 'Submitted At';
        $headers[] = 'IP Address';
    }
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($responses as $response) {
        $row = [$response['response_id']];
        
        foreach ($questions as $question) {
            $answer = $response['answers'][$question['question_id']]['answer_text'] ?? '';
            $row[] = $answer;
        }
        
        if ($include_timestamps) {
            $row[] = $response['submitted_at'];
            $row[] = $response['ip_address'];
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function exportExcel($form, $questions, $responses, $include_timestamps) {
    // For simplicity, we'll export as CSV with Excel-friendly formatting
    // In a production environment, you might want to use a library like PhpSpreadsheet
    $filename = sanitizeFilename($form['title']) . '_responses_' . date('Y-m-d') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    echo "<table border='1'>";
    
    // Header row
    echo "<tr>";
    echo "<th>Response ID</th>";
    foreach ($questions as $question) {
        echo "<th>" . htmlspecialchars($question['question_text']) . "</th>";
    }
    if ($include_timestamps) {
        echo "<th>Submitted At</th>";
        echo "<th>IP Address</th>";
    }
    echo "</tr>";
    
    // Data rows
    foreach ($responses as $response) {
        echo "<tr>";
        echo "<td>" . $response['response_id'] . "</td>";
        
        foreach ($questions as $question) {
            $answer = $response['answers'][$question['question_id']]['answer_text'] ?? '';
            echo "<td>" . htmlspecialchars($answer) . "</td>";
        }
        
        if ($include_timestamps) {
            echo "<td>" . $response['submitted_at'] . "</td>";
            echo "<td>" . htmlspecialchars($response['ip_address']) . "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
}

function exportPDF($form, $questions, $responses, $include_analytics, $include_timestamps) {
    // For simplicity, we'll create an HTML page that can be printed as PDF
    // In production, you might want to use a library like TCPDF or mPDF
    $filename = sanitizeFilename($form['title']) . '_report_' . date('Y-m-d') . '.html';
    
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "<!DOCTYPE html>";
    echo "<html><head>";
    echo "<title>Form Report: " . htmlspecialchars($form['title']) . "</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; margin: 20px; }";
    echo "table { border-collapse: collapse; width: 100%; margin: 20px 0; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "th { background-color: #f2f2f2; }";
    echo ".header { text-align: center; margin-bottom: 30px; }";
    echo ".analytics { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }";
    echo "</style>";
    echo "</head><body>";
    
    echo "<div class='header'>";
    echo "<h1>Form Response Report</h1>";
    echo "<h2>" . htmlspecialchars($form['title']) . "</h2>";
    echo "<p>Generated on: " . date('F j, Y \a\t g:i A') . "</p>";
    echo "</div>";
    
    if ($include_analytics) {
        echo "<div class='analytics'>";
        echo "<h3>Analytics Summary</h3>";
        echo "<p><strong>Total Responses:</strong> " . count($responses) . "</p>";
        echo "<p><strong>Total Questions:</strong> " . count($questions) . "</p>";
        echo "<p><strong>Form Status:</strong> " . ($form['is_active'] ? 'Active' : 'Inactive') . "</p>";
        echo "<p><strong>Created:</strong> " . date('F j, Y', strtotime($form['created_at'])) . "</p>";
        echo "</div>";
    }
    
    if (!empty($responses)) {
        echo "<h3>Response Data</h3>";
        echo "<table>";
        echo "<tr>";
        echo "<th>Response ID</th>";
        foreach ($questions as $question) {
            echo "<th>" . htmlspecialchars($question['question_text']) . "</th>";
        }
        if ($include_timestamps) {
            echo "<th>Submitted At</th>";
        }
        echo "</tr>";
        
        foreach ($responses as $response) {
            echo "<tr>";
            echo "<td>" . $response['response_id'] . "</td>";
            
            foreach ($questions as $question) {
                $answer = $response['answers'][$question['question_id']]['answer_text'] ?? '';
                echo "<td>" . htmlspecialchars($answer) . "</td>";
            }
            
            if ($include_timestamps) {
                echo "<td>" . date('M j, Y g:i A', strtotime($response['submitted_at'])) . "</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p><em>No responses have been submitted for this form yet.</em></p>";
    }
    
    echo "</body></html>";
}

function sanitizeFilename($filename) {
    // Remove or replace characters that are not safe for filenames
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return trim($filename, '_');
}
?>
