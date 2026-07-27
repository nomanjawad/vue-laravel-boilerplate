<script setup lang="ts">
/**
 * VITE PUBLIC-ASSET RULE
 * ----------------------
 * Files in /public (logos, favicons) must be referenced through a JS variable,
 * as below. Writing the path literally in the template
 * (`<img src="/images/logo.svg">`) makes Vite treat it as a module import and
 * crashes the dev server.
 *
 * Swap the path per project; the file lives in public/images/.
 */
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { SharedPageProps } from '@/types/inertia'

interface Props {
    showText?: boolean
}

const logoSrc = '/images/logo.svg'

const props = withDefaults(defineProps<Props>(), {
    showText: true,
})

const page = usePage<SharedPageProps>()
const siteName = computed(() => page.props.settings?.site_name ?? '')
</script>

<template>
    <span class="flex items-center gap-2">
        <img :src="logoSrc" :alt="siteName" class="h-8 w-auto" @error="($event.target as HTMLImageElement).style.display = 'none'" />
        <span v-if="showText" class="text-xl font-bold">{{ siteName }}</span>
    </span>
</template>
