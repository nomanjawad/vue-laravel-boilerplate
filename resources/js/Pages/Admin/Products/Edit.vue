<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    product: Object,
    categories: Array,
})

const form = useForm({
    name: props.product.name,
    slug: props.product.slug,
    description: props.product.description || '',
    price: props.product.price,
    compare_price: props.product.compare_price || '',
    sku: props.product.sku || '',
    stock_quantity: props.product.stock_quantity,
    is_active: props.product.is_active,
    featured_image: props.product.featured_image || '',
    category_id: props.product.category_id || '',
    meta_title: props.product.meta_title || '',
    meta_description: props.product.meta_description || '',
})

const submit = () => {
    form.put(`/admin/products/${props.product.id}`)
}
</script>

<template>
    <Head title="Edit Product" />
    <div class="flex items-center mb-6">
        <Link href="/admin/products" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit Product</h1>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea v-model="form.description" rows="8" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Pricing</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input v-model="form.price" type="number" step="0.01" min="0" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Compare Price <span class="text-gray-400">(original)</span></label>
                        <input v-model="form.compare_price" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Inventory</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SKU</label>
                        <input v-model="form.sku" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                        <input v-model="form.stock_quantity" type="number" min="0" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.stock_quantity" class="mt-1 text-sm text-red-600">{{ form.errors.stock_quantity }}</p>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">SEO</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                    <input v-model="form.meta_title" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                    <textarea v-model="form.meta_description" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <label class="mt-2 flex items-center">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                        <span class="ml-2 text-sm text-gray-700">Active (visible in shop)</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select v-model="form.category_id" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="">None</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Featured Image URL</label>
                    <input v-model="form.featured_image" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <div v-if="form.featured_image" class="mt-2">
                        <img :src="form.featured_image" alt="Preview" class="w-full h-32 object-cover rounded" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <Link href="/admin/products" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Update Product
                </button>
            </div>
        </div>
    </form>
</template>
