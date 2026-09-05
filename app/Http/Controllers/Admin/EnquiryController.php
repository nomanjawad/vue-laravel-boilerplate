<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Support\NavBadges\UnreadEnquiries;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['unread', 'read'], true)) {
            $status = '';
        }

        $enquiries = Enquiry::query()
            ->latest()
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('subject', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->when($status === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($status === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->paginate(25)
            ->withQueryString();

        $selected = null;
        if ($request->filled('id')) {
            $selected = Enquiry::query()->find($request->integer('id'));
            if ($selected && $selected->read_at === null) {
                $selected->update(['read_at' => now()]);
                UnreadEnquiries::forget();
                $selected->refresh();
            }
        }

        return Inertia::render('Admin/Enquiries/Index', [
            'enquiries' => $enquiries,
            'selected' => $selected,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $status !== '' ? $status : null,
                'id' => $selected?->id,
            ],
        ]);
    }

    public function markRead(Enquiry $enquiry)
    {
        if ($enquiry->read_at === null) {
            $enquiry->update(['read_at' => now()]);
            UnreadEnquiries::forget();
        }

        return back()->with('success', 'Marked as read.');
    }

    public function markUnread(Enquiry $enquiry)
    {
        if ($enquiry->read_at !== null) {
            $enquiry->update(['read_at' => null]);
            UnreadEnquiries::forget();
        }

        return back()->with('success', 'Marked as unread.');
    }

    public function bulkMarkRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:enquiries,id'],
        ]);

        Enquiry::query()
            ->whereIn('id', $validated['ids'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        UnreadEnquiries::forget();

        activity('default')
            ->causedBy(auth()->user())
            ->withProperties(['ids' => $validated['ids'], 'count' => count($validated['ids'])])
            ->log('bulk marked '.count($validated['ids']).' enquiries as read');

        return back()->with('success', 'Selected enquiries marked as read.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        UnreadEnquiries::forget();

        return redirect('/admin/enquiries')->with('success', 'Enquiry deleted.');
    }
}
