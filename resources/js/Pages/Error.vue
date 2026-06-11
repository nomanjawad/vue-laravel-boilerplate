<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    status: { type: Number, default: 500 },
})

// Placeholder branding — adjust copy per project.
const messages = {
    403: { title: 'Forbidden', message: "Sorry, you don't have permission to access this page." },
    404: { title: 'Page Not Found', message: "The page you're looking for doesn't exist or has been moved." },
    419: { title: 'Page Expired', message: 'Your session expired. Please refresh and try again.' },
    429: { title: 'Too Many Requests', message: "You're going a little fast. Please wait a moment and try again." },
    500: { title: 'Server Error', message: 'Something went wrong on our end. We have been notified.' },
    503: { title: 'Under Maintenance', message: "We're doing some quick maintenance. Please check back shortly." },
}

const info = computed(() => messages[props.status] ?? messages[500])
</script>

<template>
    <Head :title="info.title" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-6 text-center">
        <p class="text-7xl font-bold tracking-tight text-indigo-600">{{ status }}</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">{{ info.title }}</h1>
        <p class="mt-2 max-w-md text-gray-600">{{ info.message }}</p>
        <div class="mt-8 flex gap-4">
            <Link href="/" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Back to home
            </Link>
            <button
                v-if="status === 419 || status === 503"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                @click="$inertia.reload()"
            >
                Try again
            </button>
        </div>
    </div>
</template>
