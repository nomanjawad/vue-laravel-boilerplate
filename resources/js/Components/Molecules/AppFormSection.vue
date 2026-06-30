<script setup>
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
})
</script>

<template>
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <header v-if="title || description" class="mb-4">
            <h2 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
        </header>
        <div class="space-y-4">
            <slot />
        </div>
    </section>
</template>
