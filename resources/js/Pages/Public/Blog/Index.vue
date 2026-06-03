<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({ posts: Object, categories: Array, filters: Object })

const search = ref(props.filters?.search || '')
const category = ref(props.filters?.category || '')

watch([search, category], () => {
    router.get('/blog', { search: search.value, category: category.value }, { preserveState: true, replace: true })
})
</script>

<template>
    <Head title="Blog" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Blog</h1>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-3 mb-8">
                <input v-model="search" type="text" placeholder="Search posts..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none" />
                <select v-model="category" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }} ({{ cat.posts_count }})</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <Link v-for="post in posts.data" :key="post.id" :href="`/blog/${post.slug}`" class="group">
                    <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                        <img v-if="post.featured_image" :src="post.featured_image" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="flex items-center text-xs text-gray-500 mb-2 space-x-2">
                        <span v-if="post.category">{{ post.category.name }}</span>
                        <span>&middot;</span>
                        <span>{{ new Date(post.published_at).toLocaleDateString() }}</span>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ post.title }}</h2>
                    <p v-if="post.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ post.excerpt }}</p>
                </Link>
            </div>

            <div v-if="posts.links && posts.links.length > 3" class="mt-12 flex justify-center">
                <nav class="flex space-x-1">
                    <Link v-for="link in posts.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
                </nav>
            </div>
        </div>
    </section>
</template>
