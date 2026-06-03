<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    careers: Array,
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
    <Head title="Careers" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Careers</h1>
            <p class="mt-4 text-lg text-gray-300">Join our team and help us build something amazing.</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="careers.length" class="space-y-4">
                <Link
                    v-for="career in careers"
                    :key="career.id"
                    :href="`/careers/${career.slug}`"
                    class="block bg-white rounded-lg shadow hover:shadow-md transition-shadow p-6 group"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ career.title }}</h2>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                <span v-if="career.department">{{ career.department }}</span>
                                <span v-if="career.department && career.location">&middot;</span>
                                <span v-if="career.location">{{ career.location }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-medium', typeColor(career.type)]">
                                {{ typeLabel(career.type) }}
                            </span>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="text-center py-16">
                <h2 class="text-xl font-semibold text-gray-900">No open positions</h2>
                <p class="mt-2 text-gray-500">We don't have any openings right now, but check back soon!</p>
            </div>
        </div>
    </section>
</template>
