<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{
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

        $file = $request->file('file');
        $path = $file->store('media', 'public');

        Media::create([
            'user_id' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->alt_text,
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(Media $media)
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', 'File deleted successfully.');
    }
}
