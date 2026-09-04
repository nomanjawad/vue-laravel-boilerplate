<script setup lang="ts">
import AppImage, { type AppImageMedia } from '@/Components/Atoms/AppImage.vue'

interface Props {
    data?: {
        src?: string | AppImageMedia | null
        alt?: string | null
        caption?: string | null
    }
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
})
</script>

<template>
    <section v-if="data?.src" class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <AppImage
                :src="data.src"
                :alt="data.alt || (typeof data.src === 'object' ? data.src.alt_text : '') || ''"
                class="w-full h-auto rounded-lg"
            />
            <p v-if="data.caption" class="mt-3 text-sm text-gray-500">{{ data.caption }}</p>
        </div>
    </section>
</template>
