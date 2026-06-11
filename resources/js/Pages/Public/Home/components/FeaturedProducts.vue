<script setup>
import { Link } from '@inertiajs/vue3'
import SectionHeading from '@/Components/Atoms/SectionHeading.vue'
import { useImageUrl } from '@/Composables/useImageUrl'

defineProps({
    products: { type: Array, default: () => [] },
})

const { toImageUrl } = useImageUrl()
</script>

<template>
    <section v-if="products.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <SectionHeading title="Featured Products" link-href="/shop" />
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <Link
                    v-for="product in products"
                    :key="product.id"
                    :href="`/shop/${product.slug}`"
                    class="group bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img
                            v-if="product.featured_image"
                            :src="toImageUrl(product.featured_image)"
                            :alt="product.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
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
</template>
