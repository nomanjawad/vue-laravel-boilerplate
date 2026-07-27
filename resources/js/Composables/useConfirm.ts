import { ref, type Ref } from 'vue'

// Tiny global async-confirm. <ConfirmDialog/> mounted in the layout watches
// `state` and resolves the promise when the user clicks confirm/cancel.

export type ConfirmTone = 'danger' | 'primary' | 'neutral'

export interface ConfirmOptions {
    title?: string
    body?: string
    confirmLabel?: string
    confirmTone?: ConfirmTone
}

export interface ConfirmState {
    title: string
    body: string
    confirmLabel: string
    confirmTone: ConfirmTone
    resolve: (value: boolean) => void
}

const state: Ref<ConfirmState | null> = ref(null)

export function useConfirm(): {
    confirm: (options?: ConfirmOptions) => Promise<boolean>
    _state: Ref<ConfirmState | null>
} {
    function confirm({
        title = 'Are you sure?',
        body = '',
        confirmLabel = 'Confirm',
        confirmTone = 'danger',
    }: ConfirmOptions = {}): Promise<boolean> {
        return new Promise((resolve) => {
            state.value = { title, body, confirmLabel, confirmTone, resolve }
        })
    }
    return { confirm, _state: state }
}
