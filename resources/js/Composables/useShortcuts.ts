import { onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * GitHub-style admin keyboard shortcuts.
 * g h → public home, g d → dashboard, g p → posts, / → global search, ? → help.
 */
export interface ShortcutOptions {
    onFocusSearch?: () => void
    onShowHelp?: () => void
}

export function useShortcuts({ onFocusSearch, onShowHelp }: ShortcutOptions = {}): void {
    let pending: string | null = null
    let timer: ReturnType<typeof setTimeout> | null = null

    function clearPending() {
        pending = null
        if (timer) clearTimeout(timer)
        timer = null
    }

    function isTypingTarget(target: EventTarget | null): boolean {
        if (!target || !(target instanceof HTMLElement)) return false
        const tag = target.tagName
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable
    }

    function visit(name: string) {
        if (!route().has(name)) return
        router.visit(route(name))
    }

    function onKeydown(e: KeyboardEvent) {
        if (e.metaKey || e.ctrlKey || e.altKey) return

        if (isTypingTarget(e.target)) {
            if (e.key !== 'Escape') return
        }

        if (e.key === '/') {
            e.preventDefault()
            onFocusSearch?.()
            return
        }

        if (e.key === '?' || (e.shiftKey && e.key === '/')) {
            e.preventDefault()
            onShowHelp?.()
            return
        }

        if (pending === 'g') {
            clearPending()
            if (e.key === 'h') {
                e.preventDefault()
                visit('home')
            } else if (e.key === 'd') {
                e.preventDefault()
                visit('admin.dashboard')
            } else if (e.key === 'p') {
                e.preventDefault()
                visit('admin.posts.index')
            }
            return
        }

        if (e.key === 'g') {
            pending = 'g'
            timer = setTimeout(clearPending, 1000)
        }
    }

    onMounted(() => document.addEventListener('keydown', onKeydown))
    onUnmounted(() => {
        document.removeEventListener('keydown', onKeydown)
        clearPending()
    })
}
