<script setup>
// Tiny built-in icon set keyed by name. Pure SVG, no external dep.
// Extend the `paths` map below as needed. Names match the manifest icon keys.
const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 18 },
})

const paths = {
    dashboard: 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z',
    users: 'M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zM8 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.7 0-8 1.3-8 4v3h16v-3c0-2.7-5.3-4-8-4zm8 0c-.4 0-.8 0-1.2.1A5.6 5.6 0 0 1 17 17v3h7v-3c0-2.7-5.3-4-8-4z',
    posts: 'M4 4h16v2H4zM4 11h16v2H4zM4 18h10v2H4z',
    categories: 'M3 3h7v7H3zm11 0h7v4h-7zm0 6h7v12h-7zm-11 5h7v7H3z',
    tags: 'M21 12.59 12.41 4H4v8.41L12.59 21z',
    products: 'M3 7l1-3h16l1 3M3 7v13h18V7M3 7h18M9 11h6',
    orders: 'M3 4h2l3 12h11l3-9H6',
    careers: 'M9 3h6a2 2 0 0 1 2 2v2h4v12H3V7h4V5a2 2 0 0 1 2-2zm0 4h6V5H9z',
    'case-studies': 'M4 3h16v18l-8-4-8 4z',
    team: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z',
    media: 'M3 5h18v14H3zm3 8 3 4 4-5 5 6H6z',
    menus: 'M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z',
    seo: 'M11 3a8 8 0 1 0 5.3 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 11 3zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z',
    settings: 'M19.43 12.98a8 8 0 0 0 0-1.96l2.11-1.65-2-3.46-2.49 1a8.13 8.13 0 0 0-1.7-1l-.38-2.65h-4l-.38 2.65a8.13 8.13 0 0 0-1.7 1l-2.49-1-2 3.46 2.11 1.65a8 8 0 0 0 0 1.96L1.4 14.63l2 3.46 2.49-1a8.13 8.13 0 0 0 1.7 1l.38 2.65h4l.38-2.65a8.13 8.13 0 0 0 1.7-1l2.49 1 2-3.46zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z',
    modules: 'M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 0h8v8h-8z',
    cube: 'M12 2 2 7v10l10 5 10-5V7zm0 2.2L19.4 8 12 11.8 4.6 8zM4 9.5l7 3.5v7.6L4 17z',
}

const path = paths[props.name] ?? paths.cube
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="currentColor"
        aria-hidden="true"
    >
        <path :d="path" />
    </svg>
</template>
