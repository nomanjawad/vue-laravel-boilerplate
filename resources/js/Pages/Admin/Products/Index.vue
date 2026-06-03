<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    products: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const applyFilters = () => {
    router.get('/admin/products', { search: search.value }, { preserveState: true, replace: true })
}

watch(search, applyFilters)

const deleteProduct = (product) => {
    if (confirm(`Delete "${product.name}"?`)) {
        router.delete(`/admin/products/${product.id}`)
    }
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}
</script>

<template>
    <Head title="Products" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <Link href="/admin/products/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            New Product
        </Link>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex flex-wrap gap-3">
            <input v-model="search" type="text" placeholder="Search products..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div v-if="product.featured_image" class="flex-shrink-0 h-10 w-10 mr-3">
                                    <img :src="product.featured_image" :alt="product.name" class="h-10 w-10 rounded object-cover" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ product.name }}</div>
                                    <div v-if="product.sku" class="text-xs text-gray-500">SKU: {{ product.sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ formatPrice(product.price) }}
                            <span v-if="product.compare_price" class="text-xs text-gray-400 line-through ml-1">{{ formatPrice(product.compare_price) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'text-sm',
                                product.stock_quantity <= 0 ? 'text-red-600 font-medium' :
                                product.stock_quantity <= 5 ? 'text-yellow-600' : 'text-gray-900'
                            ]">{{ product.stock_quantity }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ product.category?.name || '-' }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                product.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">{{ product.is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/products/${product.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="products.links && products.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
            </nav>
        </div>
    </div>
</template>
