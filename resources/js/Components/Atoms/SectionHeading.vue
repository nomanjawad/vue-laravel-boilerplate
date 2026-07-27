<script setup lang="ts">
/** Atom: section heading with optional "view all" link on the right. */
import { Link } from '@inertiajs/vue3'

interface Props {
    title: string
    linkHref?: string | null
    linkText?: string
}

withDefaults(defineProps<Props>(), {
    linkHref: null,
    linkText: 'View All →',
})
</script>

<template>
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
        <Link v-if="linkHref" :href="linkHref" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            {{ linkText }}
        </Link>
    </div>
</template>
