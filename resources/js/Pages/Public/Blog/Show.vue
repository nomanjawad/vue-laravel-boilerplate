<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PublicBreadcrumbs from '@/Components/Molecules/PublicBreadcrumbs.vue'
import AppImage, { type AppImageMedia } from '@/Components/Atoms/AppImage.vue'
import { Link, Head } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: PublicLayout })

interface BlogPostCategory {
    id: number
    name: string
    slug: string
}

interface BlogPostTag {
    id: number
    name: string
    slug: string
}

interface BlogPost {
    id: number
    slug: string
    title: string
    body: string
    excerpt?: string | null
    featured_image?: string | AppImageMedia | null
    published_at: string
    categories?: BlogPostCategory[]
    user?: { id: number; name: string } | null
    tags?: BlogPostTag[]
}

interface RelatedPost {
    id: number
    slug: string
    title: string
    featured_image?: string | AppImageMedia | null
}

interface BreadcrumbItem {
    name: string
    url: string
}

interface LcpPreload {
    href: string
    imagesrcset?: string
    imagesizes?: string
}

interface Props {
    post: BlogPost
    relatedPosts?: RelatedPost[] | null
    breadcrumbs?: BreadcrumbItem[]
    lcpPreload?: string | LcpPreload | null
}

const props = withDefaults(defineProps<Props>(), {
    relatedPosts: null,
    breadcrumbs: () => [],
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
    <div class="page-blog">
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

        <PublicBreadcrumbs :items="breadcrumbs" />

        <article class="py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500 mb-4">
                        <template v-if="post.categories?.length">
                            <Link
                                v-for="cat in post.categories"
                                :key="cat.id"
                                :href="`/blog/category/${cat.slug}`"
                                class="hover:text-gray-700"
                            >
                                {{ cat.name }}
                            </Link>
                            <span>&middot;</span>
                        </template>
                        <span>{{ new Date(post.published_at).toLocaleDateString() }}</span>
                        <span>&middot;</span>
                        <span>{{ post.user?.name }}</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ post.title }}</h1>
                    <p v-if="post.excerpt" class="text-lg text-gray-600">{{ post.excerpt }}</p>
                </div>

                <div v-if="post.featured_image" class="aspect-video bg-gray-100 rounded-lg mb-8 overflow-hidden">
                    <AppImage
                        :src="post.featured_image"
                        :alt="post.title"
                        eager
                        sizes="(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="cms-prose prose prose-gray max-w-none" v-html="post.body" />

                <div v-if="post.tags?.length" class="mt-8 flex flex-wrap gap-2">
                    <span v-for="tag in post.tags" :key="tag.id" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                        {{ tag.name }}
                    </span>
                </div>
            </div>
        </article>

        <section v-if="relatedPosts?.length" class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Posts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <Link v-for="rp in relatedPosts" :key="rp.id" :href="`/blog/${rp.slug}`" class="group">
                        <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                            <AppImage
                                v-if="rp.featured_image"
                                :src="rp.featured_image"
                                :alt="rp.title"
                                sizes="(max-width: 768px) 100vw, 33vw"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-gray-600">{{ rp.title }}</h3>
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
