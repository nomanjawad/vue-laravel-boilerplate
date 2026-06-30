import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Permission helpers fed by the `auth.permissions` shared prop from
 * HandleInertiaRequests. Super-admins always pass.
 *
 *   const { can } = usePermissions()
 *   if (can('posts.delete')) ...
 *   <Can permission="posts.delete">...</Can>  (see Components/Shared/Can.vue)
 */
export function usePermissions() {
    const page = usePage()

    const permissions = computed(() => page.props.auth?.user?.permissions ?? [])
    const isSuperAdmin = computed(() => !!page.props.auth?.user?.is_super_admin)

    function can(permission) {
        if (!permission) return true
        if (isSuperAdmin.value) return true
        return permissions.value.includes(permission)
    }

    function canAny(list = []) {
        return list.some(can)
    }

    return { permissions, isSuperAdmin, can, canAny }
}
