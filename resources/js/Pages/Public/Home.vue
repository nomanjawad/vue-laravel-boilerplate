<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    data: Object,
    featuredPosts: Array,
    featuredProducts: Array,
})
</script>

<template>
    <Head title="Home" />

    <!-- Hero -->
    <section class="bg-gray-900 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ data?.hero?.title || 'Welcome' }}</h1>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">{{ data?.hero?.subtitle }}</p>
            <div class="flex justify-center space-x-4">
                <Link
                    v-if="data?.hero?.cta_text"
                    :href="data.hero.cta_url || '/contact'"
                    class="inline-flex items-center px-6 py-3 bg-white text-gray-900 font-medium rounded-md hover:bg-gray-100 transition-colors"
                >
                    {{ data.hero.cta_text }}
                </Link>
                <Link
                    v-if="data?.hero?.secondary_cta_text"
                    :href="data.hero.secondary_cta_url || '/about'"
                    class="inline-flex items-center px-6 py-3 border border-white text-white font-medium rounded-md hover:bg-white/10 transition-colors"
                >
                    {{ data.hero.secondary_cta_text }}
                </Link>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section v-if="data?.features?.length" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="(feature, i) in data.features" :key="i" class="text-center p-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
                    <p class="text-gray-600">{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section v-if="data?.stats?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="(stat, i) in data.stats" :key="i" class="text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ stat.value }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Posts -->
    <section v-if="featuredPosts?.length" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Latest Posts</h2>
                <Link href="/blog" class="text-sm font-medium text-gray-600 hover:text-gray-900">View All &rarr;</Link>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <Link v-for="post in featuredPosts" :key="post.id" :href="`/blog/${post.slug}`" class="group">
                    <div class="aspect-video bg-gray-100 rounded-lg mb-4 overflow-hidden">
                        <img v-if="post.featured_image" :src="post.featured_image" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">{{ post.title }}</h3>
                    <p v-if="post.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ post.excerpt }}</p>
                </Link>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section v-if="featuredProducts?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Featured Products</h2>
                <Link href="/shop" class="text-sm font-medium text-gray-600 hover:text-gray-900">View All &rarr;</Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <Link v-for="product in featuredProducts" :key="product.id" :href="`/shop/${product.slug}`" class="group bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img v-if="product.featured_image" :src="product.featured_image" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 text-sm">{{ product.name }}</h3>
                        <div class="mt-1">
                            <span class="font-semibold text-gray-900">${{ product.price }}</span>
                            <span v-if="product.compare_price" class="text-sm text-gray-400 line-through ml-2">${{ product.compare_price }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section v-if="data?.cta_section" class="py-16 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">{{ data.cta_section.title }}</h2>
            <p class="text-gray-300 mb-8 max-w-xl mx-auto">{{ data.cta_section.description }}</p>
            <Link
                :href="data.cta_section.button_url || '/contact'"
                class="inline-flex items-center px-6 py-3 bg-white text-gray-900 font-medium rounded-md hover:bg-gray-100 transition-colors"
            >
                {{ data.cta_section.button_text }}
            </Link>
        </div>
    </section>
</template>
