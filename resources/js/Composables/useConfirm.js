import { ref } from 'vue'

// Tiny global async-confirm. <ConfirmDialog/> mounted in the layout watches
// `state` and resolves the promise when the user clicks confirm/cancel.

const state = ref(null) // null | { title, body, confirmLabel, resolve }

export function useConfirm() {
    function confirm({ title = 'Are you sure?', body = '', confirmLabel = 'Confirm', confirmTone = 'danger' } = {}) {
        return new Promise((resolve) => {
            state.value = { title, body, confirmLabel, confirmTone, resolve }
        })
    }
    return { confirm, _state: state }
}
