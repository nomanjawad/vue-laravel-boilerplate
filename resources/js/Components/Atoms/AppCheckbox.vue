<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: undefined },
})
defineEmits(['update:modelValue'])
</script>

<template>
    <input
        :id="id"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        @change="$emit('update:modelValue', $event.target.checked)"
    >
</template>
