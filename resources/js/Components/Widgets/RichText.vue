<script setup lang="ts">
interface Props {
    data?: {
        title?: string | null
        body?: string | null
    }
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
})
</script>

<template>
    <section v-if="data?.title || data?.body" class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data?.title" class="text-2xl font-bold text-gray-900 mb-6 text-center">{{ data.title }}</h2>
            <div v-if="data?.body" class="prose prose-gray max-w-none text-gray-600 leading-relaxed" v-html="data.body" />
        </div>
    </section>
</template>
