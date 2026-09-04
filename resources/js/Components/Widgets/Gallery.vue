<script setup lang="ts">
import AppImage, { type AppImageMedia } from '@/Components/Atoms/AppImage.vue'

interface GalleryImage {
    src?: string | AppImageMedia
    alt?: string
}

interface Props {
    data?: {
        title?: string | null
        images?: GalleryImage[]
    }
}

withDefaults(defineProps<Props>(), {
    data: () => ({ images: [] }),
})
</script>

<template>
    <section v-if="data?.images?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 v-if="data?.title" class="text-2xl font-bold text-gray-900 text-center mb-12">{{ data.title }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div v-for="(img, i) in data.images" :key="i" class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                    <AppImage
                        v-if="img.src"
                        :src="img.src"
                        :alt="img.alt || ''"
                        sizes="(max-width: 768px) 50vw, 33vw"
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
