<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary navigation (product-first)
    |--------------------------------------------------------------------------
    */

    'primary' => [
        [
            'label' => 'Home',
            'route' => 'dashboard',
            'active' => ['dashboard'],
        ],
        [
            'label' => 'AI Video Search',
            'active' => ['ai-search.*'],
            'children' => [
                [
                    'label' => 'Library',
                    'route' => 'ai-search.videos.index',
                    'active' => ['ai-search.videos.*'],
                ],
                [
                    'label' => 'Semantic search',
                    'route' => 'ai-search.playground.index',
                    'active' => ['ai-search.playground.*'],
                ],
                [
                    'label' => 'Anatomy dictionary',
                    'route' => 'ai-search.vocabulary.index',
                    'active' => ['ai-search.vocabulary.*'],
                ],
                [
                    'label' => 'Catalog terms',
                    'route' => 'ai-search.catalog-terms.index',
                    'active' => ['ai-search.catalog-terms.*'],
                ],
                [
                    'label' => 'Search-visible audio',
                    'route' => 'ai-search.search-visible-audio',
                    'active' => ['ai-search.search-visible-audio'],
                ],
                [
                    'label' => 'Namespace studio',
                    'route' => 'ai-search.namespace-studio',
                    'active' => ['ai-search.namespace-studio', 'ai-search.embedding-text'],
                ],
                [
                    'label' => 'Analytics',
                    'route' => 'ai-search.analytics',
                    'active' => ['ai-search.analytics*'],
                ],
            ],
        ],
        [
            'label' => 'MOW/ROW',
            'active' => ['mow-row.*'],
            'children' => [
                [
                    'label' => 'Catalog',
                    'route' => 'mow-row.catalog',
                    'active' => ['mow-row.catalog'],
                ],
                [
                    'label' => 'Semantic search',
                    'route' => 'mow-row.search.index',
                    'active' => ['mow-row.search.*'],
                ],
                [
                    'label' => 'Namespace studio',
                    'route' => 'mow-row.namespace-studio',
                    'active' => ['mow-row.namespace-studio', 'mow-row.embedding-text'],
                ],
                [
                    'label' => 'Featured this week',
                    'route' => 'mow-row.featured',
                    'active' => ['mow-row.featured'],
                ],
            ],
        ],
        [
            'label' => 'Platform',
            'active' => ['sync-logs.*', 'account.*', 'videos.database', 'videos.embeddings-reconcile', 'query.*'],
            'children' => [
                [
                    'label' => 'Sync logs',
                    'route' => 'sync-logs.index',
                    'active' => ['sync-logs.*'],
                ],
                [
                    'label' => 'Metadata explorer',
                    'route' => 'videos.database',
                    'active' => ['videos.database', 'videos.database-export', 'query.*'],
                ],
                [
                    'label' => 'Embeddings reconcile',
                    'route' => 'videos.embeddings-reconcile',
                    'active' => ['videos.embeddings-reconcile'],
                ],
                [
                    'label' => 'Account',
                    'route' => 'account.index',
                    'active' => ['account.*'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User menu (logout only — account moved under Platform)
    |--------------------------------------------------------------------------
    */

    'user' => [],

];
