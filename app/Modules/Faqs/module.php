<?php

/**
 * Module manifest for Faqs.
 *
 * Edit this file to declare permissions, sidebar nav, dependencies, etc.
 * Every key here flows into:
 *   - the admin Modules page (`/admin/modules`)
 *   - the RBAC permission seeder
 *   - the sidebar
 *   - the global search index
 */
return [
    'key'          => 'faqs',
    'name'         => 'FAQs',
    'description'  => 'Frequently asked questions grouped by category.',
    'version'      => '1.0.0',
    'dependencies' => [],
    'nav_group'    => 'content',
    'permissions'  => [
        'faqs' => ['view', 'create', 'update', 'delete'],
    ],
    'nav'          => [
        ['label' => 'FAQs', 'route' => 'admin.faqs.index', 'icon' => 'question-mark-circle', 'permission' => 'faqs.view'],
    ],
    'searchable'   => [
        \App\Modules\Faqs\Models\Faq::class => ['title', 'body'],
    ],
];
