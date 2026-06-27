<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\NotificationMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Mail\RichTextEmail;
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
    public function user_can_create_a_support_ticket_with_image_attachment()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->post(route('user.tickets.store'), [
                'subject' => 'Payment proof',
                'message' => 'Please check the attached screenshot.',
                'attachment' => UploadedFile::fake()->image('ticket-proof.png'),
            ]);

        $ticket = SupportTicket::first();
        $message = SupportTicketMessage::first();

        $response->assertRedirect(route('user.tickets.show', $ticket->id));

        $this->assertNotNull($message);
        $this->assertNotNull($message->attachment_path);
        Storage::disk('public')->assertExists($message->attachment_path);
    }

    #[Test]
    public function user_can_reply_to_a_ticket_with_an_optional_image_attachment()
    {
        Storage::fake('public');

        $ticket = SupportTicket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'TKT-USERREPLY',
            'subject' => 'Verification issue',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.tickets.reply', $ticket->id), [
                'message' => 'Here is the screenshot you requested.',
                'attachment' => UploadedFile::fake()->image('reply-proof.png'),
            ]);

        $response->assertRedirect();

        $message = SupportTicketMessage::latest('id')->first();

        $this->assertSame('user', $message->sender_type);
        $this->assertNotNull($message->attachment_path);
        Storage::disk('public')->assertExists($message->attachment_path);
    }

    #[Test]
    public function user_can_load_ticket_attachment_through_the_ticket_route()
    {
        Storage::fake('public');

        $ticket = SupportTicket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'TKT-ATTACH01',
            'subject' => 'Attachment route',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $path = UploadedFile::fake()->image('ticket-view.png')->store('support-tickets', 'public');

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'sender_type' => 'user',
            'message' => 'Please check my image.',
            'attachment_path' => $path,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.tickets.attachment', [$ticket->id, $message->id]));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_reply_to_and_close_a_ticket()
    {
        Mail::fake();

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
        $this->assertDatabaseHas('notification_messages', [
            'user_id' => $this->user->id,
            'title' => 'Support ticket reply',
        ]);
        Mail::assertSent(RichTextEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email) && $mail->subject === 'Support ticket reply: TKT-TEST123';
        });

        $closeResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tickets.close', $ticket->id));

        $closeResponse->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
            'closed_by_admin_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function admin_can_reply_to_a_ticket_with_an_optional_image_attachment()
    {
        Storage::fake('public');

        $ticket = SupportTicket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'TKT-ADMINIMG',
            'subject' => 'Withdrawal issue',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tickets.reply', $ticket->id), [
                'message' => 'Please review this support reference image.',
                'attachment' => UploadedFile::fake()->image('admin-reply.png'),
            ]);

        $response->assertRedirect();

        $message = SupportTicketMessage::latest('id')->first();

        $this->assertSame('admin', $message->sender_type);
        $this->assertNotNull($message->attachment_path);
        Storage::disk('public')->assertExists($message->attachment_path);
    }

    #[Test]
    public function admin_can_load_ticket_attachment_through_the_ticket_route()
    {
        Storage::fake('public');

        $ticket = SupportTicket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'TKT-ADMINAT',
            'subject' => 'Admin attachment route',
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $path = UploadedFile::fake()->image('admin-view.png')->store('support-tickets', 'public');

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'admin_id' => $this->admin->id,
            'sender_type' => 'admin',
            'message' => 'Here is an admin image.',
            'attachment_path' => $path,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.tickets.attachment', [$ticket->id, $message->id]));

        $response->assertOk();
    }
}
