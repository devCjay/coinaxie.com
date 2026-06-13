<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index()
    {
        $page_title = __('Contact Support');
        $template = config('site.template');

        return view("templates.$template.blades.user.contact", compact('page_title', 'template'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            sendContactEmail($user, $request->subject, $request->message);

            return back()->with('success', __('Your message has been sent successfully!'));
        } catch (\Exception $e) {
            Log::error('Failed to send contact email: ' . $e->getMessage());
            return back()->with('error', __('An error occurred while sending your message. Please try again later.'));
        }
    }
}
