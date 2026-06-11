<script setup>
import AppButton from '@/Components/Atoms/AppButton.vue'

defineProps({
    cta: { type: Object, default: null },
})
</script>

<template>
    <section v-if="cta" class="py-16 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">{{ cta.title }}</h2>
            <p class="text-gray-300 mb-8 max-w-xl mx-auto">{{ cta.description }}</p>
            <AppButton :href="cta.button_url || '/contact'" light>
                {{ cta.button_text }}
            </AppButton>
        </div>
    </section>
</template>
