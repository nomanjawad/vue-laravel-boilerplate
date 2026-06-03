<script setup>
import { Link, Head } from '@inertiajs/vue3'
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <Link href="/" class="text-2xl font-bold text-gray-900">WebTemplate</Link>
            </div>
            <div class="bg-white rounded-lg shadow-md p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
