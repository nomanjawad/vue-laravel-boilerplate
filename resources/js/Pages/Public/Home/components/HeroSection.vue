<script setup>
import AppButton from '@/Components/Atoms/AppButton.vue'

defineProps({
    hero: { type: Object, default: () => ({}) },
})
</script>

<template>
    <section class="bg-gray-900 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ hero?.title || 'Welcome' }}</h1>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">{{ hero?.subtitle }}</p>
            <div class="flex justify-center space-x-4">
                <AppButton v-if="hero?.cta_text" :href="hero.cta_url || '/contact'" light>
                    {{ hero.cta_text }}
                </AppButton>
                <AppButton v-if="hero?.secondary_cta_text" :href="hero.secondary_cta_url || '/about'" variant="outline" light>
                    {{ hero.secondary_cta_text }}
                </AppButton>
            </div>
        </div>
    </section>
</template>
