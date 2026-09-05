import mammoth from 'mammoth'
import { marked } from 'marked'
import { ingestEditorHtml } from '@/Utils/ingestEditorHtml'

marked.setOptions({ gfm: true, breaks: false })

/**
 * Parse a .docx or .md file into cleaned HTML with images in the media library.
 */
export async function importDocumentFile(file: File): Promise<string> {
    const name = file.name.toLowerCase()
    let html = ''

    if (name.endsWith('.docx')) {
        const buffer = await file.arrayBuffer()
        const result = await mammoth.convertToHtml(
            { arrayBuffer: buffer },
            {
                convertImage: mammoth.images.imgElement(async (image) => {
                    const base64 = await image.read('base64')
                    const contentType = image.contentType || 'image/png'
                    return { src: `data:${contentType};base64,${base64}` }
                }),
            },
        )
        html = result.value
    } else if (name.endsWith('.md') || name.endsWith('.markdown')) {
        const text = await file.text()
        html = await marked.parse(text)
    } else {
        throw new Error('Unsupported file. Use .docx or .md (from Google Docs: File → Download → Microsoft Word).')
    }

    return ingestEditorHtml(html)
}
