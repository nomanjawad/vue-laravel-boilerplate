<?php

return [
    'features' => [
        'blog'         => env('FEATURE_BLOG', true),
        'shop'         => env('FEATURE_SHOP', true),
        'careers'      => env('FEATURE_CAREERS', false),
        'case_studies' => env('FEATURE_CASE_STUDIES', false),
        'teams'        => env('FEATURE_TEAMS', true),
        'contact_form' => env('FEATURE_CONTACT_FORM', true),
    ],
];
