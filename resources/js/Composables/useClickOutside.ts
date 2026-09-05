import { onBeforeUnmount, toValue, type MaybeRefOrGetter } from 'vue'

/**
 * Close when the user clicks outside `el` or presses Escape.
 * Caller owns the open state; we only fire `onClose`.
 */
export function useClickOutside(
    el: MaybeRefOrGetter<HTMLElement | null | undefined>,
    onClose: () => void,
    options: { enabled?: MaybeRefOrGetter<boolean> } = {},
): void {
    function isEnabled(): boolean {
        return options.enabled === undefined ? true : !!toValue(options.enabled)
    }

    function onPointerDown(event: MouseEvent | TouchEvent) {
        if (!isEnabled()) return
        const node = toValue(el)
        const target = event.target as Node | null
        if (!node || !target || node.contains(target)) return
        onClose()
    }

    function onKeydown(event: KeyboardEvent) {
        if (!isEnabled()) return
        if (event.key === 'Escape') {
            event.preventDefault()
            onClose()
        }
    }

    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('touchstart', onPointerDown)
    document.addEventListener('keydown', onKeydown)

    onBeforeUnmount(() => {
        document.removeEventListener('mousedown', onPointerDown)
        document.removeEventListener('touchstart', onPointerDown)
        document.removeEventListener('keydown', onKeydown)
    })
}
