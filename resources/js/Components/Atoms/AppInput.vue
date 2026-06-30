<script setup>
// Atom: text input. No business logic. Dark-admin-aware via admin.css overrides.
defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    autocomplete: { type: String, default: 'off' },
    id: { type: String, default: undefined },
    inputmode: { type: String, default: undefined },
    invalid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
