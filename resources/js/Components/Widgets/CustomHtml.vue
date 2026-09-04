<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    data?: {
        html?: string | null
    }
}

const props = withDefaults(defineProps<Props>(), {
    data: () => ({}),
})

/**
 * Ensure iframes in custom HTML are lazy-loaded (Lighthouse
 * third-party / embed guidance). Adds loading="lazy" when missing.
 * Prefer including loading="lazy" in the embed snippet itself.
 */
const html = computed(() => {
    const raw = props.data?.html
    if (!raw) return ''
    return raw.replace(/<iframe\b([^>]*)>/gi, (_match, attrs: string) => {
        if (/\bloading\s*=/i.test(attrs)) {
            return `<iframe${attrs}>`
        }
        return `<iframe${attrs} loading="lazy">`
    })
})
</script>

<template>
    <section v-if="html" class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" v-html="html" />
    </section>
</template>
