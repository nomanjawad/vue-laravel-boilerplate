<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({ products: Object, categories: Array, filters: Object })

const search = ref(props.filters?.search || '')
const category = ref(props.filters?.category || '')
const sort = ref(props.filters?.sort || '')

watch([search, category, sort], () => {
    router.get('/shop', {
        search: search.value,
        category: category.value,
        sort: sort.value,
    }, { preserveState: true, replace: true })
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const addToCart = (productId) => {
    router.post('/cart/add', { product_id: productId, quantity: 1 }, { preserveScroll: true })
}
</script>

<template>
    <Head title="Shop" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Shop</h1>
            <p class="mt-2 text-gray-300">Browse our collection of products</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <input v-model="search" type="text" placeholder="Search products..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none" />
                <select v-model="category" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
                </select>
                <select v-model="sort" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                </select>
            </div>

            <!-- Product Grid -->
            <div v-if="products.data.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div v-for="product in products.data" :key="product.id" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <Link :href="`/shop/${product.slug}`">
                        <div class="aspect-square bg-gray-100 overflow-hidden">
                            <img v-if="product.featured_image" :src="product.featured_image" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                    </Link>
                    <div class="p-4">
                        <Link :href="`/shop/${product.slug}`">
                            <h3 class="text-sm font-medium text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2">{{ product.name }}</h3>
                        </Link>
                        <div class="mt-2 flex items-center space-x-2">
                            <span class="text-lg font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                            <span v-if="product.compare_price" class="text-sm text-gray-400 line-through">{{ formatPrice(product.compare_price) }}</span>
                        </div>
                        <button @click="addToCart(product.id)" class="mt-3 w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <p class="text-gray-500 text-lg">No products found.</p>
                <button v-if="search || category" @click="search = ''; category = ''; sort = ''" class="mt-4 text-sm text-gray-600 hover:text-gray-900 underline">Clear filters</button>
            </div>

            <!-- Pagination -->
            <div v-if="products.links && products.links.length > 3" class="mt-12 flex justify-center">
                <nav class="flex space-x-1">
                    <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
                </nav>
            </div>
        </div>
    </section>
</template>
