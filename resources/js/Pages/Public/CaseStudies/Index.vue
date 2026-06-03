<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    caseStudies: Array,
})
</script>

<template>
    <Head title="Case Studies" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Case Studies</h1>
            <p class="mt-4 text-lg text-gray-300">See how we've helped our clients achieve their goals.</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="caseStudies.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <Link
                    v-for="cs in caseStudies"
                    :key="cs.id"
                    :href="`/case-studies/${cs.slug}`"
                    class="group"
                >
                    <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                        <img v-if="cs.featured_image" :src="cs.featured_image" :alt="cs.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <p v-if="cs.client_name" class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">{{ cs.client_name }}</p>
                    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ cs.title }}</h2>
                    <p v-if="cs.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ cs.excerpt }}</p>
                </Link>
            </div>

            <div v-else class="text-center py-16">
                <h2 class="text-xl font-semibold text-gray-900">No case studies yet</h2>
                <p class="mt-2 text-gray-500">Check back soon for our latest work.</p>
            </div>
        </div>
    </section>
</template>
