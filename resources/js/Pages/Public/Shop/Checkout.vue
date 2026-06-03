<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    items: Array,
    subtotal: Number,
})

const form = useForm({
    customer_name: '',
    customer_email: '',
    shipping_address: {
        line1: '',
        line2: '',
        city: '',
        state: '',
        zip: '',
        country: '',
    },
    notes: '',
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const submit = () => {
    form.post('/checkout')
}
</script>

<template>
    <Head title="Checkout" />

    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Checkout</h1>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input v-model="form.customer_name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-600">{{ form.errors.customer_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input v-model="form.customer_email" type="email" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.customer_email" class="mt-1 text-sm text-red-600">{{ form.errors.customer_email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Shipping Address</h2>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                            <input v-model="form.shipping_address.line1" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                            <p v-if="form.errors['shipping_address.line1']" class="mt-1 text-sm text-red-600">{{ form.errors['shipping_address.line1'] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address Line 2 <span class="text-gray-400">(optional)</span></label>
                            <input v-model="form.shipping_address.line2" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input v-model="form.shipping_address.city" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors['shipping_address.city']" class="mt-1 text-sm text-red-600">{{ form.errors['shipping_address.city'] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State / Province <span class="text-gray-400">(optional)</span></label>
                                <input v-model="form.shipping_address.state" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                                <input v-model="form.shipping_address.zip" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors['shipping_address.zip']" class="mt-1 text-sm text-red-600">{{ form.errors['shipping_address.zip'] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                <input v-model="form.shipping_address.country" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors['shipping_address.country']" class="mt-1 text-sm text-red-600">{{ form.errors['shipping_address.country'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Order Notes <span class="text-gray-400 text-sm font-normal">(optional)</span></h2>
                        <textarea v-model="form.notes" rows="3" placeholder="Any special instructions..." class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div>
                    <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                        <div class="space-y-3 mb-4">
                            <div v-for="item in items" :key="item.product_id" class="flex justify-between text-sm">
                                <div>
                                    <span class="text-gray-900">{{ item.name }}</span>
                                    <span class="text-gray-400 ml-1">x{{ item.quantity }}</span>
                                </div>
                                <span class="text-gray-900">{{ formatPrice(item.price * item.quantity) }}</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="text-gray-900">{{ formatPrice(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Shipping</span>
                                <span class="text-gray-500">Free</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 mt-4 pt-4">
                            <div class="flex justify-between text-lg font-bold text-gray-900">
                                <span>Total</span>
                                <span>{{ formatPrice(subtotal) }}</span>
                            </div>
                        </div>
                        <button type="submit" :disabled="form.processing" class="mt-6 w-full px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 transition-colors disabled:opacity-50">
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Place Order</span>
                        </button>
                        <Link href="/cart" class="block w-full text-center mt-3 text-sm text-gray-600 hover:text-gray-900">
                            Back to Cart
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </section>
</template>
