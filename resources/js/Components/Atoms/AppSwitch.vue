<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
    <label class="relative inline-flex cursor-pointer items-center" :class="{ 'opacity-50 pointer-events-none': disabled }">
        <input
            type="checkbox"
            class="peer sr-only"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', $event.target.checked)"
        >
        <div class="h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-indigo-600" />
        <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5" />
    </label>
</template>
