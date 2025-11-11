<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Error - FeedLoop</title>
    
    <!-- Bootstrap CSS -->
    <link href="../../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Form Styles -->
    <link rel="stylesheet" href="../../assets/css/public/form_styles.css">
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        
        <h1 class="error-title">Something Went Wrong</h1>
        
        <p class="error-message">
            We're sorry, but there was an error loading this feedback form.
            <br><br>
            This might be a temporary issue. Please try again in a few moments, or contact the administrator if the problem persists.
        </p>
        
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <button onclick="window.location.reload()" class="btn btn-outline-primary">
                <i class="fas fa-redo me-2"></i>Try Again
            </button>
            <a href="../../index.php" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Back to FeedLoop
            </a>
        </div>
        
        <div class="mt-4 pt-4 border-top">
            <small class="text-muted">
                If this error continues, please contact technical support.
            </small>
        </div>
    </div>
</body>
</html>
