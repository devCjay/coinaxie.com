<?php

namespace App\Services;

use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LaunchpadWalletService
{
    public function getOrCreateSpotAccount(User $user, string $currency): TradingAccount
    {
        $currency = strtoupper(trim($currency));

        $account = $user->tradingAccounts()
            ->where('account_type', 'spot')
            ->where('currency', $currency)
            ->first();

        if ($account) {
            return $account;
        }

        return TradingAccount::create([
            'user_id' => $user->id,
            'account_type' => 'spot',
            'account_status' => 'active',
            'balance' => 0,
            'currency' => $currency,
            'mode' => 'live',
            'equity' => 0,
            'level' => 'micro',
            'margin_call' => 100,
        ]);
    }

    public function debitSpot(User $user, string $currency, float $amount): TradingAccount
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        return DB::transaction(function () use ($user, $currency, $amount) {
            $account = $this->getOrCreateSpotAccount($user, $currency);
            $account->refresh();
            if ($account->account_status !== 'active') {
                throw new \RuntimeException('Spot account inactive');
            }
            if ((float) $account->balance < $amount) {
                throw new \RuntimeException('Insufficient spot balance');
            }
            $account->decrement('balance', $amount);
            return $account->fresh();
        });
    }

    public function creditSpot(User $user, string $currency, float $amount): TradingAccount
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        return DB::transaction(function () use ($user, $currency, $amount) {
            $account = $this->getOrCreateSpotAccount($user, $currency);
            $account->refresh();
            if ($account->account_status !== 'active') {
                throw new \RuntimeException('Spot account inactive');
            }
            $account->increment('balance', $amount);
            return $account->fresh();
        });
    }
}

