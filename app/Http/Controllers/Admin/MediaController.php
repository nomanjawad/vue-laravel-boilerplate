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
        $query = Media::with('user:id,name')
            ->latest()
            ->when($request->search, function ($q, string $s) {
                $q->where(function ($inner) use ($s) {
                    $inner->where('filename', 'like', "%{$s}%")
                        ->orWhere('alt_text', 'like', "%{$s}%");
                });
            })
            ->when($request->type, function ($q, string $type) {
                match ($type) {
                    'image' => $q->where('mime_type', 'like', 'image/%'),
                    'pdf' => $q->where('mime_type', 'application/pdf'),
                    default => null,
                };
            });

        $paginator = $query->paginate(24)->withQueryString()->through(
            fn (Media $item) => $this->serialize($item)
        );

        // JSON browse mode for AppMediaPicker's library overlay.
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($paginator);
        }

        return Inertia::render('Admin/Media/Index', [
            'media' => $paginator,
            'filters' => $request->only('search', 'type'),
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

    // `media.update` was declared as a permission (config/modules.php) with no
    // route/controller/UI behind it — alt text could only ever be set at
    // upload time, with no way to fix a typo or add a description afterward.
    // See feedback.md §33.
    public function update(Request $request, Media $media)
    {
        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return back()->with('success', 'Media updated successfully.');
    }

    public function destroy(Media $media)
    {
        $this->mediaService->delete($media);

        return back()->with('success', 'File deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media,id'],
        ]);

        $count = 0;
        Media::whereIn('id', $validated['ids'])->each(function (Media $media) use (&$count) {
            $this->mediaService->delete($media);
            $count++;
        });

        return back()->with('success', "{$count} file(s) deleted.");
    }

    /** @return array<string, mixed> */
    private function serialize(Media $item): array
    {
        return [
            'id' => $item->id,
            'filename' => $item->filename,
            'url' => $item->url,
            'variants' => $item->variants,
            'mime_type' => $item->mime_type,
            'size' => $item->size,
            'width' => $item->width,
            'height' => $item->height,
            'alt_text' => $item->alt_text,
            'user' => $item->user?->name,
            'created_at' => $item->created_at?->format('M d, Y'),
        ];
    }
}
