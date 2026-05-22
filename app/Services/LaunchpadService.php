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

        $purchase = DB::transaction(function () use ($user, $project, $quoteAmount, $max) {
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
        $this->notifyPurchase($user, $project, $purchase);
        return $purchase;
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
        $notify = [];
        $shouldNotify = false;

        DB::transaction(function () use ($project, &$notify, &$shouldNotify) {
            $project->refresh();
            if (!in_array($project->status, ['live', 'ended'], true)) {
                throw new \RuntimeException('Invalid status');
            }

            $purchases = LaunchpadPurchase::where('project_id', $project->id)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            $shouldNotify = $project->sale_finalized_notified_at === null;

            foreach ($purchases as $purchase) {
                $user = User::find($purchase->user_id);
                if (!$user) {
                    continue;
                }
                $this->wallet->creditSpot($user, $project->token_symbol, (float) $purchase->token_amount);
                $purchase->update(['status' => 'allocated']);
                if ($shouldNotify) {
                    $notify[] = [
                        'user_id' => (int) $user->id,
                        'token_amount' => (float) $purchase->token_amount,
                        'quote_amount' => (float) $purchase->quote_amount,
                        'quote_currency' => (string) $purchase->quote_currency,
                    ];
                }
            }

            $project->update(['status' => 'ended']);
            if ($shouldNotify) {
                $project->update(['sale_finalized_notified_at' => now()]);
            }
            $this->ensureMarket($project);
        });

        if ($shouldNotify) {
            $this->notifyFinalized($project, $notify);
        }
    }

    public function enableTrading(LaunchpadProject $project): void
    {
        $buyerIds = [];
        $shouldNotify = false;

        DB::transaction(function () use ($project, &$buyerIds, &$shouldNotify) {
            $project->refresh();
            $shouldNotify = $project->trading_enabled_notified_at === null;
            $project->update([
                'status' => 'launched',
                'trading_enabled' => true,
            ]);
            $market = $this->ensureMarket($project);
            $market->update(['status' => 'active']);
            if ($shouldNotify) {
                $project->update(['trading_enabled_notified_at' => now()]);
                $buyerIds = LaunchpadPurchase::where('project_id', $project->id)
                    ->whereIn('status', ['reserved', 'allocated'])
                    ->distinct()
                    ->pluck('user_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();
            }
        });

        if ($shouldNotify && !empty($buyerIds)) {
            $this->notifyTradingEnabled($project, $buyerIds);
        }
    }

    protected function notifyPurchase(User $buyer, LaunchpadProject $project, LaunchpadPurchase $purchase): void
    {
        try {
            $title = __('Launchpad purchase successful');
            $body = __('You purchased :tokenAmount :token for :quoteAmount :quote in :project.', [
                'tokenAmount' => number_format((float) $purchase->token_amount, 8, '.', ''),
                'token' => strtoupper((string) $project->token_symbol),
                'quoteAmount' => number_format((float) $purchase->quote_amount, 8, '.', ''),
                'quote' => strtoupper((string) $purchase->quote_currency),
                'project' => (string) $project->name,
            ]);

            recordNotificationMessage($buyer, $title, $body);
            sendRichTextEmail($title, nl2br(e($body)), $buyer);

            $creator = User::find((int) $project->created_by_user_id);
            if ($creator) {
                $title2 = __('New launchpad purchase');
                $buyerName = $buyer->username ?? $buyer->email ?? __('Buyer');
                $body2 = __(':buyer purchased :quoteAmount :quote in :project.', [
                    'buyer' => $buyerName,
                    'quoteAmount' => number_format((float) $purchase->quote_amount, 8, '.', ''),
                    'quote' => strtoupper((string) $purchase->quote_currency),
                    'project' => (string) $project->name,
                ]);
                recordNotificationMessage($creator, $title2, $body2);
            }
        } catch (\Throwable $e) {
        }
    }

    protected function notifyFinalized(LaunchpadProject $project, array $notify): void
    {
        foreach ($notify as $row) {
            $user = User::find((int) ($row['user_id'] ?? 0));
            if (!$user) {
                continue;
            }
            try {
                $title = __('Launchpad allocation completed');
                $body = __('Your purchase in :project was allocated. You received :tokenAmount :token.', [
                    'project' => (string) $project->name,
                    'tokenAmount' => number_format((float) ($row['token_amount'] ?? 0), 8, '.', ''),
                    'token' => strtoupper((string) $project->token_symbol),
                ]);
                recordNotificationMessage($user, $title, $body);
                sendRichTextEmail($title, nl2br(e($body)), $user);
            } catch (\Throwable $e) {
            }
        }
    }

    protected function notifyTradingEnabled(LaunchpadProject $project, array $buyerIds): void
    {
        $tradeUrl = route('user.launchpad.trade', $project->slug);
        foreach ($buyerIds as $userId) {
            $user = User::find((int) $userId);
            if (!$user) {
                continue;
            }
            try {
                $title = __('Launchpad trading is now live');
                $body = __('Trading is enabled for :project (:symbol). You can trade here: :url', [
                    'project' => (string) $project->name,
                    'symbol' => strtoupper((string) $project->token_symbol) . '/' . strtoupper((string) $project->quote_currency),
                    'url' => $tradeUrl,
                ]);
                recordNotificationMessage($user, $title, $body);
                sendRichTextEmail($title, nl2br(e($body)), $user);
            } catch (\Throwable $e) {
            }
        }
    }
}
