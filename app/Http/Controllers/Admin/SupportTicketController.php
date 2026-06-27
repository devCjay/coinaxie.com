<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $page_title = __('Support Tickets');

        $query = SupportTicket::with('user')->withCount('messages');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($ticketQuery) use ($term) {
                $ticketQuery->where('ticket_number', 'like', '%' . $term . '%')
                    ->orWhere('subject', 'like', '%' . $term . '%')
                    ->orWhereHas('user', function ($userQuery) use ($term) {
                        $userQuery->where('username', 'like', '%' . $term . '%')
                            ->orWhere('email', 'like', '%' . $term . '%')
                            ->orWhere('first_name', 'like', '%' . $term . '%')
                            ->orWhere('last_name', 'like', '%' . $term . '%');
                    });
            });
        }

        $tickets = $query
            ->orderByDesc('last_reply_at')
            ->latest()
            ->paginate(getSetting('pagination', 15))
            ->appends($request->all());

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::open()->count(),
            'closed' => SupportTicket::closed()->count(),
        ];

        $template = config('site.template');

        return view("templates.$template.blades.admin.tickets.index", compact(
            'page_title',
            'tickets',
            'stats',
        ));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'user',
            'closedByAdmin',
            'messages.user',
            'messages.admin',
        ])->findOrFail($id);

        $page_title = __('Ticket :ticket', ['ticket' => $ticket->ticket_number]);
        $template = config('site.template');

        return view("templates.$template.blades.admin.tickets.show", compact('page_title', 'ticket'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::with('user')->findOrFail($id);

        if ($ticket->status === 'closed') {
            return back()->with('error', __('This ticket has already been closed.'));
        }

        DB::transaction(function () use ($ticket, $request) {
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'admin_id' => Auth::guard('admin')->id(),
                'sender_type' => 'admin',
                'message' => $request->message,
            ]);

            $ticket->update([
                'last_reply_at' => now(),
            ]);
        });

        recordNotificationMessage(
            $ticket->user,
            __('Support ticket reply'),
            __('Your ticket :ticket has received a new reply from support.', ['ticket' => $ticket->ticket_number]),
        );

        return back()->with('success', __('Reply sent successfully.'));
    }

    public function close($id)
    {
        $ticket = SupportTicket::with('user')->findOrFail($id);

        if ($ticket->status === 'closed') {
            return back()->with('success', __('Ticket is already closed.'));
        }

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        recordNotificationMessage(
            $ticket->user,
            __('Support ticket closed'),
            __('Your ticket :ticket has been closed by support.', ['ticket' => $ticket->ticket_number]),
        );

        return back()->with('success', __('Ticket closed successfully.'));
    }
}
