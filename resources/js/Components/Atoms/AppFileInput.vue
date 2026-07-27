<script setup lang="ts">
interface Props {
    accept?: string
    multiple?: boolean
    disabled?: boolean
    id?: string
}

withDefaults(defineProps<Props>(), {
    accept: 'image/*',
    multiple: false,
    disabled: false,
    id: undefined,
})

const emit = defineEmits<{
    (e: 'select', files: File[]): void
}>()

function onChange(e: Event) {
    const target = e.target as HTMLInputElement
    const files = Array.from(target.files || [])
    if (!files.length) return
    emit('select', files)
    target.value = ''
}
</script>

<template>
    <input
        :id="id"
        type="file"
        :accept="accept"
        :multiple="multiple"
        :disabled="disabled"
        class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
        @change="onChange"
    >
</template>
