<?php

/**
 * Module manifest for Events.
 *
 * Edit this file to declare permissions, sidebar nav, dependencies, etc.
 * Every key here flows into:
 *   - the admin Modules page (`/admin/modules`)
 *   - the RBAC permission seeder
 *   - the sidebar
 *   - the global search index
 */
return [
    'key'          => 'events',
    'name'         => 'Events',
    'description'  => 'Events with dates, locations, and public listings.',
    'version'      => '1.0.0',
    'dependencies' => ['media'],
    'permissions'  => [
        'events' => ['view', 'create', 'update', 'delete'],
    ],
    'nav'          => [
        ['label' => 'Events', 'route' => 'admin.events.index', 'icon' => 'calendar-days', 'permission' => 'events.view'],
    ],
    'searchable'   => [
        \App\Modules\Events\Models\Event::class => ['title', 'body'],
    ],
];
