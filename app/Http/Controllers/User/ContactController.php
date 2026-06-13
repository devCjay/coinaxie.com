<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        // TODO: Send email to support email using existing email helpers

        return back()->with('success', __('Your message has been sent successfully!'));
    }
}
