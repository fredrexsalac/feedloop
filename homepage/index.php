<?php
session_start();

// Check for welcome or logout messages
$welcome_message = '';
$logout_message = '';

// Check if user is logged in (clean state - no automatic login)
$is_logged_in = isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in'];
$full_name = $_SESSION['frontend_full_name'] ?? '';

if (isset($_GET['welcome']) && $_GET['welcome'] == '1' && $is_logged_in) {
    $welcome_message = "Welcome back, " . htmlspecialchars($full_name) . "!";
}

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $logout_message = "You have been logged out successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FeedLoop - Home</title>
  <link rel="icon" type="image/png" href="../assets/img/logo/logo.jpg">
  <link rel="stylesheet" href="../assets/css/homepage/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <!-- Include the header -->
  <?php include_once('../includes/header.php'); ?>

  <!-- Messages -->
  <div class="container mt-3">
    <?php if ($welcome_message): ?>
    <div class="alert alert-success d-flex align-items-center shadow-sm" role="alert">
      <i class="fas fa-check-circle me-2"></i>
      <div><?php echo $welcome_message; ?> You can now view announcements and updates.</div>
    </div>
    <?php endif; ?>
    
    <?php if ($logout_message): ?>
    <div class="alert alert-info d-flex align-items-center shadow-sm" role="alert">
      <i class="fas fa-info-circle me-2"></i>
      <div><?php echo $logout_message; ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Announcements -->
    <div class="alert alert-primary d-flex align-items-center shadow-sm" role="alert" style="background-color: var(--bs-body-bg, #fff) !important; color: var(--bs-body-color, #212529) !important; border-color: var(--bs-border-color, #dee2e6) !important;">
      <svg class="bi flex-shrink-0 me-2" width="24" height="24" fill="currentColor"><use xlink:href="#info-fill"/></svg>
      <div>
        <strong>Announcements:</strong> Welcome to FeedLoop! &nbsp;|&nbsp; View the latest announcements and updates &nbsp;|&nbsp; Stay informed!
      </div>
    </div>
  </div>

  <!-- Hero Section -->
  <div class="container hero-section">
    <img src="../assets/img/logo/logo.jpg" alt="FeedLoop" width="120" height="120" class="d-block mx-auto mb-3 rounded-circle shadow-sm logo-with-border">
    <h1 class="display-5 fw-bold text-primary mb-3">Welcome to FeedLoop</h1>
    <p class="lead mb-4 text-secondary">Your trusted platform for feedback, announcements, and collaboration.</p>
    <a href="../pages/user_portal.php" class="btn btn-primary btn-lg px-4 me-2">View Announcements</a>
    <a href="#about" class="btn btn-outline-primary btn-lg px-4">Learn More</a>
  </div>

  <!-- Main Content -->
  <div class="container mb-5">
    <div class="row g-4">
      <div class="col-md-8">
        <div class="card quick-updates-card mb-4">
          <div class="card-body">
            <h4 class="card-title text-primary mb-3">Why FeedLoop?</h4>
            <ul class="list-unstyled fs-5 mb-0">
              <li>✔️ Easy and secure feedback submission</li>
              <li>✔️ Real-time announcements and notifications</li>
              <li>✔️ Collaborative platform for students and admins</li>
              <li>✔️ Modern, responsive, and user-friendly design</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card quick-updates-card">
          <div class="card-header bg-primary text-white fw-semibold">Quick Updates</div>
          <div class="card-body" style="max-height:220px; overflow-y:auto;">
            <ul class="list-group list-group-flush">
              <li class="list-group-item">📌 New: View announcements directly from the user portal</li>
              <li class="list-group-item">📰 Announcement: Weekly bulletin now available in some Specific Schedules</li>
              <li class="list-group-item">✨ Improvement: Faster page loading and smoother navigation</li>
              <li class="list-group-item">📱 Mobile: Homepage is now fully responsive for all devices</li>
              <li class="list-group-item">🎯 Update: Notification system to alert you about new posts</li>
              <li class="list-group-item">🤝 Community: Group discussions added in collaboration section</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- About Section -->
  <section id="about" class="py-5 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h2 class="text-center mb-4 text-primary">ℹ️ About FeedLoop</h2>
          <p class="lead text-center mb-4">
            FeedLoop is your all-in-one platform designed for seamless user feedback, reporting, and communication.
          </p>
          <div class="card shadow-lg">
            <div class="card-body">
              <div class="text-center mb-4">
                <img src="../assets/img/logo/logo.jpg" alt="FeedLoop" width="80" height="80" class="rounded-circle shadow-sm logo-with-border">
              </div>
              <h5 class="fw-semibold mb-2 text-primary">📖 Definition</h5>
              <p>
                FeedLoop is a feedback and reporting system designed for users to easily submit feedback and communicate with administrators. 
                It simplifies the process of submitting feedback, viewing updates, and staying informed 
                about important announcements and developments.
              </p>
              <hr>
              <h5 class="fw-semibold mb-2 text-primary">🎯 Purpose</h5>
              <p>
                The purpose of FeedLoop is to create a transparent, efficient, and accessible environment 
                where users can easily express their concerns, suggestions, and experiences. 
                It also provides administrators with tools to monitor and address feedback effectively.
              </p>
              <hr>
              <h5 class="fw-semibold mb-2 text-primary">🛠️ How to Use</h5>
              <ul>
                <li>Create your account or login using your credentials.</li>
                <li>Navigate to the feedback section to submit your input.</li>
                <li>Check for announcements and updates on the homepage.</li>
                <li>Administrators can view and manage all feedback submissions.</li>
              </ul>
              <hr>
              <h5 class="fw-semibold mb-2 text-primary">📌 User Guidelines</h5>
              <ul>
                <li>Submit feedback that is clear, concise, and respectful.</li>
                <li>Avoid duplicate submissions unless there is new information.</li>
                <li>Create an account to track your feedback submissions.</li>
                <li>Use FeedLoop responsibly as an official communication channel.</li>
              </ul>
              <hr>
              <h5 class="fw-semibold mb-2 text-primary">⚠️ Limits</h5>
              <p>
                FeedLoop is designed for feedback and reporting only. It is not a replacement for 
                direct communication or emergency services. Users must create an account to submit 
                feedback, and administrative features are restricted to authorized personnel only.
              </p>
              <hr>
              <h5 class="fw-semibold mb-2 text-primary">📝 Version</h5>
              <p>FeedLoop v1.0.0 (Beta) – Last updated: August 2025</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section id="faq" class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h2 class="text-center mb-4 text-primary">❓ Frequently Asked Questions</h2>
          <div class="accordion" id="faqAccordion">
            <!-- Basic Usage -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="faq1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                  How do I submit feedback?
                </button>
              </h2>
              <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Create an account or login to your existing account, navigate to the feedback section, fill out the form with your concerns or suggestions, and submit. You'll receive a confirmation message upon successful submission.
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header" id="faq2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                  Can I edit my feedback after submission?
                </button>
              </h2>
              <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Once submitted, feedback cannot be edited. However, you can submit additional feedback with clarifications or updates if needed.
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

            <!-- Admin Account Differences -->
            <div class="accordion-item">
              <h2 class="accordion-header" id="faq7">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                  What's the difference between User and Admin accounts?
                </button>
              </h2>
              <div id="collapse7" class="accordion-collapse collapse" aria-labelledby="faq7" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  <strong>User Accounts:</strong> Can create accounts, submit feedback, and receive confirmation messages upon submission.<br><br>
                  <strong>Admin Accounts:</strong> Have additional privileges including viewing all feedback submissions, managing feedback status (pending/resolved), and accessing the administrative dashboard. Admin accounts are restricted to authorized personnel only.<br><br>
                  <strong>Super Admin:</strong> Has the highest level of access, including the ability to manage other admin accounts, access system settings, and perform advanced administrative functions.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="faq8">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                  How can I get an Admin account?
                </button>
              </h2>
              <div id="collapse8" class="accordion-collapse collapse" aria-labelledby="faq8" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Admin accounts are exclusively for authorized personnel. To request admin access, contact the system administrator or IT department. All admin account requests undergo a verification process to ensure system security and proper access control.
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
  <footer class="footer-light text-center py-3 mt-5" style="background-color: var(--bs-body-bg, #fff) !important; color: var(--bs-body-color, #212529) !important; border-top: 1px solid var(--bs-border-color, #eee);">
    &copy; 2025 FeedLoop. All rights reserved.
  </footer>

  <!-- Bootstrap JS -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <?php if ($is_logged_in): ?>
  <script src="../assets/js/notifications.js"></script>
  <?php endif; ?>
  <!-- Bootstrap Icons (for alert icon) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
      <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533l.738-3.468c.194-.897-.105-1.319-.808-1.319-.545 0-1.178.252-1.465.598l-.088.416c.2-.176.492-.246.686-.246.275 0 .375.193.304.533zm-1.812-1.01c.07-.34.366-.533.642-.533.276 0 .375.193.304.533-.07.34-.366.533-.642.533-.276 0-.375-.193-.304-.533z"/>
    </symbol>
  </svg>
  <script src="../assets/js/homepage/theme.js">
    <?php
    session_start();

    // Check if user is logged in (clean state - no automatic login)
    $is_logged_in = isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in'];
    $username = $_SESSION['frontend_username'] ?? '';
    $full_name = $_SESSION['frontend_full_name'] ?? '';
    ?>
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
    });
  </script>

  <!-- Logout Confirmation Modal -->
  <?php if ($is_logged_in): ?>
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">
            <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center">
            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
            <h6>Are you sure you want to logout?</h6>
            <p class="text-muted mb-0">You will need to login again to submit feedback.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Cancel
          </button>
          <a href="../frontend_logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt me-1"></i>Yes, Logout
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</body>
</html>
