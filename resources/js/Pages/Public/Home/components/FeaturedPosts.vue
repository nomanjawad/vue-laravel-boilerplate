<script setup>
import { Link } from '@inertiajs/vue3'
import SectionHeading from '@/Components/Atoms/SectionHeading.vue'
import { useImageUrl } from '@/Composables/useImageUrl'

defineProps({
    posts: { type: Array, default: () => [] },
})

const { toImageUrl } = useImageUrl()
</script>

<template>
    <section v-if="posts.length" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <SectionHeading title="Latest Posts" link-href="/blog" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <Link v-for="post in posts" :key="post.id" :href="`/blog/${post.slug}`" class="group">
                    <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                        <img
                            v-if="post.featured_image"
                            :src="toImageUrl(post.featured_image)"
                            :alt="post.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ post.title }}</h3>
                    <p v-if="post.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ post.excerpt }}</p>
                </Link>
            </div>
        </div>
    </section>
</template>
