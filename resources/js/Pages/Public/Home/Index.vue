<script setup>
/**
 * Page convention: a page file only composes section components from its own
 * `components/` folder (plus shared Atoms). Markup lives in the sections —
 * that keeps pages scannable and the sections reusable.
 *
 * Page-specific CSS (rarely needed): resources/css/pages/home.css
 */
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head } from '@inertiajs/vue3'
import HeroSection from './components/HeroSection.vue'
import FeatureCards from './components/FeatureCards.vue'
import StatsBand from './components/StatsBand.vue'
import FeaturedPosts from './components/FeaturedPosts.vue'
import FeaturedProducts from './components/FeaturedProducts.vue'
import CtaSection from './components/CtaSection.vue'

defineOptions({ layout: PublicLayout })

defineProps({
    data: Object,
    featuredPosts: Array,
    featuredProducts: Array,
})
</script>

<template>
    <Head title="Home" />

    <HeroSection :hero="data?.hero" />
    <FeatureCards :features="data?.features || []" />
    <StatsBand :stats="data?.stats || []" />
    <FeaturedPosts :posts="featuredPosts || []" />
    <FeaturedProducts :products="featuredProducts || []" />
    <CtaSection :cta="data?.cta_section" />
</template>
