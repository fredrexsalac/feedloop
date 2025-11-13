<?php
/**
 * Google OAuth Configuration
 * For "Sign in with Google" functionality
 */

if (!defined('FEEDLOOP_SECURE')) {
    define('FEEDLOOP_SECURE', true);
}

return [
    // Google OAuth 2.0 Credentials
    // Get these from: https://console.cloud.google.com/apis/credentials
    'client_id' => '1027762356669-uhv48ghj3g86c6d2150bts2tfajeu2qr.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => 'http://localhost/auth/google_callback.php', // Change in production
    
    // OAuth Endpoints
    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    
    // Scopes
    'scopes' => [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile'
    ],
    
    // Settings
    'enabled' => true, // Google Sign-In is now enabled!
    
    /**
     * SETUP INSTRUCTIONS:
     * 
     * 1. Go to Google Cloud Console: https://console.cloud.google.com/
     * 2. Create a new project or select existing one   
     * 3. Enable Google+ API
     * 4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
     * 5. Application type: "Web application"
     * 6. Authorized redirect URIs: 
     *    - Development: http://localhost/auth/google_callback.php
     *    - Production: https://yourdomain.com/auth/google_callback.php
     * 7. Copy Client ID and Client Secret above
     * 8. Set 'enabled' to true
     * 
     * SECURITY NOTES:
     * - Never commit this file with real credentials to Git
     * - Use environment variables in production
     * - Keep client_secret confidential
     * - Update redirect_uri for production deployment
     */
];
