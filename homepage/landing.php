<?php
// Deprecate old landing: redirect to new landing page
if (!headers_sent()) {
    header('Location: ../index.html');
    exit();
}

// Session already started in index.php - don't start again
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user session and messages - support both unified and legacy session variables
$welcome_message = '';
$logout_message = '';
$is_logged_in = false;
$full_name = 'User';
$user_profile_pic = '';
$user_id = null;

// Check unified session variables first (new system)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $is_logged_in = true;
    $full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
    $user_id = $_SESSION['user_id'] ?? null;
}
// Fallback to legacy frontend session variables
elseif (isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in']) {
    $is_logged_in = true;
    $full_name = $_SESSION['frontend_full_name'] ?? $_SESSION['frontend_username'] ?? 'User';
    $user_id = $_SESSION['frontend_user_id'] ?? null;
}

// Get user profile picture and full name if logged in
if ($is_logged_in && !empty($user_id)) {
    try {
        require_once 'db.php';
        // Fetch both full_name and profile_pic from database (unified users table)
        $stmt = $pdo->prepare("SELECT full_name, profile_pic FROM users WHERE user_id = ? AND role = 'user'");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        if ($result) {
            // Update full name from database if available
            if (!empty($result['full_name'])) {
                $full_name = $result['full_name'];
            }
            // Update profile picture if available
            if (!empty($result['profile_pic'])) {
                $user_profile_pic = $result['profile_pic'];
            }
        }
    } catch (Exception $e) {
        // Silently handle error - keep session values
    }
}

if (isset($_GET['welcome']) && $_GET['welcome'] == '1' && $is_logged_in) {
    $welcome_message = "Welcome back, " . htmlspecialchars($full_name) . "!";
}

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $logout_message = "You have been logged out successfully.";
}

// Get dynamic stats from database
require_once 'db.php';
$stats = [
    'total_feedback' => 1250,
    'active_users' => 500,
    'avg_response_time' => '24h',
    'satisfaction_rate' => 98
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM feedback_submissions");
    $stats['total_feedback'] = $stmt->fetchColumn() ?: 1250;
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('student', 'user')");
    $stats['active_users'] = $stmt->fetchColumn() ?: 500;
} catch (Exception $e) {
    // Use defaults if database not available
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FeedLoop - Modern Feedback Management System</title>
    <link rel="icon" type="image/png" href="assets/img/logo/logo.jpg">
    <link href="assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/homepage/style.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            overflow-x: hidden;
        }

        /* Navigation Fixes */
        .navbar {
            transition: all 0.3s ease;
            z-index: 1050;
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.1);
        }

        .navbar-nav .nav-link {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-top: 76px; /* Account for fixed navbar */
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .logo-with-border {
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .logo-with-border:hover {
            transform: scale(1.05);
        }

        /* Stats Section */
        .stats-section {
            background: var(--light-color);
            padding: 60px 0;
        }

        .stat-card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Feature Cards */
        .feature-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
            box-shadow: 0 5px 20px rgba(13, 110, 253, 0.3);
        }

        /* Buttons */
        .btn-custom {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        /* Admin Access Section */
        .admin-access-section {
            background: linear-gradient(135deg, var(--secondary-color), var(--dark-color));
            padding: 15px 0;
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }

        .footer h6 {
            color: var(--info-color);
            margin-bottom: 20px;
        }

        .footer a {
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--info-color) !important;
        }

        /* Accordion Improvements */
        .accordion-item {
            border: none;
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .accordion-button {
            background: white;
            border: none;
            padding: 20px 25px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
            border: none;
        }

        .accordion-body {
            padding: 25px;
            background: #fafafa;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0 60px;
                margin-top: 56px; /* Smaller navbar on mobile */
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1.1rem;
            }

            .hero-section .btn {
                margin: 5px;
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-card {
                padding: 20px 15px;
                margin-bottom: 20px;
            }

            .feature-card {
                margin-bottom: 20px;
            }

            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }

            .navbar-nav .nav-link {
                margin: 5px 0;
                text-align: center;
            }

            .navbar-collapse {
                background: white;
                padding: 20px;
                border-radius: 10px;
                margin-top: 10px;
                box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            }

            .accordion-button {
                padding: 15px 20px;
                font-size: 0.95rem;
            }

            .accordion-body {
                padding: 20px;
                font-size: 0.9rem;
            }

            .footer {
                padding: 30px 0 20px;
            }

            .footer .col-lg-3, .footer .col-lg-6 {
                margin-bottom: 30px;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 60px 0 40px;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }

            .stat-card {
                padding: 15px 10px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .feature-card {
                padding: 20px 15px;
            }

            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        /* Loading Animation */
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Alert Improvements */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1edff, #e8f5e8);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-info {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            color: #0c5460;
            border-left: 4px solid var(--info-color);
        }

        /* Navbar scroll effect */
        .navbar.scrolled {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#home">
                <img src="assets/img/logo/logo.jpg" alt="FeedLoop" width="40" height="40" class="rounded-circle me-2">
                <span class="fw-bold text-primary">FeedLoop</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <?php if (!$is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-primary ms-2 px-3" href="pages/user_portal.php">View Forms</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-secondary ms-2 px-3" href="auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary ms-2 px-3 text-white" href="auth/register.php">Create Account</a></li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn btn-success ms-2 px-3 text-white" href="pages/user_portal.php">Forms</a></li>
                    <li class="nav-item">
                        <span class="navbar-text me-2 d-flex align-items-center">
                            <?php if (!empty($user_profile_pic)): ?>
                                <?php $clean_pic = str_replace('../', '', $user_profile_pic); ?>
                                <img src="<?php echo $clean_pic; ?>" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #28a745;">
                            <?php endif; ?>
                            Welcome, <strong><?php echo htmlspecialchars($full_name); ?></strong>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-secondary ms-2 px-3" href="auth/logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages -->
    <?php if ($welcome_message || $logout_message): ?>
    <div class="container mt-5 pt-4">
        <?php if ($welcome_message): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <div><?php echo $welcome_message; ?> Check out the latest updates and announcements in your portal.</div>
        </div>
        <?php endif; ?>
        
        <?php if ($logout_message): ?>
        <div class="alert alert-info d-flex align-items-center shadow-sm" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <div><?php echo $logout_message; ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section id="home" class="hero-section fade-in">
        <div class="hero-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <img src="assets/img/logo/logo.jpg" alt="FeedLoop" width="120" height="120" 
                             class="d-block mx-auto mb-4 rounded-circle logo-with-border">
                        <h1 class="display-4 fw-bold mb-3">Welcome to FeedLoop</h1>
                        <p class="lead mb-4">Your modern, secure, and efficient feedback management system</p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <?php if (!$is_logged_in): ?>
                            <a href="auth/register.php" class="btn btn-light btn-custom btn-lg text-dark">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </a>
                            <a href="pages/user_portal.php" class="btn btn-outline-light btn-custom btn-lg">
                                <i class="fas fa-clipboard-list me-2"></i>View Forms
                            </a>
                            <?php else: ?>
                            <a href="pages/user_portal.php" class="btn btn-light btn-custom btn-lg">
                                <i class="fas fa-clipboard-list me-2"></i>Forms
                            </a>
                            <a href="auth/logout.php" class="btn btn-outline-light btn-custom btn-lg">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                            <?php endif; ?>
                            <a href="#features" class="btn btn-outline-light btn-custom btn-lg">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section fade-in">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_feedback']); ?>+</div>
                        <h6 class="text-muted">Feedback Submitted</h6>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['active_users']); ?>+</div>
                        <h6 class="text-muted">Active Users</h6>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['avg_response_time']; ?></div>
                        <h6 class="text-muted">Avg Response Time</h6>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['satisfaction_rate']; ?>%</div>
                        <h6 class="text-muted">User Satisfaction</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Why Choose FeedLoop?</h2>
                <p class="lead text-muted">Modern features for efficient communication and updates</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Secure & Private</h5>
                        <p class="text-muted">Data Privacy Act compliant with enterprise-grade security</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Mobile-First</h5>
                        <p class="text-muted">Responsive design optimized for all devices</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Real-time Analytics</h5>
                        <p class="text-muted">Comprehensive dashboard with actionable insights</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold text-primary mb-4">About FeedLoop</h2>
                    <p class="lead mb-4">Comprehensive communication and announcement system for Philippine educational institutions.</p>
                    <p class="mb-4">Built with modern technologies and international security standards, providing a secure and user-friendly platform for feedback collection and management.</p>
                    <a href="#faq" class="btn btn-primary btn-lg">Learn More</a>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="assets/img/logo/logo.jpg" alt="FeedLoop" class="img-fluid rounded-circle shadow-lg" style="max-width: 300px;">
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="text-center display-5 fw-bold text-primary mb-5">❓ Frequently Asked Questions</h2>
                    <div class="accordion" id="faqAccordion">
                        <!-- Basic Usage -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    How do I view announcements and updates?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Simply visit the User Portal to view all announcements and updates from administrators. You can access it as a guest or create an account for a personalized experience with notifications and participation in surveys.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    Can I participate in surveys and forms?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! When administrators create surveys or feedback forms, you can participate directly from the User Portal. Look for the 'Participate' button on survey announcements.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    How long does it take to get a response?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Response times vary depending on the nature of your feedback. Urgent matters are typically addressed within 24-48 hours, while general feedback may take 3-5 business days.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    What if I forgot my password?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Currently, password reset functionality is not implemented. If you forget your password, please contact the system administrator for assistance with account recovery.
                                </div>
                            </div>
                        </div>

                        <!-- Data Privacy & Security -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                    How does FeedLoop protect my personal data under the Data Privacy Act?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    FeedLoop is fully compliant with the Data Privacy Act of 2012 (Republic Act No. 10173). We implement strict data protection measures including encrypted storage, limited access controls, and secure transmission protocols. Your personal information is only used for feedback processing and institutional communication. We do not share your data with third parties without explicit consent, and you have the right to access, correct, or request deletion of your personal data at any time.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq6">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                    Is my feedback anonymous and confidential?
                                </button>
                            </h2>
                            <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="faq6" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Your feedback is linked to your user account for tracking and follow-up purposes. Only authorized administrators can access your feedback and identifying information. All feedback is treated confidentially and used solely for improvement purposes.
                                </div>
                            </div>
                        </div>

                        

                        <!-- Platform & Business -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq9">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                                    Why should our institution choose FeedLoop over other feedback systems?
                                </button>
                            </h2>
                            <div id="collapse9" class="accordion-collapse collapse" aria-labelledby="faq9" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    FeedLoop offers several competitive advantages: <strong>1) Local Compliance</strong> - Built specifically for Philippine educational institutions with Data Privacy Act compliance, <strong>2) Cost-Effective</strong> - Significantly lower costs compared to international platforms, <strong>3) Customizable</strong> - Tailored features for Filipino academic culture and requirements, <strong>4) Responsive Support</strong> - Local technical support in your timezone, <strong>5) Integration Ready</strong> - Seamlessly integrates with existing school management systems, <strong>6) Mobile-First Design</strong> - Optimized for smartphone usage, which is prevalent among Filipino students.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq10">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                                    What are FeedLoop's key features and benefits?
                                </button>
                            </h2>
                            <div id="collapse10" class="accordion-collapse collapse" aria-labelledby="faq10" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Core Features:</strong> Real-time feedback submission, automated notifications, comprehensive analytics dashboard, multi-role access control, mobile-responsive design, and secure data management.<br><br>
                                    <strong>Business Benefits:</strong> Improved student satisfaction tracking, faster issue resolution, data-driven decision making, enhanced communication transparency, reduced administrative workload, and better institutional accountability.<br><br>
                                    <strong>Technical Benefits:</strong> Cloud-based reliability, automatic backups, scalable architecture, and regular security updates.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq11">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse11" aria-expanded="false" aria-controls="collapse11">
                                    How does FeedLoop help improve institutional performance?
                                </button>
                            </h2>
                            <div id="collapse11" class="accordion-collapse collapse" aria-labelledby="faq11" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    FeedLoop transforms feedback from a reactive to a proactive tool. Institutions can identify trends early, measure satisfaction metrics, track improvement initiatives, and demonstrate accountability to stakeholders. The analytics dashboard provides actionable insights that help administrators make informed decisions, ultimately leading to better student outcomes and institutional reputation.
                                </div>
                            </div>
                        </div>

                        <!-- Technical & Advanced -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq12">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse12" aria-expanded="false" aria-controls="collapse12">
                                    What happens if there's a system outage or technical issue?
                                </button>
                            </h2>
                            <div id="collapse12" class="accordion-collapse collapse" aria-labelledby="faq12" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    FeedLoop maintains 99.9% uptime with redundant systems and automatic backups. In case of technical issues, our support team provides immediate assistance during business hours. Emergency contact procedures are available for critical situations, and all data is continuously backed up to prevent loss.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq13">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse13" aria-expanded="false" aria-controls="collapse13">
                                    Can FeedLoop integrate with our existing school management system?
                                </button>
                            </h2>
                            <div id="collapse13" class="accordion-collapse collapse" aria-labelledby="faq13" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, FeedLoop is designed with integration capabilities for popular school management systems used in the Philippines. We provide API access and can work with your IT team to establish seamless data synchronization, including student enrollment data, faculty information, and academic calendar integration.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq14">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse14" aria-expanded="false" aria-controls="collapse14">
                                    How do I report a bug or request a new feature?
                                </button>
                            </h2>
                            <div id="collapse14" class="accordion-collapse collapse" aria-labelledby="faq14" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Use the built-in feedback system to report bugs or request features - we practice what we preach! You can also contact our technical support team directly. All reported issues are tracked and prioritized based on impact and user needs. Feature requests from institutional clients are given high priority in our development roadmap.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/img/logo/logo.jpg" alt="FeedLoop" width="40" height="40" class="rounded-circle me-2">
                        <span class="h5 mb-0 text-white">FeedLoop</span>
                    </div>
                    <p class="text-light">Modern feedback management system designed for Philippine educational institutions. Secure, efficient, and user-friendly.</p>
                </div>
                
                <div class="col-lg-3">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="text-light text-decoration-none">Home</a></li>
                        <li><a href="#features" class="text-light text-decoration-none">Features</a></li>
                        <li><a href="#about" class="text-light text-decoration-none">About</a></li>
                        <li><a href="#faq" class="text-light text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3">
                    <h6 class="text-white mb-3">User Access</h6>
                    <ul class="list-unstyled">
                        <?php if (!$is_logged_in): ?>
                        <li><a href="pages/user_portal.php" class="text-light text-decoration-none">View Forms</a></li>
                        <li><a href="auth/login.php" class="text-light text-decoration-none">User Login</a></li>
                        <li><a href="auth/register.php" class="text-light text-decoration-none">Create Account</a></li>
                        <?php else: ?>
                        <li><a href="pages/user_portal.php" class="text-light text-decoration-none">Forms</a></li>
                        <li><a href="pages/user_profile.php" class="text-light text-decoration-none">My Profile</a></li>
                        <li><a href="auth/logout.php" class="text-light text-decoration-none">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 border-light">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">&copy; 2025 FeedLoop. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">Data Privacy Act 2012 Compliant</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animate stats on scroll (Intersection Observer)
        function animateStats() {
            const stats = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        
                        // Add animation class
                        target.style.opacity = '0';
                        target.style.transform = 'scale(0.5)';
                        
                        setTimeout(() => {
                            target.style.transition = 'all 0.6s ease';
                            target.style.opacity = '1';
                            target.style.transform = 'scale(1)';
                        }, 100);
                        
                        observer.unobserve(target);
                    }
                });
            }, {
                threshold: 0.5
            });

            stats.forEach(stat => observer.observe(stat));
        }

        // Animate feature cards on scroll
        function animateFeatures() {
            const features = document.querySelectorAll('.feature-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 200);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            features.forEach(feature => {
                feature.style.opacity = '0';
                feature.style.transform = 'translateY(30px)';
                feature.style.transition = 'all 0.6s ease';
                observer.observe(feature);
            });
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateStats();
            animateFeatures();
            
            // Add fade-in class to sections
            const sections = document.querySelectorAll('.fade-in');
            sections.forEach(section => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            });
        });

        // Mobile menu improvements
        document.addEventListener('DOMContentLoaded', function() {
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            // Close mobile menu when clicking on a link
            document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (navbarCollapse.classList.contains('show')) {
                        navbarToggler.click();
                    }
                });
            });
        });

        // Touch improvements for mobile
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
            
            // Add touch feedback for buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                btn.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        }
    </script>
</body>
</html>
