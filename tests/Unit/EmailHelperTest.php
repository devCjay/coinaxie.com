<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailHelperTest extends TestCase
{
    public function test_send_verification_email_returns_false_when_mail_delivery_fails()
    {
        config()->set('app.env', 'production');

        app()->instance('website_settings', (object) [
            'email_notification' => json_encode([
                'notifications' => [
                    'email_verification' => ['status' => 'enabled'],
                ],
            ]),
        ]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('locale')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \Exception('SMTP down'));

        $this->assertFalse(sendVerificationEmail('Test User', 'test@example.com', '123456'));
    }
}
