<script setup>
import { usePermissions } from '@/Composables/usePermissions.js'

const props = defineProps({
    permission: { type: [String, Array], required: true },
})

const { can, canAny } = usePermissions()
const allowed = Array.isArray(props.permission) ? canAny(props.permission) : can(props.permission)
</script>

<template>
    <template v-if="allowed">
        <slot />
    </template>
    <template v-else>
        <slot name="fallback" />
    </template>
</template>
