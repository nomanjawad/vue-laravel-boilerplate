<?php

/**
 * Virtual modules: v2 features registered with the module manager but not yet
 * (or never) moved into the app/Modules/{X}/ folder layout. They share the
 * existing routes/*.php files and admin Vue pages. New features should be
 * created with `php artisan make:module` (creates a physical module) instead
 * of adding to this list.
 *
 * Toggling a virtual module from the admin dashboard flips `enabled` in the
 * `modules` table; the routes file is only required if the manager says the
 * module is enabled, so disabling actually stops routes from registering.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Core modules — always-on
    |--------------------------------------------------------------------------
    |
    | These cannot be disabled from the admin UI. They underpin every other
    | module (auth/dashboard/menus/settings/media). Removing them would brick
    | the panel; the manager refuses.
    */

    'users' => [
        'name' => 'Users & Auth',
        'description' => 'Admin users, roles, password reset, module toggles.',
        'core' => true,
        'nav_group' => 'system',
        'permissions' => [
            'users' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'update'],
            // Module dashboard guard — admins only, never editor.
            'modules' => ['manage'],
            // Audit log guard — admins only. Reads the spatie/activitylog
            // stream (content CRUD + login events). See feedback.md §-
            // "who deleted this?" is the recurring question this closes.
            'audit_log' => ['view'],
        ],
        'nav' => [
            ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'permission' => 'users.view'],
            ['label' => 'Audit log', 'route' => 'admin.audit-log.index', 'icon' => 'clipboard-document-list', 'permission' => 'audit_log.view'],
        ],
    ],

    'settings' => [
        'name' => 'Site Settings',
        'description' => 'Branding, contact, SEO defaults, analytics ids, integrations.',
        'core' => true,
        'nav_group' => 'system',
        'permissions' => ['settings' => ['view', 'update']],
        'nav' => [
            ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'cog-6-tooth', 'permission' => 'settings.view'],
        ],
    ],

    'media' => [
        'name' => 'Media',
        'description' => 'Image uploads with WebP variants. Required by most content modules.',
        'core' => true,
        'nav_group' => 'content',
        'permissions' => ['media' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Media', 'route' => 'admin.media.index', 'icon' => 'photo', 'permission' => 'media.view'],
        ],
    ],

    'menus' => [
        'name' => 'Menus',
        'description' => 'Header & footer navigation builder.',
        'core' => true,
        'nav_group' => 'content',
        'permissions' => ['menus' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Menus', 'route' => 'admin.menus.index', 'icon' => 'bars-3', 'permission' => 'menus.view'],
        ],
    ],

    'page_content' => [
        'name' => 'Page Content',
        'description' => 'Static page content (home/about/contact + header/footer), stored in data/*.json. Each page carries its own SEO block.',
        'core' => true,
        'nav_group' => 'content',
        'permissions' => ['page_content' => ['view', 'update']],
        'nav' => [
            ['label' => 'Pages', 'route' => 'admin.page-content.index', 'icon' => 'document-text', 'permission' => 'page_content.view'],
            ['label' => 'Header / Footer', 'route' => 'admin.page-content.layout', 'icon' => 'bars-3', 'permission' => 'page_content.view'],
        ],
    ],

    'redirects' => [
        'name' => 'Redirects',
        'description' => '301 manager with 404 monitoring.',
        'core' => true,
        'nav_group' => 'system',
        'permissions' => ['redirects' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Redirects', 'route' => 'admin.redirects.index', 'icon' => 'arrow-path-rounded-square', 'permission' => 'redirects.view'],
        ],
    ],

    'subscribers' => [
        'name' => 'Newsletter',
        'description' => 'Email subscribers + CSV export.',
        'core' => true,
        'nav_group' => 'system',
        'permissions' => ['subscribers' => ['view', 'delete']],
        'nav' => [
            ['label' => 'Subscribers', 'route' => 'admin.subscribers.index', 'icon' => 'envelope', 'permission' => 'subscribers.view'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional modules — toggleable from the admin dashboard
    |--------------------------------------------------------------------------
    */

    'blog' => [
        'name' => 'Blog',
        'description' => 'Posts, categories, tags, author bylines, RSS, sitemap entries.',
        'dependencies' => ['media'],
        'nav_group' => 'content',
        'feature_flag_fallback' => 'blog',
        'permissions' => [
            'posts' => ['view', 'create', 'update', 'delete', 'publish'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'tags' => ['view', 'create', 'update', 'delete'],
        ],
        // Posts/Categories/Tags share one sidebar entry — the Posts index page
        // (and its siblings) render a BlogTabs bar to switch between them, so
        // the sidebar doesn't need three separate links for one content type.
        'nav' => [
            ['label' => 'Blog', 'route' => 'admin.posts.index', 'icon' => 'document-text', 'permission' => 'posts.view'],
        ],
        'admin_route_file' => 'admin-blog.php',
        'public_route_file' => 'public-blog.php',
        'searchable' => [
            \App\Models\Post::class => ['title', 'excerpt'],
            \App\Models\Category::class => ['name'],
        ],
    ],

    'shop' => [
        'name' => 'Shop',
        'description' => 'Products, orders, cart, currency settings.',
        'dependencies' => ['media'],
        'nav_group' => 'commerce',
        'feature_flag_fallback' => 'shop',
        'permissions' => [
            'products' => ['view', 'create', 'update', 'delete'],
            'orders' => ['view', 'update', 'delete'],
        ],
        'nav' => [
            ['label' => 'Products', 'route' => 'admin.products.index', 'icon' => 'shopping-bag', 'permission' => 'products.view'],
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'icon' => 'receipt-percent', 'permission' => 'orders.view'],
        ],
        'admin_route_file' => 'admin-shop.php',
        'public_route_file' => 'public-shop.php',
        'searchable' => [
            \App\Models\Product::class => ['name', 'description'],
            \App\Models\Order::class => ['order_number', 'customer_email'],
        ],
    ],

    'careers' => [
        'name' => 'Careers',
        'description' => 'Job listings with JobPosting JSON-LD.',
        'dependencies' => [],
        'nav_group' => 'content',
        'feature_flag_fallback' => 'careers',
        'permissions' => ['careers' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Careers', 'route' => 'admin.careers.index', 'icon' => 'briefcase', 'permission' => 'careers.view'],
        ],
        'admin_route_file' => 'admin-optional.php',
        'public_route_file' => 'public-optional.php',
        'searchable' => [
            \App\Models\Career::class => ['title', 'description'],
        ],
    ],

    'case_studies' => [
        'name' => 'Case Studies',
        'description' => 'Portfolio entries.',
        'dependencies' => ['media'],
        'nav_group' => 'content',
        'feature_flag_fallback' => 'case_studies',
        'permissions' => ['case_studies' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Case Studies', 'route' => 'admin.case-studies.index', 'icon' => 'rectangle-stack', 'permission' => 'case_studies.view'],
        ],
        'admin_route_file' => 'admin-optional.php',
        'public_route_file' => 'public-optional.php',
        'searchable' => [
            \App\Models\CaseStudy::class => ['title', 'body'],
        ],
    ],

    'teams' => [
        'name' => 'Team',
        'description' => 'Team member directory.',
        'dependencies' => ['media'],
        'nav_group' => 'content',
        'feature_flag_fallback' => 'teams',
        'permissions' => ['teams' => ['view', 'create', 'update', 'delete']],
        'nav' => [
            ['label' => 'Team', 'route' => 'admin.teams.index', 'icon' => 'user-group', 'permission' => 'teams.view'],
        ],
        'admin_route_file' => 'admin-optional.php',
        'public_route_file' => 'public-optional.php',
        'searchable' => [
            \App\Models\Team::class => ['name', 'position'],
        ],
    ],

];
