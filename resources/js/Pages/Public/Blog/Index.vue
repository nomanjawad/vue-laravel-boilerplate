<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: PublicLayout })

interface BlogListPost {
    id: number
    slug: string
    title: string
    excerpt?: string | null
    featured_image?: string | null
    published_at: string
    categories?: { id: number; name: string; slug: string }[]
}

interface BlogCategory {
    id: number
    name: string
    slug: string
    posts_count?: number
}

interface BlogFilters {
    search?: string | null
    category?: string | null
}

interface ArchiveInfo {
    name: string
    description?: string | null
    slug: string
}

interface Props {
    posts: Illuminate.LengthAwarePaginator<number, BlogListPost>
    categories: BlogCategory[]
    filters?: BlogFilters | null
    archive?: ArchiveInfo | null
}

const props = defineProps<Props>()

const search = ref<string>(props.filters?.search || '')

watch(search, () => {
    const base = props.archive ? `/blog/category/${props.archive.slug}` : '/blog'
    router.get(base, { search: search.value || undefined }, { preserveState: true, replace: true })
})

function onCategoryChange(event: Event) {
    const slug = (event.target as HTMLSelectElement).value
    if (!slug) {
        router.get('/blog', { search: search.value || undefined }, { preserveState: true, replace: true })
        return
    }
    router.get(`/blog/category/${slug}`, { search: search.value || undefined }, { preserveState: true, replace: true })
}
</script>

<template>
    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p v-if="archive" class="mb-2 text-sm uppercase tracking-wide text-gray-400">Category</p>
            <h1 class="text-4xl font-bold">{{ archive?.name || 'Blog' }}</h1>
            <p v-if="archive?.description" class="mx-auto mt-4 max-w-2xl text-gray-300">{{ archive.description }}</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-3 mb-8">
                <input v-model="search" type="text" placeholder="Search posts..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none" />
                <select
                    :value="filters?.category || ''"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                    @change="onCategoryChange"
                >
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }} ({{ cat.posts_count }})</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <article v-for="post in posts.data" :key="post.id" class="group">
                    <Link :href="`/blog/${post.slug}`">
                        <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                            <img v-if="post.featured_image" :src="post.featured_image" :alt="post.title" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        </div>
                    </Link>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 mb-2">
                        <template v-if="post.categories?.length">
                            <Link
                                v-for="cat in post.categories"
                                :key="cat.id"
                                :href="`/blog/category/${cat.slug}`"
                                class="hover:text-gray-800"
                            >
                                {{ cat.name }}
                            </Link>
                            <span>&middot;</span>
                        </template>
                        <span>{{ new Date(post.published_at).toLocaleDateString() }}</span>
                    </div>
                    <Link :href="`/blog/${post.slug}`">
                        <h2 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ post.title }}</h2>
                        <p v-if="post.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ post.excerpt }}</p>
                    </Link>
                </article>
            </div>

            <div v-if="posts.links && posts.links.length > 3" class="mt-12 flex justify-center">
                <nav class="flex space-x-1">
                    <Link v-for="link in posts.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
                </nav>
            </div>
        </div>
    </section>
</template>
