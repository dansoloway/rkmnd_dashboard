<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FastAPI Backend Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the connection to the FastAPI backend server
    | that provides video data, search functionality, and analytics.
    |
    */

    'api_url' => env('BACKEND_API_URL', 'https://fitform100.com'),
    
    'timeout' => env('BACKEND_API_TIMEOUT', 30),
    
    'default_api_key' => env('TENANT_DEFAULT_API_KEY', ''),
    
    'endpoints' => [
        'wordpress' => [
            'videos' => '/api/v1/wordpress/videos',
            'video_detail' => '/api/v1/wordpress/videos/{id}',
            'stats' => '/api/v1/wordpress/stats',
        ],
        'tenant' => [
            'info' => '/api/v1/tenant/info',
            'analytics' => '/api/v1/tenant/analytics',
            'quota' => '/api/v1/tenant/quota',
        ],
        's3' => [
            'presigned_url' => '/api/v1/s3/presigned-url',
            'info' => '/api/v1/s3/info',
            'files' => '/api/v1/s3/files',
        ],
        'health' => '/health/detailed',
    ],
];


