<script setup lang="ts">
import AppButton from '@/Components/Atoms/AppButton.vue'

interface Props {
    data?: {
        title?: string
        description?: string
        button_text?: string
        button_url?: string | null
    } | null
}

withDefaults(defineProps<Props>(), {
    data: null,
})
</script>

<template>
    <section v-if="data" class="py-16 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">{{ data.title }}</h2>
            <p class="text-gray-300 mb-8 max-w-xl mx-auto">{{ data.description }}</p>
            <AppButton v-if="data.button_text" :href="data.button_url || '/contact'" light>
                {{ data.button_text }}
            </AppButton>
        </div>
    </section>
</template>
