<script setup lang="ts">
/**
 * Google-style SERP snippet preview + character counters for meta title /
 * description. Green ≤60/160, amber near the limit, red when over.
 */
import { computed } from 'vue'

interface Props {
    title: string
    description: string
    /** Shown as the green URL line; defaults to a placeholder path. */
    url?: string
    siteName?: string
}

const props = withDefaults(defineProps<Props>(), {
    url: 'https://example.com/page',
    siteName: '',
})

const TITLE_SOFT = 60
const DESC_SOFT = 160

const displayTitle = computed(() => {
    const t = props.title.trim()
    if (t) return t
    return props.siteName || 'Page title'
})

const displayDesc = computed(() => {
    const d = props.description.trim()
    return d || 'Meta description preview appears here once you add one.'
})

const titleLen = computed(() => props.title.trim().length)
const descLen = computed(() => props.description.trim().length)

function tone(len: number, soft: number): string {
    if (len === 0) return 'text-gray-400'
    if (len <= soft) return 'text-emerald-600'
    if (len <= soft + 10) return 'text-amber-600'
    return 'text-rose-600'
}

const titleTone = computed(() => tone(titleLen.value, TITLE_SOFT))
const descTone = computed(() => tone(descLen.value, DESC_SOFT))
</script>

<template>
    <div class="space-y-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="truncate text-sm text-emerald-800">{{ url }}</p>
            <p class="mt-1 line-clamp-2 text-lg leading-snug text-[#1a0dab]">{{ displayTitle }}</p>
            <p class="mt-1 line-clamp-2 text-sm leading-snug text-gray-600">{{ displayDesc }}</p>
        </div>
        <div class="flex flex-wrap gap-4 text-xs">
            <span :class="titleTone">Title {{ titleLen }}/{{ TITLE_SOFT }}</span>
            <span :class="descTone">Description {{ descLen }}/{{ DESC_SOFT }}</span>
        </div>
    </div>
</template>
