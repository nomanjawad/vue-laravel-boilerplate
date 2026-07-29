<?php

namespace App\Http\Controllers\Admin;

use App\Data\MediaData;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function index(Request $request)
    {
        return Inertia::render('Admin/Media/Index', [
            'media' => Media::with('user:id,name')
                ->latest()
                ->paginate(24)
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'filename' => $item->filename,
                    'url' => $item->url,
                    'mime_type' => $item->mime_type,
                    'size' => $item->size,
                    'alt_text' => $item->alt_text,
                    'user' => $item->user?->name,
                    'created_at' => $item->created_at->format('M d, Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        // Whitelist MIME types explicitly — `file` alone accepts arbitrary
        // content, including .php/.phtml/.htaccess, which would be RCE if
        // ever served by the webserver from public/storage. SVG is
        // deliberately excluded (script tags render inline).
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf',
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        // Images are WebP-converted, resized, and EXIF-stripped by the service.
        $media = $this->mediaService->upload($request->file('file'), $request->alt_text, auth()->id());

        // Flash the created row back so AppMediaPicker's onSuccess handler
        // can pick it up and update its v-model without an extra fetch.
        // HandleInertiaRequests forwards session('media') into the shared
        // `flash` prop; AppMediaPicker.vue reads flash.media from there.
        return back()
            ->with('success', 'File uploaded successfully.')
            ->with('media', MediaData::fromModel($media));
    }

    public function destroy(Media $media)
    {
        $this->mediaService->delete($media);

        return back()->with('success', 'File deleted successfully.');
    }
}
