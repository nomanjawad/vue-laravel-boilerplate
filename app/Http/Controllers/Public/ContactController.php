<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use App\Models\Setting;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        return Inertia::render('Public/Contact/Index', [
            'data' => $this->jsonData->get('contact'),
        ]);
    }

    public function store(Request $request)
    {
        // Honeypot: bots fill hidden fields humans never see. If "website"
        // has a value, silently pretend success so we don't tip them off.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thank you for your message! We will get back to you soon.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $adminEmail = Setting::get('contact_email', 'admin@example.com');

        Mail::to($adminEmail)->queue(new ContactMessage(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            phone: $validated['phone'] ?? null,
            subjectLine: $validated['subject'],
            messageBody: $validated['message'],
        ));

        return back()->with('success', 'Thank you for your message! We will get back to you soon.');
    }
}
