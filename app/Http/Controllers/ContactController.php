<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(StoreContactMessageRequest $request): RedirectResponse
    {
        // Milestone 1 records the enquiry in the application log. Routing these to the PESO
        // inbox lands with the Brevo mail integration in Milestone 2 (PRD §12).
        Log::channel('single')->info('Contact enquiry', $request->validated());

        return back()->with('status', 'Thank you. Your message has been sent to the PESO office.');
    }
}
