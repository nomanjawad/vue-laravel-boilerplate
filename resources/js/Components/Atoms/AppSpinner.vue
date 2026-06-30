<script setup>
defineProps({
    size: { type: Number, default: 16 },
})
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        class="animate-spin"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-opacity="0.25" />
        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
</template>
