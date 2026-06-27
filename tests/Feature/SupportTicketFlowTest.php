<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->user = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'username' => 'janedoe',
            'lang' => 'en',
            'status' => 'active',
            'balance' => 0,
            'password' => Hash::make('password123'),
        ]);

        $this->admin = Admin::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    #[Test]
    public function user_can_create_a_support_ticket()
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.tickets.store'), [
                'subject' => 'Deposit issue',
                'message' => 'My deposit has not shown up yet.',
            ]);

        $ticket = SupportTicket::first();

        $response->assertRedirect(route('user.tickets.show', $ticket->id));

        $this->assertNotNull($ticket);
        $this->assertSame('open', $ticket->status);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'user_id' => $this->user->id,
            'subject' => 'Deposit issue',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'sender_type' => 'user',
            'message' => 'My deposit has not shown up yet.',
        ]);
    }

    #[Test]
    public function admin_can_reply_to_and_close_a_ticket()
    {
        $ticket = SupportTicket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'TKT-TEST123',
            'subject' => 'Login issue',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'sender_type' => 'user',
            'message' => 'I cannot log in to my account.',
        ]);

        $replyResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tickets.reply', $ticket->id), [
                'message' => 'Please reset your password and try again.',
            ]);

        $replyResponse->assertRedirect();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'admin_id' => $this->admin->id,
            'sender_type' => 'admin',
            'message' => 'Please reset your password and try again.',
        ]);

        $closeResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tickets.close', $ticket->id));

        $closeResponse->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
            'closed_by_admin_id' => $this->admin->id,
        ]);
    }
}
