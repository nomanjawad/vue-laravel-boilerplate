<script setup>
/** Atom: small status pill (order status, post status, stock, ...). */
import { computed } from 'vue'

const props = defineProps({
    color: { type: String, default: 'gray' }, // gray | green | amber | red | brand
})

const palette = {
    gray: 'bg-gray-100 text-gray-700',
    green: 'bg-green-100 text-green-700',
    amber: 'bg-amber-100 text-amber-700',
    red: 'bg-red-100 text-red-700',
    brand: 'bg-brand-100 text-brand-700',
}

const classes = computed(() => `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${palette[props.color] || palette.gray}`)
</script>

<template>
    <span :class="classes"><slot /></span>
</template>
