<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'entreprise' => 'nullable|string|max:255',
            'sujet' => 'required|in:demo,devis,support,partenariat,autre',
            'message' => 'required|string|max:5000',
        ]);

        // Log the contact request (can be replaced with email/notification later)
        Log::channel('single')->info('Contact form submission', $data);

        return redirect()->route('contact')->with('success', 'Votre message a bien ete envoye. Nous vous repondrons sous 24h.');
    }
}
