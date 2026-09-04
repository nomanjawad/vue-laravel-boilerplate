<script setup lang="ts">
interface FaqItem {
    id?: number
    question?: string
    answer?: string
}

interface Props {
    data?: { title?: string | null }
    items?: FaqItem[]
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
    items: () => [],
})
</script>

<template>
    <section v-if="items.length" class="py-16 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">{{ data?.title || 'FAQs' }}</h2>
            <div class="space-y-4">
                <details v-for="(faq, i) in items" :key="faq.id ?? i" class="bg-white p-4 rounded-lg">
                    <summary class="font-medium text-gray-900 cursor-pointer">{{ faq.question }}</summary>
                    <div class="mt-2 text-gray-600 prose prose-sm max-w-none" v-html="faq.answer" />
                </details>
            </div>
        </div>
    </section>
</template>
