<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $subscribers = Subscriber::latest()
            ->when($request->search, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->paginate(25)
            ->withQueryString();

        // Stamp unseen rows after the list is built so the badge clears on
        // next nav render (and Cache::forget so the 60s remember doesn't lag).
        $unseenIds = Subscriber::query()->whereNull('seen_at')->pluck('id');
        if ($unseenIds->isNotEmpty()) {
            Subscriber::query()->whereIn('id', $unseenIds)->update(['seen_at' => now()]);
            \Illuminate\Support\Facades\Cache::forget('nav.badge.unseen_subscribers');
        }

        return Inertia::render('Admin/Subscribers/Index', [
            'subscribers' => $subscribers,
            'filters' => $request->only('search'),
        ]);
    }

    /** Streamed CSV export — works within shared-hosting memory limits. */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'subscribed_at', 'unsubscribed_at']);

            Subscriber::orderBy('id')->chunk(500, function ($subscribers) use ($out) {
                foreach ($subscribers as $s) {
                    fputcsv($out, [$s->email, $s->name, $s->created_at, $s->unsubscribed_at]);
                }
            });

            fclose($out);
        }, 'subscribers-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
