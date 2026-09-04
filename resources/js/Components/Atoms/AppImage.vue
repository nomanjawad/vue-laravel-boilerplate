<script setup lang="ts">
/**
 * Single public-image discipline point. Emits srcset/sizes from media
 * variants (thumb 400 / md 1200 / original ≤2000), width/height for CLS,
 * lazy-by-default with an `eager` prop (fetchpriority=high) for LCP.
 *
 * `src` accepts a URL string or a MediaData-like object
 * `{ url, variants?, width?, height?, alt_text? }` (what page widgets store
 * after Phase 10). Prefer this over raw `<img>` on public pages/widgets.
 *
 * See agents/skills/launch-readiness/SKILL.md.
 */
import { computed, useAttrs } from 'vue'
import { useImageUrl, variantPath, variantWidth, type VariantEntry } from '@/Composables/useImageUrl'

export interface AppImageMedia {
    url?: string | null
    variants?: Record<string, VariantEntry> | null
    width?: number | null
    height?: number | null
    alt_text?: string | null
}

interface Props {
    /** URL string or media object (url + variants + dims). */
    src?: string | AppImageMedia | null
    alt?: string
    /** Default sizes hint; override per layout (gallery tiles, etc.). */
    sizes?: string
    /** Above-the-fold / LCP image: loading=eager + fetchpriority=high. */
    eager?: boolean
    /** Explicit width/height override (else from media dims). */
    width?: number | null
    height?: number | null
}

defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<Props>(), {
    src: null,
    alt: '',
    sizes: '(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px',
    eager: false,
    width: null,
    height: null,
})

const attrs = useAttrs()
const { toImageUrl } = useImageUrl()

const media = computed<AppImageMedia | null>(() => {
    const s = props.src
    if (!s) return null
    if (typeof s === 'string') return { url: s }
    return s
})

const resolvedSrc = computed(() => {
    const url = media.value?.url
    return url ? toImageUrl(url) : null
})

const srcset = computed(() => {
    const m = media.value
    if (!m) return undefined

    const parts: string[] = []
    const variants = m.variants ?? {}

    const thumb = variantPath(variants.thumb)
    if (thumb) {
        const u = toImageUrl(thumb)
        if (u) parts.push(`${u} ${variantWidth(variants.thumb, 400)}w`)
    }

    const md = variantPath(variants.md)
    if (md) {
        const u = toImageUrl(md)
        if (u) parts.push(`${u} ${variantWidth(variants.md, 1200)}w`)
    }

    const original = m.url ? toImageUrl(m.url) : null
    if (original) {
        const w = m.width && m.width > 0 ? m.width : 2000
        parts.push(`${original} ${w}w`)
    }

    return parts.length > 1 ? parts.join(', ') : undefined
})

const imgWidth = computed(() => props.width ?? media.value?.width ?? undefined)
const imgHeight = computed(() => props.height ?? media.value?.height ?? undefined)

const resolvedAlt = computed(() => {
    if (props.alt) return props.alt
    return media.value?.alt_text || ''
})
</script>

<template>
    <img
        v-if="resolvedSrc"
        :src="resolvedSrc"
        :srcset="srcset"
        :sizes="srcset ? sizes : undefined"
        :alt="resolvedAlt"
        :width="imgWidth"
        :height="imgHeight"
        :loading="eager ? 'eager' : 'lazy'"
        :fetchpriority="eager ? 'high' : undefined"
        decoding="async"
        v-bind="attrs"
    >
</template>
