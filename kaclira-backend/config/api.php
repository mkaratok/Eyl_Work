<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your API settings including versioning,
    | rate limiting, security features, and response formatting.
    |
    */

    'version' => [
        'default' => 'v1',
        'latest' => 'v2',
        'supported' => ['v1', 'v2'],
        'deprecated' => ['v1'],
    ],

    'rate_limiting' => [
        'enabled' => env('API_RATE_LIMITING_ENABLED', true),
        'limits' => [
            'guest' => [
                'requests' => env('API_RATE_LIMIT_GUEST', 100),
                'minutes' => env('API_RATE_LIMIT_WINDOW', 60),
            ],
            'user' => [
                'requests' => env('API_RATE_LIMIT_USER', 500),
                'minutes' => env('API_RATE_LIMIT_WINDOW', 60),
            ],
            'seller' => [
                'requests' => env('API_RATE_LIMIT_SELLER', 1000),
                'minutes' => env('API_RATE_LIMIT_WINDOW', 60),
            ],
            'admin' => [
                'requests' => 0, // Unlimited
                'minutes' => env('API_RATE_LIMIT_WINDOW', 60),
            ],
        ],
    ],

    'security' => [
        'api_keys' => [
            'enabled' => env('API_KEYS_ENABLED', true),
            'header_name' => 'X-API-Key',
            'query_param' => 'api_key',
            'default_rate_limit' => 1000,
            'max_keys_per_user' => 5,
        ],
        
        'csrf' => [
            'enabled' => env('API_CSRF_ENABLED', true),
            'except' => [
                'api/auth/login',
                'api/auth/register',
                'api/webhook/*',
            ],
        ],
        
        'xss_protection' => [
            'enabled' => env('API_XSS_PROTECTION', true),
            'sanitize_input' => true,
            'escape_output' => true,
        ],
        
        'sql_injection' => [
            'enabled' => env('API_SQL_INJECTION_PROTECTION', true),
            'log_attempts' => true,
            'block_suspicious_queries' => true,
        ],
        
        'request_validation' => [
            'max_request_size' => env('API_MAX_REQUEST_SIZE', '10M'),
            'max_file_size' => env('API_MAX_FILE_SIZE', '5M'),
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'validate_json' => true,
            'validate_headers' => true,
        ],
    ],

    'response' => [
        'format' => [
            'standardize' => env('API_STANDARDIZE_RESPONSES', true),
            'include_meta' => env('API_INCLUDE_META', true),
            'include_debug' => env('API_INCLUDE_DEBUG', false),
            'pretty_print' => env('API_PRETTY_PRINT', false),
        ],
        
        'headers' => [
            'add_security_headers' => env('API_SECURITY_HEADERS', true),
            'add_performance_headers' => env('API_PERFORMANCE_HEADERS', true),
            'add_cache_headers' => env('API_CACHE_HEADERS', true),
        ],
        
        'caching' => [
            'enabled' => env('API_CACHING_ENABLED', true),
            'default_ttl' => env('API_CACHE_TTL', 3600), // 1 hour
            'cache_driver' => env('API_CACHE_DRIVER', 'redis'),
        ],
    ],

    'logging' => [
        'enabled' => env('API_LOGGING_ENABLED', true),
        'log_requests' => env('API_LOG_REQUESTS', false),
        'log_responses' => env('API_LOG_RESPONSES', false),
        'log_errors' => env('API_LOG_ERRORS', true),
        'log_slow_queries' => env('API_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('API_SLOW_QUERY_THRESHOLD', 1000), // ms
        'log_channel' => env('API_LOG_CHANNEL', 'daily'),
    ],

    'documentation' => [
        'swagger' => [
            'enabled' => env('API_SWAGGER_ENABLED', true),
            'route' => env('API_SWAGGER_ROUTE', '/api/documentation'),
            'title' => 'Kaçlıra API Documentation',
            'description' => 'Comprehensive API documentation for Kaçlıra price comparison platform',
            'version' => '2.0',
            'contact' => [
                'name' => 'Kaçlıra API Support',
                'email' => 'api@kaclira.com',
                'url' => 'https://kaclira.com/support',
            ],
        ],
        
        'postman' => [
            'enabled' => env('API_POSTMAN_ENABLED', true),
            'collection_name' => 'Kaçlıra API Collection',
            'export_route' => '/api/postman/collection',
        ],
    ],

    'monitoring' => [
        'enabled' => env('API_MONITORING_ENABLED', true),
        'metrics' => [
            'response_time' => true,
            'request_count' => true,
            'error_rate' => true,
            'cache_hit_rate' => true,
        ],
        'alerts' => [
            'error_rate_threshold' => env('API_ERROR_RATE_THRESHOLD', 5), // %
            'response_time_threshold' => env('API_RESPONSE_TIME_THRESHOLD', 2000), // ms
            'notification_channels' => ['slack', 'email'],
        ],
    ],

    'features' => [
        'search' => [
            'enabled' => true,
            'elasticsearch' => env('ELASTICSEARCH_ENABLED', false),
            'cache_results' => true,
            'suggestion_limit' => 10,
        ],
        
        'notifications' => [
            'enabled' => true,
            'channels' => ['database', 'mail', 'push'],
            'queue' => env('NOTIFICATION_QUEUE', 'default'),
        ],
        
        'file_uploads' => [
            'enabled' => true,
            'disk' => env('FILESYSTEM_DISK', 'public'),
            'max_size' => env('API_MAX_FILE_SIZE', '5M'),
            'virus_scan' => env('API_VIRUS_SCAN', false),
        ],
    ],

];
