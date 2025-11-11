<?php
session_start();
require '../db.php'; // Include database connection

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['position'] !== 'Super Admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['email'];
    $position = $_POST['position'];

    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First insert into users table
        $stmt_user = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
        $stmt_user->execute([$username, $password, $email]);
        $user_id = $pdo->lastInsertId();
        
        // Then insert into admins table
        $stmt_admin = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
        $stmt_admin->execute([$user_id, $full_name, $position]);
        
        // Commit transaction
        $pdo->commit();
        $success_message = "Admin added successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "Error adding admin: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Add New Admin</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="position" class="form-label">Position</label>
                <select class="form-control" id="position" name="position" required>
                    <option value="">Select Position</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Admin</button>
        </form>
    </div>
</body>
</html>
