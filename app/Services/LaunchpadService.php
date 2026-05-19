<?php

namespace App\Services;

use App\Models\LaunchpadMarket;
use App\Models\LaunchpadProject;
use App\Models\LaunchpadPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaunchpadService
{
    public function __construct(
        protected LaunchpadWalletService $wallet
    ) {
    }

    public function buy(User $user, LaunchpadProject $project, float $quoteAmount): LaunchpadPurchase
    {
        $quoteAmount = (float) $quoteAmount;
        if ($quoteAmount <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        if ($project->status !== 'live') {
            throw new \RuntimeException('Sale not live');
        }

        $now = now();
        if ($project->sale_start_at && $now->lt($project->sale_start_at)) {
            throw new \RuntimeException('Sale not started');
        }
        if ($project->sale_end_at && $now->gt($project->sale_end_at)) {
            throw new \RuntimeException('Sale ended');
        }

        $min = (float) $project->min_buy_quote;
        $max = (float) $project->max_buy_quote;
        if ($min > 0 && $quoteAmount < $min) {
            throw new \RuntimeException('Below minimum');
        }

        return DB::transaction(function () use ($user, $project, $quoteAmount, $max) {
            $project->refresh();

            $userTotal = (float) LaunchpadPurchase::where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['reserved', 'allocated'])
                ->sum('quote_amount');

            if ($max > 0 && ($userTotal + $quoteAmount) > $max) {
                throw new \RuntimeException('Above maximum');
            }

            $hardCap = (float) $project->hard_cap_quote;
            if ($hardCap > 0 && ((float) $project->sold_quote + $quoteAmount) > $hardCap) {
                throw new \RuntimeException('Hard cap reached');
            }

            $price = (float) $project->sale_price;
            if ($price <= 0) {
                throw new \RuntimeException('Invalid price');
            }

            $tokenAmount = $quoteAmount / $price;

            $this->wallet->debitSpot($user, $project->quote_currency, $quoteAmount);

            $purchase = LaunchpadPurchase::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'quote_currency' => $project->quote_currency,
                'quote_amount' => $quoteAmount,
                'token_amount' => $tokenAmount,
                'price' => $price,
                'status' => 'reserved',
                'reference' => (string) Str::uuid(),
            ]);

            $project->increment('sold_quote', $quoteAmount);
            $project->increment('sold_tokens', $tokenAmount);

            return $purchase;
        });
    }

    public function ensureMarket(LaunchpadProject $project): LaunchpadMarket
    {
        $market = LaunchpadMarket::where('project_id', $project->id)->first();
        if ($market) {
            return $market;
        }

        $symbol = strtoupper($project->token_symbol . $project->quote_currency);

        return LaunchpadMarket::create([
            'project_id' => $project->id,
            'symbol' => $symbol,
            'base_currency' => strtoupper($project->token_symbol),
            'quote_currency' => strtoupper($project->quote_currency),
            'status' => 'inactive',
            'last_price' => 0,
            'volume_24h_base' => 0,
            'volume_24h_quote' => 0,
        ]);
    }

    public function finalizeSale(LaunchpadProject $project): void
    {
        DB::transaction(function () use ($project) {
            $project->refresh();
            if (!in_array($project->status, ['live', 'ended'], true)) {
                throw new \RuntimeException('Invalid status');
            }

            $purchases = LaunchpadPurchase::where('project_id', $project->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            foreach ($purchases as $purchase) {
                $user = User::find($purchase->user_id);
                if (!$user) {
                    continue;
                }
                $this->wallet->creditSpot($user, $project->token_symbol, (float) $purchase->token_amount);
                $purchase->update(['status' => 'allocated']);
            }

            $project->update(['status' => 'ended']);
            $this->ensureMarket($project);
        });
    }

    public function enableTrading(LaunchpadProject $project): void
    {
        DB::transaction(function () use ($project) {
            $project->refresh();
            $project->update([
                'status' => 'launched',
                'trading_enabled' => true,
            ]);
            $market = $this->ensureMarket($project);
            $market->update(['status' => 'active']);
        });
    }
}

