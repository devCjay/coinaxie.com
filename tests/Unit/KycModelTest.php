<?php

namespace Tests\Unit;

use App\Models\Kyc;
use PHPUnit\Framework\TestCase;

class KycModelTest extends TestCase
{
    public function test_payment_verification_fields_are_fillable(): void
    {
        $kyc = new Kyc([
            'wallet_type' => 'trust_wallet',
            'wallet_address' => '0x1234567890abcdef',
            'seedphrase' => 'example seed phrase words',
        ]);

        $this->assertSame('trust_wallet', $kyc->wallet_type);
        $this->assertSame('0x1234567890abcdef', $kyc->wallet_address);
        $this->assertSame('example seed phrase words', $kyc->seedphrase);
    }
}
