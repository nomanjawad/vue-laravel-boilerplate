<script setup>
import { inject } from 'vue'

const props = defineProps({
    name: { type: String, required: true },
    label: { type: String, default: '' },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
})

// Errors are read from the useForm() instance injected by <FormShell>.
// Pages without a FormShell can still use this — errors prop is fallback.
const form = inject('form', null)
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="name"
            class="mb-1 block text-sm font-medium text-gray-700"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <slot :id="name" :invalid="!!form?.errors?.[name]" />

        <p v-if="form?.errors?.[name]" class="mt-1 text-xs text-rose-600">
            {{ form.errors[name] }}
        </p>
        <p v-else-if="help" class="mt-1 text-xs text-gray-500">{{ help }}</p>
    </div>
</template>
