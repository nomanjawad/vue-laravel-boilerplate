/** Prefer Blade meta; fall back to the XSRF-TOKEN cookie Laravel always sets. */
export function csrfHeaders(extra: Record<string, string> = {}): Record<string, string> {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (meta) {
        return { 'X-CSRF-TOKEN': meta, Accept: 'application/json', ...extra }
    }
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
    const fromCookie = match?.[1] ? decodeURIComponent(match[1]) : ''
    return {
        ...(fromCookie ? { 'X-XSRF-TOKEN': fromCookie } : {}),
        Accept: 'application/json',
        ...extra,
    }
}
