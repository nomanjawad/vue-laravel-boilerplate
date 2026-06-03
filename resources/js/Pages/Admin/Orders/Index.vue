<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    orders: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get('/admin/orders', { search: search.value, status: status.value }, { preserveState: true, replace: true })
}

watch([search, status], applyFilters)

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
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
</script>

<template>
    <Head title="Orders" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex flex-wrap gap-3">
            <input v-model="search" type="text" placeholder="Search order # or customer..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            <select v-model="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="refunded">Refunded</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ order.order_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div>{{ order.customer_name }}</div>
                            <div class="text-xs text-gray-400">{{ order.customer_email }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatPrice(order.total) }}</td>
                        <td class="px-6 py-4">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', statusColor(order.status)]">{{ order.status }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', paymentStatusColor(order.payment_status)]">{{ order.payment_status }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/orders/${order.order_number}`" class="text-gray-600 hover:text-gray-900">View</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="orders.links && orders.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
            </nav>
        </div>
    </div>
</template>
