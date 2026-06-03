<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    order: Object,
})

const statusForm = useForm({
    status: props.order.status,
})

const updateStatus = () => {
    statusForm.patch(`/admin/orders/${props.order.order_number}/status`)
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const statusColor = (s) => {
    const map = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800',
    }
    return map[s] || 'bg-gray-100 text-gray-800'
}

const paymentStatusColor = (s) => {
    const map = {
        paid: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800',
        refunded: 'bg-gray-100 text-gray-800',
    }
    return map[s] || 'bg-gray-100 text-gray-800'
}

const formatAddress = (address) => {
    if (!address) return '-'
    if (typeof address === 'string') return address
    const parts = [address.line1, address.line2, address.city, address.state, address.zip, address.country].filter(Boolean)
    return parts.join(', ')
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />
    <div class="flex items-center mb-6">
        <Link href="/admin/orders" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Order {{ order.order_number }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in order.items" :key="item.id">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ item.product_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatPrice(item.unit_price) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ formatPrice(item.total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-500">Subtotal</td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">{{ formatPrice(order.subtotal) }}</td>
                            </tr>
                            <tr v-if="order.tax > 0">
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-500">Tax</td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">{{ formatPrice(order.tax) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-bold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ formatPrice(order.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Addresses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-3">Shipping Address</h3>
                    <p class="text-sm text-gray-600">{{ formatAddress(order.shipping_address) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-3">Billing Address</h3>
                    <p class="text-sm text-gray-600">{{ formatAddress(order.billing_address) }}</p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-900 uppercase mb-3">Notes</h3>
                <p class="text-sm text-gray-600">{{ order.notes }}</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Order Details -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Order Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', statusColor(order.status)]">{{ order.status }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Payment</span>
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', paymentStatusColor(order.payment_status)]">{{ order.payment_status }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Method</span>
                        <span class="text-gray-900">{{ order.payment_method || '-' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Date</span>
                        <span class="text-gray-900">{{ formatDate(order.created_at) }}</span>
                    </div>
                    <div v-if="order.paid_at" class="flex justify-between text-sm">
                        <span class="text-gray-500">Paid At</span>
                        <span class="text-gray-900">{{ formatDate(order.paid_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer -->
            <div class="bg-white rounded-lg shadow p-6 space-y-3">
                <h3 class="text-lg font-semibold text-gray-900">Customer</h3>
                <div class="text-sm">
                    <div class="font-medium text-gray-900">{{ order.customer_name }}</div>
                    <div class="text-gray-500">{{ order.customer_email }}</div>
                    <div v-if="order.user" class="mt-1 text-xs text-gray-400">Registered user: {{ order.user.name }}</div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Update Status</h3>
                <form @submit.prevent="updateStatus">
                    <select v-model="statusForm.status" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                    <button type="submit" :disabled="statusForm.processing" class="mt-3 w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                        Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
