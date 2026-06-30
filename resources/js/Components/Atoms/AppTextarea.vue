<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    rows: { type: Number, default: 6 },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: undefined },
    invalid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <textarea
        :id="id"
        :rows="rows"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
