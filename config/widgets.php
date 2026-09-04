<?php

/**
 * Widget type registry for JSON-backed pages (data/pages/{slug}.json).
 *
 * MUST stay serializable — no closures — so `php artisan config:cache` /
 * `optimize` succeed. Field types: text, textarea, richtext, image, link,
 * boolean, number, select, repeater, collection.
 *
 * Collection widgets pull live module content via WidgetDataResolver.
 * FAQs default mode is `current_page` (Phase 6.5 wires page-wise filtering;
 * until then the resolver returns latest/active FAQs).
 */
return [

    'hero' => [
        'key' => 'hero',
        'label' => 'Hero',
        'icon' => 'sparkles',
        'fields' => [
            ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => '', 'placeholder' => 'Build Something Amazing'],
            ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => '', 'placeholder' => 'A short supporting sentence under the headline'],
            ['key' => 'image', 'label' => 'Background image', 'type' => 'image', 'default' => '', 'placeholder' => 'Optional hero image URL'],
            ['key' => 'cta_text', 'label' => 'Primary CTA text', 'type' => 'text', 'default' => '', 'placeholder' => 'Get Started'],
            ['key' => 'cta_url', 'label' => 'Primary CTA URL', 'type' => 'link', 'default' => '', 'placeholder' => '/contact'],
            ['key' => 'secondary_cta_text', 'label' => 'Secondary CTA text', 'type' => 'text', 'default' => '', 'placeholder' => 'Learn More'],
            ['key' => 'secondary_cta_url', 'label' => 'Secondary CTA URL', 'type' => 'link', 'default' => '', 'placeholder' => '/about'],
        ],
    ],

    'rich_text' => [
        'key' => 'rich_text',
        'label' => 'Rich Text',
        'icon' => 'document-text',
        'fields' => [
            ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => '', 'placeholder' => 'Section heading'],
            ['key' => 'body', 'label' => 'Body', 'type' => 'richtext', 'default' => '', 'placeholder' => 'Write your content here…'],
        ],
    ],

    'feature_grid' => [
        'key' => 'feature_grid',
        'label' => 'Feature Grid',
        'icon' => 'squares-2x2',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => '', 'placeholder' => 'Our Values'],
            [
                'key' => 'items',
                'label' => 'Features',
                'type' => 'repeater',
                'default' => [],
                'placeholder' => 'Add a feature card',
                'item_fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => '', 'placeholder' => 'Modern Stack'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'default' => '', 'placeholder' => 'Short description of this feature'],
                    ['key' => 'icon', 'label' => 'Icon key', 'type' => 'text', 'default' => '', 'placeholder' => 'code'],
                ],
            ],
        ],
    ],

    'stats' => [
        'key' => 'stats',
        'label' => 'Stats',
        'icon' => 'chart-bar',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => '', 'placeholder' => 'By the numbers'],
            [
                'key' => 'items',
                'label' => 'Stats',
                'type' => 'repeater',
                'default' => [],
                'placeholder' => 'Add a stat',
                'item_fields' => [
                    ['key' => 'value', 'label' => 'Value', 'type' => 'text', 'default' => '', 'placeholder' => '99%'],
                    ['key' => 'label', 'label' => 'Label', 'type' => 'text', 'default' => '', 'placeholder' => 'Uptime'],
                ],
            ],
        ],
    ],

    'cta' => [
        'key' => 'cta',
        'label' => 'Call to Action',
        'icon' => 'megaphone',
        'fields' => [
            ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => '', 'placeholder' => 'Ready to get started?'],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'default' => '', 'placeholder' => 'Contact us today and let\'s build something great together.'],
            ['key' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => '', 'placeholder' => 'Contact Us'],
            ['key' => 'button_url', 'label' => 'Button URL', 'type' => 'link', 'default' => '', 'placeholder' => '/contact'],
        ],
    ],

    'image' => [
        'key' => 'image',
        'label' => 'Image',
        'icon' => 'photo',
        'fields' => [
            ['key' => 'src', 'label' => 'Image', 'type' => 'image', 'default' => '', 'placeholder' => 'Select an image'],
            ['key' => 'alt', 'label' => 'Alt text', 'type' => 'text', 'default' => '', 'placeholder' => 'Describe the image'],
            ['key' => 'caption', 'label' => 'Caption', 'type' => 'text', 'default' => '', 'placeholder' => 'Optional caption'],
        ],
    ],

    'gallery' => [
        'key' => 'gallery',
        'label' => 'Gallery',
        'icon' => 'squares-plus',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => '', 'placeholder' => 'Gallery'],
            [
                'key' => 'images',
                'label' => 'Images',
                'type' => 'repeater',
                'default' => [],
                'placeholder' => 'Add an image',
                'item_fields' => [
                    ['key' => 'src', 'label' => 'Image', 'type' => 'image', 'default' => '', 'placeholder' => 'Select an image'],
                    ['key' => 'alt', 'label' => 'Alt text', 'type' => 'text', 'default' => '', 'placeholder' => 'Describe the image'],
                ],
            ],
        ],
    ],

    'testimonials' => [
        'key' => 'testimonials',
        'label' => 'Testimonials',
        'icon' => 'chat-bubble-left-right',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'What clients say', 'placeholder' => 'What clients say'],
            [
                'key' => 'collection',
                'label' => 'Source',
                'type' => 'collection',
                'default' => ['mode' => 'latest', 'ids' => [], 'limit' => 6],
                'placeholder' => 'Pull from Testimonials module',
                'source' => 'testimonials',
                'mode' => 'latest',
                'limit' => 6,
            ],
        ],
    ],

    'faqs' => [
        'key' => 'faqs',
        'label' => 'FAQs',
        'icon' => 'question-mark-circle',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Frequently asked questions', 'placeholder' => 'Frequently asked questions'],
            [
                'key' => 'collection',
                'label' => 'Source',
                'type' => 'collection',
                'default' => ['mode' => 'current_page', 'ids' => [], 'page_slug' => '', 'limit' => 20],
                'placeholder' => 'FAQs for this page (falls back to global)',
                'source' => 'faqs',
                'mode' => 'current_page',
                'limit' => 20,
            ],
        ],
    ],

    'team' => [
        'key' => 'team',
        'label' => 'Team',
        'icon' => 'user-group',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Our Team', 'placeholder' => 'Our Team'],
            [
                'key' => 'collection',
                'label' => 'Source',
                'type' => 'collection',
                'default' => ['mode' => 'latest', 'ids' => [], 'limit' => 8],
                'placeholder' => 'Pull from Team module',
                'source' => 'teams',
                'mode' => 'latest',
                'limit' => 8,
            ],
        ],
    ],

    'latest_posts' => [
        'key' => 'latest_posts',
        'label' => 'Latest Posts',
        'icon' => 'newspaper',
        'fields' => [
            ['key' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Latest Posts', 'placeholder' => 'Latest Posts'],
            [
                'key' => 'collection',
                'label' => 'Source',
                'type' => 'collection',
                'default' => ['mode' => 'latest', 'ids' => [], 'limit' => 3],
                'placeholder' => 'Pull from Blog posts',
                'source' => 'blog',
                'mode' => 'latest',
                'limit' => 3,
            ],
        ],
    ],

    'contact_form' => [
        'key' => 'contact_form',
        'label' => 'Contact Form',
        'icon' => 'envelope',
        'fields' => [
            ['key' => 'heading', 'label' => 'Form heading', 'type' => 'text', 'default' => 'Send us a message', 'placeholder' => 'Send us a message'],
            ['key' => 'info_heading', 'label' => 'Info heading', 'type' => 'text', 'default' => 'Get in touch', 'placeholder' => 'Get in touch'],
            ['key' => 'office_hours', 'label' => 'Office hours', 'type' => 'text', 'default' => '', 'placeholder' => 'Monday - Friday: 9:00 AM - 6:00 PM'],
            ['key' => 'map_embed', 'label' => 'Map embed HTML', 'type' => 'textarea', 'default' => '', 'placeholder' => '<iframe src="…" loading="lazy"></iframe>'],
            ['key' => 'show_contact_info', 'label' => 'Show contact info', 'type' => 'boolean', 'default' => true, 'placeholder' => 'Show email/phone from settings'],
        ],
    ],

    'custom_html' => [
        'key' => 'custom_html',
        'label' => 'Custom HTML',
        'icon' => 'code-bracket',
        'fields' => [
            ['key' => 'html', 'label' => 'HTML', 'type' => 'textarea', 'default' => '', 'placeholder' => '<div>Custom markup</div> — iframes should include loading="lazy"'],
        ],
    ],

];
