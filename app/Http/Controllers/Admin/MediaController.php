<?php

namespace App\Http\Controllers\Admin;

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
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        // Images are WebP-converted, resized, and EXIF-stripped by the service.
        $this->mediaService->upload($request->file('file'), $request->alt_text, auth()->id());

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(Media $media)
    {
        $this->mediaService->delete($media);

        return back()->with('success', 'File deleted successfully.');
    }
}
