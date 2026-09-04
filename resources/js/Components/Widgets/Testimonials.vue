<script setup lang="ts">
interface TestimonialItem {
    id?: number
    name?: string
    role?: string
    company?: string
    content?: string
    quote?: string
    body?: string
}

interface Props {
    data?: { title?: string | null }
    items?: TestimonialItem[]
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
    items: () => [],
})
</script>

<template>
    <section v-if="items.length" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">{{ data?.title || 'What clients say' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <blockquote v-for="(t, i) in items" :key="t.id ?? i" class="bg-gray-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-4">“{{ t.content || t.quote || t.body }}”</p>
                    <footer class="text-sm font-medium text-gray-900">
                        {{ t.name }}
                        <span v-if="t.role || t.company" class="text-gray-500 font-normal">
                            — {{ [t.role, t.company].filter(Boolean).join(', ') }}
                        </span>
                    </footer>
                </blockquote>
            </div>
        </div>
    </section>
</template>
