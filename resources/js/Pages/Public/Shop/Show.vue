<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({ product: Object, relatedProducts: Array })

const quantity = ref(1)

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const addToCart = () => {
    router.post('/cart/add', { product_id: props.product.id, quantity: quantity.value }, { preserveScroll: true })
}

const incrementQty = () => { quantity.value++ }
const decrementQty = () => { if (quantity.value > 1) quantity.value-- }
</script>

<template>
    <Head :title="product.meta_title || product.name" />

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8 text-sm text-gray-500">
                <Link href="/shop" class="hover:text-gray-700">Shop</Link>
                <span class="mx-2">/</span>
                <Link v-if="product.category" :href="`/shop?category=${product.category.slug}`" class="hover:text-gray-700">{{ product.category.name }}</Link>
                <span v-if="product.category" class="mx-2">/</span>
                <span class="text-gray-900">{{ product.name }}</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Product Image -->
                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                    <img v-if="product.featured_image" :src="product.featured_image" :alt="product.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                </div>

                <!-- Product Info -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ product.name }}</h1>

                    <div class="mt-4 flex items-center space-x-3">
                        <span class="text-3xl font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                        <span v-if="product.compare_price" class="text-xl text-gray-400 line-through">{{ formatPrice(product.compare_price) }}</span>
                        <span v-if="product.compare_price" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                            Save {{ Math.round((1 - product.price / product.compare_price) * 100) }}%
                        </span>
                    </div>

                    <div v-if="product.sku" class="mt-2 text-sm text-gray-500">SKU: {{ product.sku }}</div>

                    <div class="mt-4">
                        <span v-if="product.stock_quantity > 0" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">In Stock</span>
                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Out of Stock</span>
                    </div>

                    <div v-if="product.description" class="mt-6 prose prose-gray text-gray-600" v-html="product.description" />

                    <!-- Add to Cart -->
                    <div v-if="product.stock_quantity > 0" class="mt-8 flex items-center space-x-4">
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button @click="decrementQty" class="px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50">&minus;</button>
                            <span class="px-4 py-2 text-sm font-medium text-gray-900 border-x border-gray-300">{{ quantity }}</span>
                            <button @click="incrementQty" class="px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50">&plus;</button>
                        </div>
                        <button @click="addToCart" class="flex-1 px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 transition-colors">
                            Add to Cart
                        </button>
                    </div>

                    <div v-if="product.category" class="mt-8 pt-6 border-t border-gray-200">
                        <span class="text-sm text-gray-500">Category:</span>
                        <Link :href="`/shop?category=${product.category.slug}`" class="ml-1 text-sm text-gray-700 hover:text-gray-900">{{ product.category.name }}</Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <section v-if="relatedProducts?.length" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <Link v-for="rp in relatedProducts" :key="rp.id" :href="`/shop/${rp.slug}`" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img v-if="rp.featured_image" :src="rp.featured_image" :alt="rp.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-sm font-medium text-gray-900 group-hover:text-gray-600 line-clamp-2">{{ rp.name }}</h3>
                        <div class="mt-2 flex items-center space-x-2">
                            <span class="font-bold text-gray-900">{{ formatPrice(rp.price) }}</span>
                            <span v-if="rp.compare_price" class="text-sm text-gray-400 line-through">{{ formatPrice(rp.compare_price) }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>
