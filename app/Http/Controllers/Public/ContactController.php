<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use App\Models\Enquiry;
use App\Models\Setting;
use App\Support\NavBadges\UnreadEnquiries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Contact form POST only — the contact page itself is a DynamicPage
 * (data/pages/contact.json) with a contact_form widget.
 */
class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: bots fill hidden fields humans never see. If "website"
        // has a value, silently pretend success so we don't tip them off.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thank you for your message! We will get back to you soon.');
        }

        $country = strtoupper((string) Setting::get('contact_default_country', 'BD')) ?: 'BD';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'phone:INTERNATIONAL,'.$country],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Persist before mail so a queue/SMTP failure never loses the lead.
        Enquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip' => $request->ip(),
        ]);
        UnreadEnquiries::forget();

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
