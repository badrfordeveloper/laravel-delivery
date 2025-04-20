<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FrontController extends Controller
{
    //
    public function mailContact(Request $request)
    {
        Log::info('mailContact : '.json_encode($request->all()));
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'required|string',
        ]);

        try {
            // Send email
            Mail::to('mrbadrjeddab@gmail.com')->send(new \App\Mail\FrontContactMail($validated));

            return response()->json([
                'message' => 'Votre message a été envoyé avec succès!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

}
