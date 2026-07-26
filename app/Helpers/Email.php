<?php

use App\Mail\AccountBan;
use App\Mail\ContactEmail;
use App\Mail\DepositEmail;
use App\Mail\EmailVerification;
use App\Mail\EtfEmail;
use App\Mail\InvestmentEmail;
use App\Mail\KycEmail;
use App\Mail\OtpVerificationEmail;
use App\Mail\ReferralEmail;
use App\Mail\RichTextEmail;
use App\Mail\StockEmail;
use App\Mail\TransactionEmail;
use App\Mail\WelcomeEmail;
use App\Mail\WithdrawalEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

if (!function_exists('sendMailImmediately')) {
    function sendMailImmediately($mailable, $email, $locale)
    {
        return Mail::to($email)->locale($locale)->send($mailable);
    }
}

// send verification email
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($name, $email, $otp_code)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['email_verification']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {

            $locale = Session::get('locale') ?? config('app.locale');
            sendMailImmediately(new EmailVerification($name, $email, $otp_code), $email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }
}



// send welcome email
if (!function_exists('sendWelcomeEmail')) {
    function sendWelcomeEmail($user)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['welcome']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $user->lang;
            sendMailImmediately(new WelcomeEmail($user), $user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    }
}


// SEND OTP VERIFICATION EMAIL
if (!function_exists('sendOtpVerificationEmail')) {
    function sendOtpVerificationEmail($name, $email, $otp_code, $ip, $user_agent, $message, $subject)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['otp_verification']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = Session::get('locale') ?? config('app.locale');
            sendMailImmediately(new OtpVerificationEmail($name, $email, $otp_code, $ip, $user_agent, $message, $subject), $email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send otp verification email: ' . $e->getMessage());
        }
    }
}


// send new transaction email
if (!function_exists('sendNewTransactionEmail')) {
    function sendNewTransactionEmail($transaction)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['transaction']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $transaction->user->lang;
            sendMailImmediately(new TransactionEmail($transaction), $transaction->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send new transaction email: ' . $e->getMessage());
        }
    }
}

// send kyc email
if (!function_exists('sendKycEmail')) {
    function sendKycEmail($subject, $kyc_record)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['kyc']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $kyc_record->user->lang;
            sendMailImmediately(new KycEmail($subject, $kyc_record), $kyc_record->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send kyc email: ' . $e->getMessage());
        }
    }
}


// send new referral email
if (!function_exists('sendNewReferralEmail')) {
    function sendNewReferralEmail($referral, $referrer)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['referral']['status'] == 'disabled') {
            return;
        }
        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $referrer->lang;
            sendMailImmediately(new ReferralEmail($referral, $referrer), $referrer->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send new referral email: ' . $e->getMessage());
        }
    }
}


// send deposit email
if (!function_exists('sendDepositEmail')) {
    function sendDepositEmail($custom_subject, $custom_message, $deposit)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['deposit']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $deposit->user->lang;
            sendMailImmediately(new DepositEmail($custom_subject, $custom_message, $deposit), $deposit->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send deposit email: ' . $e->getMessage());
        }
    }
}

// send withdrawal email
if (!function_exists('sendWithdrawalEmail')) {
    function sendWithdrawalEmail($custom_subject, $custom_message, $withdrawal)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['withdrawal']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $withdrawal->user->lang;
            sendMailImmediately(new WithdrawalEmail($custom_subject, $custom_message, $withdrawal), $withdrawal->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send withdrawal email: ' . $e->getMessage());
        }
    }
}

// send investment email
if (!function_exists('sendInvestmentEmail')) {
    function sendInvestmentEmail($custom_subject, $custom_message, $investment)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['investment']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $investment->user->lang;
            sendMailImmediately(new InvestmentEmail($custom_subject, $custom_message, $investment), $investment->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send investment email: ' . $e->getMessage());
        }
    }
}

// send stock email
if (!function_exists('sendStockEmail')) {
    function sendStockEmail($custom_subject, $custom_message, $holding_history)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['stock']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $holding_history->user->lang;
            sendMailImmediately(new StockEmail($holding_history, $custom_subject, $custom_message), $holding_history->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send stock email: ' . $e->getMessage());
        }
    }
}

// send etf email
if (!function_exists('sendEtfEmail')) {
    function sendEtfEmail($custom_subject, $custom_message, $holding_history)
    {
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['etf']['status'] == 'disabled') {
            return;
        }

        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $holding_history->user->lang;
            sendMailImmediately(new EtfEmail($holding_history, $custom_subject, $custom_message), $holding_history->user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send etf email: ' . $e->getMessage());
        }
    }
}


// send rich text email
if (!function_exists('sendRichTextEmail')) {
    function sendRichTextEmail($custom_subject, $custom_message, $user)
    {
        // $email_notification = json_decode(getSetting('email_notification'), true);
        // if ($email_notification['notifications']['rich_text']['status'] == 'disabled') {
        //     return;
        // }
        if (config('app.env') === 'sandbox') {
            return;
        }
        try {
            $locale = $user->lang;
            sendMailImmediately(new RichTextEmail($user, $custom_message, $custom_subject), $user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send rich text email: ' . $e->getMessage());
        }
    }
}

// send contact email to support
if (!function_exists('sendContactEmail')) {
    function sendContactEmail($sender, $subject, $message)
    {
        if (config('app.env') === 'sandbox') {
            return;
        }
        $supportEmail = getSetting('email');
        if (!$supportEmail) {
            return;
        }
        try {
            $locale = $sender->lang ?? config('app.locale');
            sendMailImmediately(
                new ContactEmail($sender->name, $sender->email, $message, $subject),
                $supportEmail,
                $locale
            );
        } catch (\Exception $e) {
            Log::error('Failed to send contact email: ' . $e->getMessage());
            throw $e;
        }
    }
}

// send account ban email
if (!function_exists('sendAccountBanEmail')) {
    function sendAccountBanEmail($user, $action)
    {
        if (config('app.env') === 'sandbox') {
            return;
        }
        $email_notification = json_decode(getSetting('email_notification'), true);
        if ($email_notification['notifications']['account_ban']['status'] == 'disabled') {
            return;
        }
        try {
            $locale = $user->lang;
            sendMailImmediately(new AccountBan($user, $action), $user->email, $locale);
        } catch (\Exception $e) {
            Log::error('Failed to send account ban email: ' . $e->getMessage());
        }
    }
}
