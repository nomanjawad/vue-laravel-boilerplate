<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { SharedPageProps } from '@/types/inertia'

// Auth layout brand pulls from settings.site_name reactively — matches
// PublicLayout.vue. Never hardcode a brand string here; feedback.md §5
// documented a real live-project bug where the hardcoded 'WebTemplate'
// leaked into every auth page's header.
const page = usePage<SharedPageProps>()
const siteName = computed(() => page.props.settings?.site_name ?? '')
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <Link href="/" class="text-2xl font-bold text-gray-900">{{ siteName }}</Link>
            </div>
            <div class="bg-white rounded-lg shadow-md p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
