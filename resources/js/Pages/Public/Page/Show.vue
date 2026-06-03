<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    page: Object,
})
</script>

<template>
    <Head :title="page.meta_title || page.title" />

    <section class="py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ page.title }}</h1>
            <div class="prose prose-gray max-w-none" v-html="page.body" />
        </div>
    </section>
</template>
