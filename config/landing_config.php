<?php
/**
 * FeedLoop Landing Page Configuration
 * 
 * This file controls which landing page version is displayed
 * when users visit the main FeedLoop URL
 */

return [
    // Landing page version to use
    // Options: 'php', 'html', 'original'
    'version' => 'html',
    
    // Landing page settings
    'settings' => [
        'show_stats' => true,           // Show dynamic statistics
        'show_user_messages' => true,   // Show welcome/logout messages
        'enable_smooth_scroll' => true, // Enable smooth scrolling navigation
        'show_social_links' => false,   // Show social media links in footer
        'maintenance_mode' => false,    // Enable maintenance mode
    ],
    
    // Version descriptions
    'versions' => [
        'php' => [
            'name' => 'PHP Integrated Landing Page',
            'description' => 'Modern HTML design with full PHP integration for dynamic content, user sessions, and database connectivity',
            'features' => [
                'Dynamic statistics from database',
                'User session management',
                'Welcome/logout messages',
                'Responsive modern design',
                'Full PHP backend integration'
            ]
        ],
        'html' => [
            'name' => 'HTML Landing Page with AJAX',
            'description' => 'Pure HTML page that loads dynamic content via AJAX API calls',
            'features' => [
                'Fast loading HTML',
                'AJAX-powered dynamic content',
                'Modern responsive design',
                'API-based PHP integration',
                'Client-side interactivity'
            ]
        ],
        'original' => [
            'name' => 'Original PHP Homepage',
            'description' => 'The original FeedLoop homepage with full PHP functionality',
            'features' => [
                'Complete PHP integration',
                'User authentication status',
                'Comprehensive FAQ section',
                'Detailed feature explanations',
                'Established functionality'
            ]
        ]
    ],
    
    // API endpoints for dynamic content
    'api_endpoints' => [
        'stats' => 'api/get_stats.php',
        'announcements' => 'api/get_announcements.php',
        'recent_activity' => 'api/get_recent_activity.php'
    ],
    
    // Cache settings for performance
    'cache' => [
        'enable_stats_cache' => true,
        'stats_cache_duration' => 300, // 5 minutes
        'enable_page_cache' => false,
        'page_cache_duration' => 3600  // 1 hour
    ]
];
?>
