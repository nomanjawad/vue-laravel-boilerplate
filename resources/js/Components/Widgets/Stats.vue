<script setup lang="ts">
interface StatItem {
    value?: string | number
    label?: string
}

interface Props {
    data?: {
        title?: string | null
        items?: StatItem[]
    }
}

withDefaults(defineProps<Props>(), {
    data: () => ({ items: [] }),
})
</script>

<template>
    <section v-if="data?.items?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data?.title" class="text-2xl font-bold text-gray-900 text-center mb-12">{{ data.title }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="(stat, i) in data.items" :key="i" class="text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ stat.value }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
