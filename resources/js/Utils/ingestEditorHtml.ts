import { csrfHeaders } from '@/Utils/csrfHeaders'
import { cleanPastedHtml } from '@/Utils/cleanPastedHtml'

interface ImportedMedia {
    id: number
    url: string
    alt_text?: string | null
    filename?: string
}

/**
 * Hosts whose image URLs should be pulled into the media library on paste/import
 * (Google Docs / Docs CDN / data URLs). Already-local /storage paths are left alone.
 */
function shouldImportSrc(src: string): boolean {
    if (!src) return false
    if (src.startsWith('data:image/')) return true
    if (src.startsWith('blob:')) return true
    try {
        const u = new URL(src, window.location.origin)
        if (u.origin === window.location.origin) {
            // Already on our app — keep as-is (library or public paths).
            return false
        }
        // Any remote http(s) image from paste/import → media library (SSRF-guarded server-side).
        return u.protocol === 'http:' || u.protocol === 'https:'
    } catch {
        return false
    }
}

async function importImageSrc(src: string, alt: string): Promise<string | null> {
    const body: Record<string, string> = {}
    if (src.startsWith('data:image/')) {
        body.data_url = src
    } else if (src.startsWith('blob:')) {
        const blob = await fetch(src).then((r) => r.blob())
        const dataUrl = await blobToDataUrl(blob)
        if (!dataUrl.startsWith('data:image/')) return null
        body.data_url = dataUrl
    } else {
        body.url = src
    }
    if (alt) body.alt_text = alt.slice(0, 255)

    const res = await fetch('/admin/media/import', {
        method: 'POST',
        credentials: 'same-origin',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify(body),
    })
    if (!res.ok) return null
    const media = (await res.json()) as ImportedMedia
    return media.url || null
}

function blobToDataUrl(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = () => resolve(String(reader.result ?? ''))
        reader.onerror = () => reject(reader.error)
        reader.readAsDataURL(blob)
    })
}

/**
 * Clean Word/GDocs HTML and route external/base64 images into /admin/media.
 */
export async function ingestEditorHtml(html: string): Promise<string> {
    const cleaned = cleanPastedHtml(html)
    const doc = new DOMParser().parseFromString(`<div id="__root">${cleaned}</div>`, 'text/html')
    const root = doc.getElementById('__root')
    if (!root) return cleaned

    const images = [...root.querySelectorAll('img')]
    await Promise.all(images.map(async (img) => {
        const src = img.getAttribute('src') || ''
        if (!shouldImportSrc(src)) return
        const alt = img.getAttribute('alt') || ''
        try {
            const url = await importImageSrc(src, alt)
            if (url) {
                img.setAttribute('src', url)
                img.removeAttribute('srcset')
            } else {
                img.remove()
            }
        } catch {
            img.remove()
        }
    }))

    return root.innerHTML
}
