/**
 * Normalize a stored image path to a browser-usable URL.
 *
 * Mirrors Controller::imageUrl() on the backend:
 * - absolute URLs and paths starting with "/" pass through
 * - "uploads/..." (imported content, e.g. WordPress) -> "/uploads/..."
 * - everything else (admin media on the public disk) -> "/storage/..."
 *
 * Use this in admin Create/Edit previews instead of re-implementing per page.
 *
 * Variant entries may be a legacy path string or `{path, width?, height?}`
 * (Phase 10). Use `variantPath()` before `toImageUrl()`.
 */
export type ImagePath = string | null | undefined

/** Legacy string path or Phase-10 `{path, width, height}` variant entry. */
export type VariantEntry =
    | string
    | { path?: string; width?: number; height?: number }
    | null
    | undefined

export function variantPath(entry: VariantEntry): string | null {
    if (!entry) return null
    if (typeof entry === 'string') return entry || null
    if (typeof entry === 'object' && typeof entry.path === 'string' && entry.path) {
        return entry.path
    }
    return null
}

export function variantWidth(entry: VariantEntry, fallback: number): number {
    if (entry && typeof entry === 'object' && typeof entry.width === 'number' && entry.width > 0) {
        return entry.width
    }
    return fallback
}

export function useImageUrl() {
    const toImageUrl = (path: ImagePath): string | null => {
        if (!path) return null
        if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) return path
        if (path.startsWith('uploads/')) return `/${path}`
        return `/storage/${path}`
    }

    // Alias kept for parity with naming used in live projects.
    return { toImageUrl, toPreviewUrl: toImageUrl, variantPath }
}
