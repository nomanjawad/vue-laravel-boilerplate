<script setup lang="ts">
import { ref } from 'vue'

interface Props {
    accept?: string
    multiple?: boolean
    disabled?: boolean
    id?: string
    /** Button label when no default slot is provided. */
    label?: string
    /** primary = filled brand; secondary = outline. */
    variant?: 'primary' | 'secondary'
}

withDefaults(defineProps<Props>(), {
    accept: 'image/*',
    multiple: false,
    disabled: false,
    id: undefined,
    label: 'Upload',
    variant: 'primary',
})

const emit = defineEmits<{
    (e: 'select', files: File[]): void
}>()

const inputRef = ref<HTMLInputElement | null>(null)

function open() {
    inputRef.value?.click()
}

function onChange(e: Event) {
    const target = e.target as HTMLInputElement
    const files = Array.from(target.files || [])
    if (!files.length) return
    emit('select', files)
    target.value = ''
}

defineExpose({ open })
</script>

<template>
    <span class="inline-flex">
        <input
            :id="id"
            ref="inputRef"
            type="file"
            :accept="accept"
            :multiple="multiple"
            :disabled="disabled"
            class="sr-only"
            tabindex="-1"
            @change="onChange"
        >
        <button
            type="button"
            :disabled="disabled"
            class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
            :class="variant === 'primary'
                ? 'bg-brand-600 text-white hover:bg-brand-700'
                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
            @click="open"
        >
            <slot>{{ label }}</slot>
        </button>
    </span>
</template>
