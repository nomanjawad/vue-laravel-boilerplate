/**
 * Normalize a stored image path to a browser-usable URL.
 *
 * Mirrors Controller::imageUrl() on the backend:
 * - absolute URLs and paths starting with "/" pass through
 * - "uploads/..." (imported content, e.g. WordPress) -> "/uploads/..."
 * - everything else (admin media on the public disk) -> "/storage/..."
 *
 * Use this in admin Create/Edit previews instead of re-implementing per page.
 */
export function useImageUrl() {
    const toImageUrl = (path) => {
        if (!path) return null
        if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) return path
        if (path.startsWith('uploads/')) return `/${path}`
        return `/storage/${path}`
    }

    // Alias kept for parity with naming used in live projects.
    return { toImageUrl, toPreviewUrl: toImageUrl }
}
