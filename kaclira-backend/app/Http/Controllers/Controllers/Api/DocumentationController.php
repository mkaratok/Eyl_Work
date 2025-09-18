<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * Get OpenAPI/Swagger documentation
     */
    public function swagger(): JsonResponse
    {
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Kaçlıra API Documentation',
                'description' => 'Comprehensive API documentation for Kaçlıra price comparison platform',
                'version' => '2.0.0',
                'contact' => [
                    'name' => 'Kaçlıra API Support',
                    'email' => 'api@kaclira.com',
                    'url' => 'https://kaclira.com/support'
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api/v1',
                    'description' => 'API Version 1'
                ],
                [
                    'url' => config('app.url') . '/api/v2',
                    'description' => 'API Version 2 (Latest)'
                ]
            ],
            'paths' => $this->getApiPaths(),
            'components' => $this->getComponents(),
            'security' => [
                ['bearerAuth' => []],
                ['apiKey' => []]
            ]
        ];

        return response()->json($swagger);
    }

    /**
     * Get Postman collection
     */
    public function postman(): JsonResponse
    {
        $collection = [
            'info' => [
                'name' => 'Kaçlıra API Collection',
                'description' => 'Complete API collection for Kaçlıra price comparison platform',
                'version' => '2.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']
                ]
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => config('app.url') . '/api', 'type' => 'string'],
                ['key' => 'jwt_token', 'value' => '', 'type' => 'string'],
                ['key' => 'api_key', 'value' => '', 'type' => 'string']
            ],
            'item' => $this->getPostmanItems()
        ];

        return response()->json($collection);
    }

    /**
     * Get API endpoints documentation
     */
    public function endpoints(): JsonResponse
    {
        $endpoints = [
            'v1' => [
                'public' => [
                    'products' => [
                        'GET /api/v1/public/products' => 'Get products list',
                        'GET /api/v1/public/products/{slug}' => 'Get product by slug',
                        'GET /api/v1/public/products/{id}/related' => 'Get related products'
                    ],
                    'categories' => [
                        'GET /api/v1/public/categories' => 'Get categories tree',
                        'GET /api/v1/public/categories/{id}' => 'Get category details'
                    ],
                    'search' => [
                        'GET /api/v1/public/search' => 'Search products',
                        'GET /api/v1/public/search/suggestions' => 'Get search suggestions'
                    ]
                ],
                'auth' => [
                    'POST /api/v1/auth/login' => 'User login',
                    'POST /api/v1/auth/register' => 'User registration',
                    'POST /api/v1/auth/logout' => 'User logout',
                    'GET /api/v1/auth/me' => 'Get user profile'
                ],
                'admin' => [
                    'GET /api/v1/admin/dashboard/stats' => 'Admin dashboard statistics',
                    'GET /api/v1/admin/users' => 'Get users list',
                    'GET /api/v1/admin/products' => 'Get products for review'
                ],
                'seller' => [
                    'GET /api/v1/seller/dashboard/stats' => 'Seller dashboard statistics',
                    'GET /api/v1/seller/products' => 'Get seller products',
                    'POST /api/v1/seller/products' => 'Create new product'
                ]
            ],
            'v2' => [
                'public' => [
                    'products' => [
                        'GET /api/v2/public/products' => 'Get products with enhanced metadata',
                        'GET /api/v2/public/products/{slug}' => 'Get product with analytics',
                        'GET /api/v2/public/products/{id}/price-history' => 'Get price history with analytics'
                    ]
                ],
                'auth' => [
                    'GET /api/v2/auth/api-keys' => 'Get user API keys',
                    'POST /api/v2/auth/api-keys' => 'Create new API key',
                    'POST /api/v2/auth/api-keys/{id}/regenerate' => 'Regenerate API key'
                ]
            ]
        ];

        return ApiResponse::success($endpoints, 'API endpoints retrieved successfully');
    }

    /**
     * Get API paths for OpenAPI
     */
    private function getApiPaths(): array
    {
        return [
            '/v1/public/products' => [
                'get' => [
                    'tags' => ['Products'],
                    'summary' => 'Get products list',
                    'parameters' => [
                        ['name' => 'category_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Products retrieved successfully']
                    ]
                ]
            ],
            '/v1/auth/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User login',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'password' => ['type' => 'string', 'minLength' => 6]
                                    ],
                                    'required' => ['email', 'password']
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Login successful'],
                        '401' => ['description' => 'Invalid credentials']
                    ]
                ]
            ]
        ];
    }

    /**
     * Get OpenAPI components
     */
    private function getComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Bearer Token'
                ]
            ],
            'schemas' => [
                'Product' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'price' => ['type' => 'number'],
                        'category_id' => ['type' => 'integer'],
                        'seller_id' => ['type' => 'integer']
                    ]
                ],
                'ApiResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean'],
                        'message' => ['type' => 'string'],
                        'data' => ['type' => 'object'],
                        'meta' => ['type' => 'object']
                    ]
                ]
            ]
        ];
    }

    /**
     * Get Postman collection items
     */
    private function getPostmanItems(): array
    {
        return [
            [
                'name' => 'Authentication',
                'item' => [
                    [
                        'name' => 'Login',
                        'request' => [
                            'method' => 'POST',
                            'header' => [
                                ['key' => 'Content-Type', 'value' => 'application/json']
                            ],
                            'url' => [
                                'raw' => '{{base_url}}/v1/auth/login',
                                'host' => ['{{base_url}}'],
                                'path' => ['v1', 'auth', 'login']
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => json_encode([
                                    'email' => 'user@example.com',
                                    'password' => 'password123'
                                ])
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Products',
                'item' => [
                    [
                        'name' => 'Get Products',
                        'request' => [
                            'method' => 'GET',
                            'url' => [
                                'raw' => '{{base_url}}/v1/public/products',
                                'host' => ['{{base_url}}'],
                                'path' => ['v1', 'public', 'products']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
