<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Settings\Setting;
use App\Notifications\NewContactNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    /**
     * Display the contact page.
     */
    public function index()
    {
        return view('pages.contact');
    }

    /**
     * Store a new contact message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ]);

        // Create contact
        $contact = Contact::create($validated);

        // Send email notification to admin
        $adminEmail = Setting::getValue('contact_email', config('mail.from.address'));
        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new NewContactNotification($contact));
        }

        return redirect()->route('contact.index')
            ->with('success', __('Your message has been sent successfully. We will get back to you soon!'));
    }
}
