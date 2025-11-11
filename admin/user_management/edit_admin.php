<?php
session_start();
require '../db.php'; // Include database connection

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['position'] !== 'Super Admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Fetch admin details
$admin_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $position = $_POST['position'];

    try {
        // Update admin details
        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, position = ? WHERE admin_id = ?");
        $stmt->execute([$full_name, $position, $admin_id]);
        $success_message = "Admin updated successfully!";
    } catch (Exception $e) {
        $error_message = "Error updating admin: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Edit Admin</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $admin['full_name']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="position" class="form-label">Position</label>
                <select class="form-control" id="position" name="position" required>
                    <option value="">Select Position</option>
                    <option value="Admin" <?php echo $admin['position'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Admin</button>
        </form>
    </div>
</body>
</html>
