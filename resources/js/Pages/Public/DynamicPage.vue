<script setup lang="ts">
/**
 * Renders a JSON-backed page: iterates widgets and maps type →
 * Components/Widgets/{PascalType}.vue. Unknown types are skipped so
 * per-client frontends can override/extend the widget set safely.
 *
 * Document title / canonical / OG come from shared `seo` (PublicLayout).
 * Hero is the sole H1; every other shipped widget starts at H2.
 * LCP preload for the first visible image widget comes via Inertia <Head>.
 */
import { computed, type Component } from 'vue'
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PublicBreadcrumbs from '@/Components/Molecules/PublicBreadcrumbs.vue'
import Hero from '@/Components/Widgets/Hero.vue'
import RichText from '@/Components/Widgets/RichText.vue'
import FeatureGrid from '@/Components/Widgets/FeatureGrid.vue'
import Stats from '@/Components/Widgets/Stats.vue'
import Cta from '@/Components/Widgets/Cta.vue'
import ImageWidget from '@/Components/Widgets/Image.vue'
import Gallery from '@/Components/Widgets/Gallery.vue'
import Testimonials from '@/Components/Widgets/Testimonials.vue'
import Faqs from '@/Components/Widgets/Faqs.vue'
import Team from '@/Components/Widgets/Team.vue'
import LatestPosts from '@/Components/Widgets/LatestPosts.vue'
import ContactForm from '@/Components/Widgets/ContactForm.vue'
import CustomHtml from '@/Components/Widgets/CustomHtml.vue'

defineOptions({ layout: PublicLayout })

interface Widget {
    id: string
    type: string
    visible?: boolean
    data?: Record<string, unknown>
}

interface BreadcrumbItem {
    name: string
    url: string
}

interface Props {
    page: {
        title: string
        slug: string
        widgets: Widget[]
    }
    collectionData?: Record<string, unknown[]>
    breadcrumbs?: BreadcrumbItem[]
    /** Prefetch for the first visible hero/image (LCP) — href + optional imagesrcset. */
    lcpPreload?: string | { href: string; imagesrcset?: string; imagesizes?: string } | null
}

const props = withDefaults(defineProps<Props>(), {
    collectionData: () => ({}),
    breadcrumbs: () => [],
    lcpPreload: null,
})

const lcpHref = computed(() => {
    const p = props.lcpPreload
    if (!p) return null
    return typeof p === 'string' ? p : p.href
})
const lcpSrcset = computed(() => {
    const p = props.lcpPreload
    return p && typeof p === 'object' ? p.imagesrcset : undefined
})
const lcpSizes = computed(() => {
    const p = props.lcpPreload
    return p && typeof p === 'object' ? p.imagesizes : undefined
})

const widgetMap: Record<string, Component> = {
    hero: Hero,
    rich_text: RichText,
    feature_grid: FeatureGrid,
    stats: Stats,
    cta: Cta,
    image: ImageWidget,
    gallery: Gallery,
    testimonials: Testimonials,
    faqs: Faqs,
    team: Team,
    latest_posts: LatestPosts,
    contact_form: ContactForm,
    custom_html: CustomHtml,
}

const visibleWidgets = computed(() =>
    (props.page.widgets || []).filter((w) => w.visible !== false && widgetMap[w.type]),
)
</script>

<template>
    <Head>
        <link
            v-if="lcpHref"
            head-key="lcp-preload"
            rel="preload"
            as="image"
            :href="lcpHref"
            :imagesrcset="lcpSrcset"
            :imagesizes="lcpSrcset ? lcpSizes : undefined"
        />
    </Head>

    <PublicBreadcrumbs :items="breadcrumbs" />

    <component
        :is="widgetMap[widget.type]"
        v-for="widget in visibleWidgets"
        :key="widget.id"
        :data="widget.data || {}"
        :items="collectionData[widget.id] || []"
        :page-slug="page.slug"
    />
</template>
