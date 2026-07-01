<?php

/**
 * Module manifest for Testimonials.
 *
 * Edit this file to declare permissions, sidebar nav, dependencies, etc.
 * Every key here flows into:
 *   - the admin Modules page (`/admin/modules`)
 *   - the RBAC permission seeder
 *   - the sidebar
 *   - the global search index
 */
return [
    'key'          => 'testimonials',
    'name'         => 'Testimonials',
    'description'  => 'Client testimonials with author name, quote, and optional photo.',
    'version'      => '1.0.0',
    'dependencies' => ['media'],
    'permissions'  => [
        'testimonials' => ['view', 'create', 'update', 'delete'],
    ],
    'nav'          => [
        ['label' => 'Testimonials', 'route' => 'admin.testimonials.index', 'icon' => 'chat-bubble-left-right', 'permission' => 'testimonials.view'],
    ],
    'searchable'   => [
        \App\Modules\Testimonials\Models\Testimonial::class => ['title', 'body'],
    ],
];
