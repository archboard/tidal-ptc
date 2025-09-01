<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SmtpTest;
use Illuminate\Http\Request;

class SendSmtpTestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user->notify(new SmtpTest);

        session()->flash('success', 'SMTP test sent successfully.');

        return back();
    }
}
