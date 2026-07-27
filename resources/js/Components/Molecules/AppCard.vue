<script setup lang="ts">
interface Props {
    title?: string
    padded?: boolean
}

withDefaults(defineProps<Props>(), {
    title: '',
    padded: true,
})
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <header v-if="title || $slots.header" class="border-b border-gray-100 px-4 py-3">
            <slot name="header">
                <h3 class="text-sm font-semibold text-gray-900">{{ title }}</h3>
            </slot>
        </header>
        <div :class="padded ? 'p-4' : ''">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="border-t border-gray-100 bg-gray-50 px-4 py-3">
            <slot name="footer" />
        </footer>
    </div>
</template>
