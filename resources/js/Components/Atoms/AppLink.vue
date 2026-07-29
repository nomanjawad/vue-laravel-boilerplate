<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

/**
 * Inertia <Link> wrapper that defaults to `prefetch="hover"` — starts the
 * next visit's XHR the moment the user hovers, so the click feels instant.
 * Cheap perceived-perf win; safe on any GET link.
 *
 * Only declares the props we intercept (`prefetch`) and the essential
 * `href`; every other Inertia Link prop passes through via `$attrs`. To
 * disable prefetch on a specific link (e.g. a POST/DELETE action), pass
 * `:prefetch="false"`.
 *
 *   <AppLink href="/">Home</AppLink>
 *   <AppLink :href="`/admin/posts/${post.id}`" method="delete" :prefetch="false">
 *     Delete
 *   </AppLink>
 */

type PrefetchStrategy = 'hover' | 'mount' | 'click'

interface Props {
    href: string
    prefetch?: boolean | PrefetchStrategy | PrefetchStrategy[]
}

const props = withDefaults(defineProps<Props>(), {
    prefetch: 'hover',
})

defineOptions({ inheritAttrs: false })
</script>

<template>
    <Link v-bind="$attrs" :href="props.href" :prefetch="props.prefetch">
        <slot />
    </Link>
</template>
