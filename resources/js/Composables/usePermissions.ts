import { computed, type ComputedRef } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedPageProps } from '@/types/inertia'

/**
 * Permission helpers fed by the `auth.permissions` shared prop from
 * HandleInertiaRequests. Super-admins always pass.
 *
 *   const { can } = usePermissions()
 *   if (can('posts.delete')) ...
 *   <Can permission="posts.delete">...</Can>  (see Components/Shared/Can.vue)
 */
export function usePermissions(): {
    permissions: ComputedRef<string[]>
    isSuperAdmin: ComputedRef<boolean>
    can: (permission?: string | null) => boolean
    canAny: (list?: string[]) => boolean
} {
    const page = usePage<SharedPageProps>()

    const permissions = computed<string[]>(() => page.props.auth?.user?.permissions ?? [])
    const isSuperAdmin = computed<boolean>(() => !!page.props.auth?.user?.is_super_admin)

    function can(permission?: string | null): boolean {
        if (!permission) return true
        if (isSuperAdmin.value) return true
        return permissions.value.includes(permission)
    }

    function canAny(list: string[] = []): boolean {
        return list.some((p) => can(p))
    }

    return { permissions, isSuperAdmin, can, canAny }
}
