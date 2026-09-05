<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Link, Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppImage, { type AppImageMedia } from '@/Components/Atoms/AppImage.vue'

defineOptions({ layout: PublicLayout })

interface CaseStudy {
    id: number
    slug: string
    title: string
    body?: string | null
    excerpt?: string | null
    featured_image?: string | AppImageMedia | null
    client_name?: string | null
}

interface LcpPreload {
    href: string
    imagesrcset?: string
    imagesizes?: string
}

interface Props {
    caseStudy: CaseStudy
    lcpPreload?: string | LcpPreload | null
}

const props = withDefaults(defineProps<Props>(), {
    lcpPreload: null,
})

const lcpHref = computed(() => {
    const p = props.lcpPreload
    if (!p) return null
    return typeof p === 'string' ? p : p.href
})
const lcpSrcset = computed(() => {
    const p = props.lcpPreload
    return p && typeof p === 'object' ? p.imagesrcset : undefined
})
const lcpSizes = computed(() => {
    const p = props.lcpPreload
    return p && typeof p === 'object' ? p.imagesizes : undefined
})
</script>

<template>
    <Head>
        <link
            v-if="lcpHref"
            head-key="lcp-preload"
            rel="preload"
            as="image"
            :href="lcpHref"
            :imagesrcset="lcpSrcset"
            :imagesizes="lcpSrcset ? lcpSizes : undefined"
        />
    </Head>

    <article class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <Link href="/case-studies" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Case Studies
            </Link>

            <div class="mb-8">
                <p v-if="caseStudy.client_name" class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">{{ caseStudy.client_name }}</p>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ caseStudy.title }}</h1>
                <p v-if="caseStudy.excerpt" class="text-lg text-gray-600">{{ caseStudy.excerpt }}</p>
            </div>

            <div v-if="caseStudy.featured_image" class="aspect-video bg-gray-100 rounded-lg mb-8 overflow-hidden">
                <AppImage
                    :src="caseStudy.featured_image"
                    :alt="caseStudy.title"
                    eager
                    sizes="(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="prose prose-gray max-w-none" v-html="caseStudy.body" />

            <div class="border-t border-gray-200 pt-8 mt-12">
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Ready to start your project?</h3>
                    <p class="text-gray-500 mb-4">Let's discuss how we can help you achieve similar results.</p>
                    <Link href="/contact" class="inline-flex items-center px-6 py-3 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">
                        Get in Touch
                    </Link>
                </div>
            </div>
        </div>
    </article>
</template>
