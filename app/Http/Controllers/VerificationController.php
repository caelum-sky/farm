<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        AppNotification::create([
            'user_id' => $request->user()->id,
            'channel' => 'in_app',
            'type' => 'profile.email_verified',
            'status' => 'sent',
            'subject' => 'Email verified',
            'body' => 'Your email address is now verified for protected marketplace workflows.',
            'sent_at' => now(),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Email verified successfully.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('profile.edit')->with('status', 'Your email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent. Check your email or local mail log.');
    }
}
