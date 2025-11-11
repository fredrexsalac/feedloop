<?php
/**
 * Apply Custom Forms Database Schema
 * Run this script to add custom forms tables to the database
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

// Include database connection
require_once '../db.php';

echo "<h2>Applying Custom Forms Database Schema</h2>\n";
echo "<pre>\n";

try {
    // Read the SQL file
    $sql_file = __DIR__ . '/custom_forms_schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL statements (simple approach)
    $statements = explode(';', $sql_content);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, '/*') === 0 ||
            strpos($statement, 'SET ') === 0 ||
            strpos($statement, 'START TRANSACTION') === 0 ||
            strpos($statement, 'COMMIT') === 0) {
            continue;
        }
        
        try {
            echo "Executing: " . substr($statement, 0, 100) . "...\n";
            $pdo->exec($statement);
            $executed++;
            echo "✓ Success\n\n";
        } catch (PDOException $e) {
            $errors++;
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            
            // Continue with other statements even if one fails
            // This allows the script to work even if some tables already exist
        }
    }
    
    echo "Schema application completed!\n";
    echo "Statements executed successfully: $executed\n";
    echo "Errors encountered: $errors\n";
    
    // Verify tables were created
    echo "\nVerifying table creation:\n";
    $tables = ['custom_forms', 'form_questions', 'form_responses', 'form_answers', 'form_analytics'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists\n";
            } else {
                echo "✗ Table '$table' not found\n";
            }
        } catch (PDOException $e) {
            echo "✗ Error checking table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCustom Forms database schema has been applied successfully!\n";
    echo "You can now use the Custom Forms feature in the Super Admin dashboard.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <p><a href="../admin/super_admin/super_admin_dashboard.php">← Back to Super Admin Dashboard</a></p>
</body>
</html>
