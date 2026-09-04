<script setup lang="ts">
import AppButton from '@/Components/Atoms/AppButton.vue'
import AppImage, { type AppImageMedia } from '@/Components/Atoms/AppImage.vue'

interface Props {
    data?: {
        title?: string | null
        subtitle?: string | null
        image?: string | AppImageMedia | null
        cta_text?: string | null
        cta_url?: string | null
        secondary_cta_text?: string | null
        secondary_cta_url?: string | null
    }
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
})
</script>

<template>
    <section class="relative bg-gray-900 text-white py-20 overflow-hidden">
        <AppImage
            v-if="data?.image"
            :src="data.image"
            alt=""
            eager
            sizes="100vw"
            class="absolute inset-0 h-full w-full object-cover"
        />
        <div v-if="data?.image" class="absolute inset-0 bg-gray-900/60" aria-hidden="true" />
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ data?.title || 'Welcome' }}</h1>
            <p v-if="data?.subtitle" class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">{{ data.subtitle }}</p>
            <div v-if="data?.cta_text || data?.secondary_cta_text" class="flex justify-center space-x-4">
                <AppButton v-if="data?.cta_text" :href="data.cta_url || '/contact'" light>
                    {{ data.cta_text }}
                </AppButton>
                <AppButton v-if="data?.secondary_cta_text" :href="data.secondary_cta_url || '/about'" variant="outline" light>
                    {{ data.secondary_cta_text }}
                </AppButton>
            </div>
        </div>
    </section>
</template>
