<script setup>
defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    options: {
        // Accepts ['a','b'] or [{ value, label }]
        type: Array,
        default: () => [],
    },
    placeholder: { type: String, default: 'Select…' },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: undefined },
    invalid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

function normalize(opt) {
    if (typeof opt === 'object' && opt !== null) return opt
    return { value: opt, label: String(opt) }
}
</script>

<template>
    <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <option value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options.map(normalize)" :key="opt.value" :value="opt.value">
            {{ opt.label }}
        </option>
    </select>
</template>
