<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: bots fill hidden fields humans never see (F11 #15).
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thanks for subscribing!');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
        ]);

        $email = strtolower($validated['email']);
        $existing = Subscriber::query()->where('email', $email)->first();

        if ($existing) {
            // Never silently re-subscribe an opt-out; keep their name unless
            // they supply a new one; reset seen_at so the admin badge notices.
            $existing->forceFill([
                'name' => $validated['name'] ?? $existing->name,
                'ip' => $request->ip(),
                'seen_at' => null,
            ])->save();

            if ($existing->unsubscribed_at !== null) {
                return back()->with(
                    'success',
                    'This email previously unsubscribed. Contact us if you want to re-subscribe.',
                );
            }

            return back()->with('success', 'You are already subscribed — thanks!');
        }

        Subscriber::create([
            'email' => $email,
            'name' => $validated['name'] ?? null,
            'ip' => $request->ip(),
            'unsubscribed_at' => null,
            'seen_at' => null,
        ]);

        Cache::forget('nav.badge.unseen_subscribers');

        return back()->with('success', 'Thanks for subscribing!');
    }
}
