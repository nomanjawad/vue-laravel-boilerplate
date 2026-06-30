import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Two-way sync for a table page's URL query state. Pass the initial filters
 * (from page props), get back refs whose changes debounce-reload the page
 * with `preserveState`.
 *
 *   const { search, sort, direction, page } = useTableFilters({
 *     search: props.filters.search,
 *     sort: props.filters.sort,
 *   }, { route: '/admin/posts' })
 */
export function useTableFilters(initial = {}, { route, debounce = 250, only = [] } = {}) {
    const refs = {}
    for (const k in initial) {
        refs[k] = ref(initial[k] ?? '')
    }

    let t = null
    function reload() {
        clearTimeout(t)
        t = setTimeout(() => {
            const params = {}
            for (const k in refs) {
                const v = refs[k].value
                if (v !== '' && v !== null && v !== undefined) params[k] = v
            }
            router.get(route ?? window.location.pathname, params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
            })
        }, debounce)
    }

    for (const k in refs) {
        watch(refs[k], reload)
    }

    return refs
}
