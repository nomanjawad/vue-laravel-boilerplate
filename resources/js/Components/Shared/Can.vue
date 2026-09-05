<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/Composables/usePermissions'

interface Props {
    permission?: string
    any?: string[]
}

const props = defineProps<Props>()

const { can, canAny } = usePermissions()
const allowed = computed(() => (props.any ? canAny(props.any) : can(props.permission)))
</script>

<template>
    <template v-if="allowed">
        <slot />
    </template>
    <template v-else>
        <slot name="fallback" />
    </template>
</template>
