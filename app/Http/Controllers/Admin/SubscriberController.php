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
        return Inertia::render('Admin/Subscribers/Index', [
            'subscribers' => Subscriber::latest()
                ->when($request->search, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
                ->paginate(25)
                ->withQueryString(),
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
