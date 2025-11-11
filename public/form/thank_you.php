<?php
/**
 * Thank You Page for Form Submission
 * Displayed after successful form submission
 */

$form_code = $_GET['code'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - FeedLoop</title>
    
    <!-- Bootstrap CSS -->
    <link href="../../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Form Styles -->
    <link rel="stylesheet" href="../../assets/css/public/form_styles.css">
</head>
<body>
    <div class="thank-you-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="thank-you-title">Thank You!</h1>
        
        <p class="thank-you-message">
            Your feedback has been successfully submitted. We appreciate you taking the time to share your thoughts with us.
            <br><br>
            Your responses will help us improve our services and better serve our community.
        </p>
        
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <?php if ($form_code): ?>
                <a href="index.php?code=<?php echo urlencode($form_code); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-redo me-2"></i>Submit Another Response
                </a>
            <?php endif; ?>
            <a href="../../index.php" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Back to FeedLoop
            </a>
        </div>
        
        <div class="mt-4 pt-4 border-top">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-2"></i>
                Your data is secure and will be handled according to our privacy policy.
            </small>
        </div>
    </div>
</body>
</html>
