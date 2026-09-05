/**
 * Strip Word / Google Docs paste cruft so TipTap keeps real structure
 * (headings, lists, tables, links) without mso noise.
 */
export function cleanPastedHtml(html: string): string {
    let out = html

    // Drop Word conditional comments and Office namespaces.
    out = out.replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '')
    out = out.replace(/<\/?(?:o|w|m):[^>]*>/gi, '')
    out = out.replace(/<o:p[^>]*>[\s\S]*?<\/o:p>/gi, '')

    // Strip scripts/styles — never execute pasted JS (XSS hygiene).
    out = out.replace(/<script[\s\S]*?<\/script>/gi, '')
    out = out.replace(/<style[\s\S]*?<\/style>/gi, '')

    // Google Docs wraps "normal" text in <b style="font-weight:normal"> —
    // rewritten to spans below via DOM.

    // Remove empty spans and mso class/style noise via DOM.
    const doc = new DOMParser().parseFromString(`<div id="__root">${out}</div>`, 'text/html')
    const root = doc.getElementById('__root')
    if (!root) return out

    root.querySelectorAll('script, style, meta, link, xml, title').forEach((el) => el.remove())

    root.querySelectorAll('*').forEach((el) => {
        // Drop mso-* classes and class attrs that are only Office noise.
        if (el.hasAttribute('class')) {
            const cleaned = (el.getAttribute('class') || '')
                .split(/\s+/)
                .filter((c) => c && !/^mso/i.test(c) && !/^Apple-/i.test(c))
                .join(' ')
            if (cleaned) el.setAttribute('class', cleaned)
            else el.removeAttribute('class')
        }

        if (el.hasAttribute('style')) {
            const style = (el.getAttribute('style') || '')
                .split(';')
                .map((s) => s.trim())
                .filter((s) => {
                    if (!s) return false
                    const prop = s.split(':')[0]?.trim().toLowerCase() ?? ''
                    if (prop.startsWith('mso-')) return false
                    // Keep alignment + basic text decoration that TipTap understands.
                    return ['text-align', 'font-weight', 'font-style', 'text-decoration'].includes(prop)
                })
                .join('; ')
            if (style) el.setAttribute('style', style)
            else el.removeAttribute('style')
        }

        // Strip event handlers / javascript: URLs.
        ;[...el.attributes].forEach((attr) => {
            const name = attr.name.toLowerCase()
            const val = attr.value.trim()
            if (name.startsWith('on')) el.removeAttribute(attr.name)
            if ((name === 'href' || name === 'src') && /^javascript:/i.test(val)) {
                el.removeAttribute(attr.name)
            }
        })
    })

    // Unwrap <b>/<span> that only carried font-weight:normal (GDocs).
    root.querySelectorAll('b, span').forEach((el) => {
        const style = (el.getAttribute('style') || '').toLowerCase()
        const isFakeBold = el.tagName === 'B' && /font-weight\s*:\s*normal/.test(style)
        const isEmptySpan = el.tagName === 'SPAN' && !el.getAttribute('style') && !el.getAttribute('class')
        if (isFakeBold || isEmptySpan) {
            const parent = el.parentNode
            if (!parent) return
            while (el.firstChild) parent.insertBefore(el.firstChild, el)
            parent.removeChild(el)
        }
    })

    return root.innerHTML
}
