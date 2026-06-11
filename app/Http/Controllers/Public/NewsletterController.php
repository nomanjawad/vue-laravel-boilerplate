<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
        ]);

        Subscriber::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            ['name' => $validated['name'] ?? null, 'ip' => $request->ip(), 'unsubscribed_at' => null],
        );

        return back()->with('success', 'Thanks for subscribing!');
    }
}
