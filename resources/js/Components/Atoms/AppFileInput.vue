<script setup>
defineProps({
    accept: { type: String, default: 'image/*' },
    multiple: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: undefined },
})

const emit = defineEmits(['select'])

function onChange(e) {
    const files = Array.from(e.target.files || [])
    if (!files.length) return
    emit('select', files)
    e.target.value = ''
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
