<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $page_title = __('Support Tickets');
        $user = Auth::user();

        $query = SupportTicket::where('user_id', $user->id)->withCount('messages');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($ticketQuery) use ($term) {
                $ticketQuery->where('ticket_number', 'like', '%' . $term . '%')
                    ->orWhere('subject', 'like', '%' . $term . '%');
            });
        }

        $tickets = $query
            ->orderByDesc('last_reply_at')
            ->latest()
            ->paginate(getSetting('pagination', 15))
            ->appends($request->all());

        $stats = [
            'total' => SupportTicket::where('user_id', $user->id)->count(),
            'open' => SupportTicket::where('user_id', $user->id)->open()->count(),
            'closed' => SupportTicket::where('user_id', $user->id)->closed()->count(),
        ];

        $template = config('site.template');

        return view("templates.$template.blades.user.tickets.index", compact(
            'page_title',
            'tickets',
            'stats',
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $user = Auth::user();
        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('support-tickets', 'public')
            : null;

        try {
            $ticket = DB::transaction(function () use ($validated, $user, $attachmentPath) {
                $ticket = SupportTicket::create([
                    'user_id' => $user->id,
                    'ticket_number' => $this->generateTicketNumber(),
                    'subject' => $validated['subject'],
                    'status' => 'open',
                    'last_reply_at' => now(),
                ]);

                SupportTicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'sender_type' => 'user',
                    'message' => $validated['message'],
                    'attachment_path' => $attachmentPath,
                ]);

                return $ticket;
            });
        } catch (\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $exception;
        }

        recordNotificationMessage(
            $user,
            __('Support ticket created'),
            __('Your ticket :ticket has been created successfully.', ['ticket' => $ticket->ticket_number]),
        );

        return redirect()
            ->route('user.tickets.show', $ticket->id)
            ->with('success', __('Support ticket created successfully.'));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'user',
            'closedByAdmin',
            'messages.user',
            'messages.admin',
        ])->where('user_id', Auth::id())->findOrFail($id);

        $page_title = __('Ticket :ticket', ['ticket' => $ticket->ticket_number]);
        $template = config('site.template');

        return view("templates.$template.blades.user.tickets.show", compact('page_title', 'ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($id);

        if ($ticket->status === 'closed') {
            return back()->with('error', __('This ticket has already been closed.'));
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('support-tickets', 'public')
            : null;

        try {
            DB::transaction(function () use ($ticket, $validated, $attachmentPath) {
                SupportTicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'sender_type' => 'user',
                    'message' => $validated['message'],
                    'attachment_path' => $attachmentPath,
                ]);

                $ticket->update([
                    'last_reply_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $exception;
        }

        return back()->with('success', __('Reply sent successfully.'));
    }

    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'TKT-' . strtoupper(Str::random(8));
        } while (SupportTicket::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
