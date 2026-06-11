<script setup>
defineProps({
    stats: { type: Array, default: () => [] },
})
</script>

<template>
    <section v-if="stats.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="(stat, i) in stats" :key="i" class="text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ stat.value }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
