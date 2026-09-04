<?php

return [
    // When false, the site actively discourages search engines from
    // indexing it (noindex meta tag, X-Robots-Tag header, blocking
    // robots.txt). Set SEO_INDEXABLE=true in production once ready.
    'indexable' => env('SEO_INDEXABLE', false),

    'features' => [
        'blog'         => env('FEATURE_BLOG', true),
        'careers'      => env('FEATURE_CAREERS', false),
        'case_studies' => env('FEATURE_CASE_STUDIES', false),
        'teams'        => env('FEATURE_TEAMS', true),
        'contact_form' => env('FEATURE_CONTACT_FORM', true),
    ],

    // Admin Menus location tabs + MenuController location validation.
    'menu_locations' => [
        'header' => 'Header',
        'footer' => 'Footer',
    ],
];
