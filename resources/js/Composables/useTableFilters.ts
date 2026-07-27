import { ref, watch, type Ref } from 'vue'
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
export interface TableFiltersOptions {
    route?: string
    debounce?: number
    only?: string[]
}

export type FilterValue = string | number | null | undefined

export function useTableFilters<T extends Record<string, FilterValue>>(
    initial: T,
    { route, debounce = 250, only = [] }: TableFiltersOptions = {},
): { [K in keyof T]: Ref<T[K] | ''> } {
    const refs = {} as { [K in keyof T]: Ref<T[K] | ''> }
    for (const k in initial) {
        refs[k] = ref((initial[k] ?? '') as T[typeof k] | '') as Ref<T[typeof k] | ''>
    }

    let t: ReturnType<typeof setTimeout> | null = null
    function reload() {
        if (t) clearTimeout(t)
        t = setTimeout(() => {
            const params: Record<string, string | number> = {}
            for (const k in refs) {
                const v = refs[k].value
                if (v !== '' && v !== null && v !== undefined) {
                    params[k] = v as string | number
                }
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
