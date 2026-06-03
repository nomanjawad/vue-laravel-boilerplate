<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function __construct(private JsonDataService $jsonData)
    {
    }

    public function index()
    {
        return Inertia::render('Public/Contact', [
            'data' => $this->jsonData->get('contact'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Send email to admin
        $adminEmail = Setting::get('contact_email', 'admin@example.com');

        Mail::raw(
            "Name: {$validated['name']}\nEmail: {$validated['email']}\nPhone: {$validated['phone']}\n\n{$validated['message']}",
            function ($message) use ($validated, $adminEmail) {
                $message->to($adminEmail)
                    ->subject("Contact Form: {$validated['subject']}")
                    ->replyTo($validated['email'], $validated['name']);
            }
        );

        return back()->with('success', 'Thank you for your message! We will get back to you soon.');
    }
}
