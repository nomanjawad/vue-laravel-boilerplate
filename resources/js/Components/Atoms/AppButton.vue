<script setup>
/**
 * Atom: button/link with the template's two button styles.
 * Renders an Inertia <Link> when `href` is set, otherwise a <button>.
 */
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' }, // primary | outline
    light: { type: Boolean, default: false }, // invert for dark backgrounds
})

const classes = computed(() => {
    const base = 'inline-flex items-center px-6 py-3 font-medium rounded-(--radius-button) transition-colors'

    if (props.variant === 'outline') {
        return props.light
            ? `${base} border border-white text-white hover:bg-white/10`
            : `${base} border border-gray-900 text-gray-900 hover:bg-gray-900/5`
    }

    return props.light
        ? `${base} bg-white text-gray-900 hover:bg-gray-100`
        : `${base} bg-brand-600 text-white hover:bg-brand-700`
})
</script>

<template>
    <Link v-if="href" :href="href" :class="classes"><slot /></Link>
    <button v-else :class="classes"><slot /></button>
</template>
