<script setup lang="ts">
interface Props {
    title?: string
    description?: string
}

withDefaults(defineProps<Props>(), {
    title: 'Nothing here yet',
    description: '',
})
</script>

<template>
    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-10 text-center">
        <h3 class="text-sm font-semibold text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
