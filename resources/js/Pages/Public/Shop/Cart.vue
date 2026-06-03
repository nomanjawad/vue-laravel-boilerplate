<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    items: Array,
    subtotal: Number,
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const updateQuantity = (productId, quantity) => {
    router.patch('/cart/update', { product_id: productId, quantity }, { preserveScroll: true })
}

const removeItem = (productId) => {
    router.delete(`/cart/remove/${productId}`, { preserveScroll: true })
}

const increment = (item) => {
    updateQuantity(item.product_id, item.quantity + 1)
}

const decrement = (item) => {
    if (item.quantity > 1) {
        updateQuantity(item.product_id, item.quantity - 1)
    }
}
</script>

<template>
    <Head title="Shopping Cart" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Shopping Cart</h1>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="items.length">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in items" :key="item.product_id">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div v-if="item.featured_image" class="flex-shrink-0 h-12 w-12 mr-4">
                                            <img :src="item.featured_image" :alt="item.name" class="h-12 w-12 rounded object-cover" />
                                        </div>
                                        <div>
                                            <Link :href="`/shop/${item.slug}`" class="text-sm font-medium text-gray-900 hover:text-gray-600">{{ item.name }}</Link>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ formatPrice(item.price) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <button @click="decrement(item)" class="px-2 py-1 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">&minus;</button>
                                        <span class="mx-3 text-sm font-medium text-gray-900 w-8 text-center">{{ item.quantity }}</span>
                                        <button @click="increment(item)" class="px-2 py-1 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">&plus;</button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ formatPrice(item.price * item.quantity) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="removeItem(item.product_id)" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cart Summary -->
                <div class="mt-8 flex flex-col items-end">
                    <div class="bg-white rounded-lg shadow p-6 w-full sm:w-80">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-medium text-gray-900">{{ formatPrice(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-4 pb-4 border-b border-gray-200">
                            <span class="text-gray-500">Shipping</span>
                            <span class="text-gray-500">Calculated at checkout</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-gray-900 mb-6">
                            <span>Total</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>
                        <Link href="/checkout" class="block w-full text-center px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 transition-colors">
                            Proceed to Checkout
                        </Link>
                        <Link href="/shop" class="block w-full text-center mt-3 text-sm text-gray-600 hover:text-gray-900">
                            Continue Shopping
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty Cart -->
            <div v-else class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                <h2 class="mt-4 text-xl font-semibold text-gray-900">Your cart is empty</h2>
                <p class="mt-2 text-gray-500">Looks like you haven't added anything to your cart yet.</p>
                <Link href="/shop" class="inline-block mt-6 px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 transition-colors">
                    Browse Products
                </Link>
            </div>
        </div>
    </section>
</template>
