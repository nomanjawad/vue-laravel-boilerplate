<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    career: Object,
})

const typeLabel = (type) => {
    const labels = {
        'full-time': 'Full Time',
        'part-time': 'Part Time',
        'contract': 'Contract',
        'remote': 'Remote',
    }
    return labels[type] || type
}

const typeColor = (type) => {
    const colors = {
        'full-time': 'bg-blue-100 text-blue-800',
        'part-time': 'bg-purple-100 text-purple-800',
        'contract': 'bg-orange-100 text-orange-800',
        'remote': 'bg-green-100 text-green-800',
    }
    return colors[type] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head :title="career.title" />

    <article class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back link -->
            <Link href="/careers" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Careers
            </Link>

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ career.title }}</h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    <span v-if="career.department" class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ career.department }}
                    </span>
                    <span v-if="career.location" class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ career.location }}
                    </span>
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-medium', typeColor(career.type)]">
                        {{ typeLabel(career.type) }}
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
                <div class="prose prose-gray max-w-none" v-html="career.description" />
            </div>

            <!-- Requirements -->
            <div v-if="career.requirements" class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Requirements</h2>
                <div class="prose prose-gray max-w-none" v-html="career.requirements" />
            </div>

            <!-- CTA -->
            <div class="border-t border-gray-200 pt-8 mt-8">
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Interested in this position?</h3>
                    <p class="text-gray-500 mb-4">We'd love to hear from you. Send us your application today.</p>
                    <Link href="/contact" class="inline-flex items-center px-6 py-3 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">
                        Apply Now
                    </Link>
                </div>
            </div>
        </div>
    </article>
</template>
