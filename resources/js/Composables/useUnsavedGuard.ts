import { onBeforeUnmount, toValue, type MaybeRefOrGetter } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Warn on tab close (beforeunload) and on Inertia SPA navigation when dirty.
 * FormShell, Pages/Edit, and Posts/Create all share this so a sidebar click
 * can't silently discard a long edit (feedback.md F11 #22).
 */
export function useUnsavedGuard(isDirty: MaybeRefOrGetter<boolean>): void {
    function guardUnload(e: BeforeUnloadEvent) {
        if (!toValue(isDirty)) return
        e.preventDefault()
        e.returnValue = ''
    }

    window.addEventListener('beforeunload', guardUnload)

    const removeBefore = router.on('before', (event) => {
        if (!toValue(isDirty)) return
        if (!window.confirm('You have unsaved changes. Leave this page?')) {
            event.preventDefault()
        }
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', guardUnload)
        removeBefore()
    })
}
