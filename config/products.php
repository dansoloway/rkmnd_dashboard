<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Consumer products (organizing principle for rkmnd dashboard)
    |--------------------------------------------------------------------------
    */

    'ai_search' => [
        'id' => 'ai_search',
        'label' => 'AI Video Search',
        'short_label' => 'AI Search',
        'consumer' => 'WordPress page-video-ai.php',
        'default_namespace' => 'v6_title_tags',
        'namespace_allow_list' => [
            'v6_title_only',
            'v6_title_tags',
            'v6_title_tags_short',
            'v6_title_tags_long',
            'v6_title_tags_short_long',
            'v6_title_tags_catalog',
            'v7',
        ],
        'catalog_filters' => [
            'post_type' => 'video',
        ],
        'search_pool_filters' => [
            'embedding_namespace' => 'v6_title_tags',
        ],
        'search_post_type' => null,
    ],

    'mow_row' => [
        'id' => 'mow_row',
        'label' => 'MOW/ROW PWA',
        'short_label' => 'MOW/ROW',
        'consumer' => 'mowrow.fitform100.net',
        'default_namespace' => 'mow_row_v6_title_tags',
        'namespace_allow_list' => [
            'mow_row_v6_title_tags',
        ],
        'catalog_filters' => [
            'embedding_namespace' => 'mow_row_v6_title_tags',
        ],
        'search_pool_filters' => [
            'embedding_namespace' => 'mow_row_v6_title_tags',
        ],
        'search_post_type' => 'scheduled',
    ],

];
