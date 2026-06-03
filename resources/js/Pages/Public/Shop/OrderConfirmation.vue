<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    order: Object,
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatAddress = (address) => {
    if (!address) return ''
    if (typeof address === 'string') return address
    const parts = [address.line1, address.line2, address.city, address.state, address.zip, address.country].filter(Boolean)
    return parts.join(', ')
}
</script>

<template>
    <Head title="Order Confirmation" />

    <section class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Success Header -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Order Confirmed</h1>
                <p class="mt-2 text-gray-600">Thank you for your order! We've received your payment and your order is being processed.</p>
            </div>

            <!-- Order Info Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Order Header -->
                <div class="px-6 py-4 bg-gray-50 border-b flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Order Number</div>
                        <div class="text-lg font-bold text-gray-900">{{ order.order_number }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Order Date</div>
                        <div class="text-sm font-medium text-gray-900">{{ formatDate(order.created_at) }}</div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="px-6 py-4">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-3">Items Ordered</h3>
                    <div class="space-y-3">
                        <div v-for="item in order.items" :key="item.id" class="flex justify-between items-center text-sm">
                            <div>
                                <span class="font-medium text-gray-900">{{ item.product_name }}</span>
                                <span class="text-gray-400 ml-2">x{{ item.quantity }}</span>
                            </div>
                            <span class="font-medium text-gray-900">{{ formatPrice(item.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="px-6 py-4 bg-gray-50 border-t space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-900">{{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.tax > 0" class="flex justify-between text-sm">
                        <span class="text-gray-500">Tax</span>
                        <span class="text-gray-900">{{ formatPrice(order.tax) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-200">
                        <span class="text-gray-900">Total</span>
                        <span class="text-gray-900">{{ formatPrice(order.total) }}</span>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="px-6 py-4 border-t">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-2">Shipping Address</h3>
                    <p class="text-sm text-gray-600">{{ order.customer_name }}</p>
                    <p class="text-sm text-gray-600">{{ formatAddress(order.shipping_address) }}</p>
                </div>

                <!-- Status -->
                <div class="px-6 py-4 border-t">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-500">Status:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ order.status }}</span>
                    </div>
                    <div class="flex items-center space-x-3 mt-1">
                        <span class="text-sm text-gray-500">Payment:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ order.payment_status }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 text-center space-x-4">
                <Link href="/shop" class="inline-block px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 transition-colors">
                    Continue Shopping
                </Link>
            </div>
        </div>
    </section>
</template>
