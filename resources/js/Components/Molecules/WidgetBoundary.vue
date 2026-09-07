<script setup lang="ts">
/**
 * Error boundary for one public widget. A runtime throw inside a widget
 * component (bad data shape, client-override bug) would otherwise kill the
 * whole client-side render — blank page, no server 500, so no error page
 * either. Captured here instead: the broken widget renders nothing and the
 * rest of the page survives, matching DynamicPage's "unknown types are
 * skipped" philosophy.
 */
import { onErrorCaptured, ref } from 'vue'

const props = defineProps<{ widgetType?: string }>()

const failed = ref(false)

onErrorCaptured((err) => {
    failed.value = true
    console.error(`[widget:${props.widgetType ?? 'unknown'}] render failed — widget skipped`, err)
    return false // handled: don't propagate to the app-level errorHandler
})
</script>

<template>
    <slot v-if="!failed" />
</template>
