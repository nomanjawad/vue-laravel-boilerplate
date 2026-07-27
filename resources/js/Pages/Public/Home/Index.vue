<script setup lang="ts">
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

interface HomeHero {
    title?: string | null
    subtitle?: string | null
    cta_text?: string | null
    cta_url?: string | null
    secondary_cta_text?: string | null
    secondary_cta_url?: string | null
}

interface HomeFeature {
    title: string
    description: string
    icon?: string | null
}

interface HomeStat {
    value: string | number
    label: string
}

interface HomeCta {
    title: string
    description: string
    button_text: string
    button_url?: string | null
}

interface HomeData {
    hero?: HomeHero | null
    features?: HomeFeature[] | null
    stats?: HomeStat[] | null
    cta_section?: HomeCta | null
}

interface HomeFeaturedPost {
    id: number
    slug: string
    title: string
    excerpt?: string | null
    featured_image?: string | null
}

interface HomeFeaturedProduct {
    id: number
    slug: string
    name: string
    price: number
    compare_price?: number | null
    featured_image?: string | null
}

interface Props {
    data?: HomeData | null
    featuredPosts?: HomeFeaturedPost[] | null
    featuredProducts?: HomeFeaturedProduct[] | null
}

defineProps<Props>()
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
