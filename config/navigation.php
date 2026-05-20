<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary navigation (desktop top bar + mobile)
    |--------------------------------------------------------------------------
    */

    'primary' => [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => ['dashboard'],
        ],
        [
            'label' => 'Videos',
            'route' => 'videos.index',
            'active' => [
                'videos.index',
                'videos.show',
                'videos.database',
                'videos.embeddings-reconcile',
                'videos.search-visible-audio',
                'videos.namespace-studio',
                'videos.embedding-text',
            ],
            'children' => [
                [
                    'label' => 'Library',
                    'route' => 'videos.index',
                    'active' => ['videos.index', 'videos.show'],
                ],
                [
                    'label' => 'Metadata explorer',
                    'route' => 'videos.database',
                    'active' => ['videos.database'],
                ],
                [
                    'label' => 'Namespace studio',
                    'route' => 'videos.namespace-studio',
                    'active' => ['videos.namespace-studio', 'videos.embedding-text'],
                ],
                [
                    'label' => 'Search-visible audio',
                    'route' => 'videos.search-visible-audio',
                    'active' => ['videos.search-visible-audio'],
                ],
                [
                    'label' => 'Embeddings reconcile',
                    'route' => 'videos.embeddings-reconcile',
                    'active' => ['videos.embeddings-reconcile'],
                ],
            ],
        ],
        [
            'label' => 'AI Search',
            'route' => 'ai-search.index',
            'active' => ['ai-search.*'],
        ],
        [
            'label' => 'Analytics',
            'route' => 'analytics.index',
            'active' => ['analytics.*'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User menu (account, sync logs, logout)
    |--------------------------------------------------------------------------
    */

    'user' => [
        [
            'label' => 'Account',
            'route' => 'account.index',
            'active' => ['account.*'],
        ],
        [
            'label' => 'Sync logs',
            'route' => 'sync-logs.index',
            'active' => ['sync-logs.*'],
        ],
    ],

];
