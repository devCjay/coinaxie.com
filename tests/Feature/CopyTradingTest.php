<?php

namespace Tests\Feature;

use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
use App\Models\FuturesTradingOrders;
use App\Models\FuturesTradingPositions;
use App\Models\MarginTradingOrder;
use App\Models\MarginTradingPosition;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\CopyTradingService;
use App\Services\LozandServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class CopyTradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_copies_futures_market_orders(): void
    {
        $lozand = Mockery::mock(LozandServices::class);
        $lozand->shouldReceive('futureTicker')->andReturn([
            'status' => 'success',
            'data' => ['current_price' => 100],
        ]);
        $this->app->instance(LozandServices::class, $lozand);

        $proUser = User::create([
            'first_name' => 'Pro',
            'last_name' => 'Trader',
            'email' => 'pro@example.com',
            'password' => Hash::make('password'),
        ]);
        $follower = User::create([
            'first_name' => 'Copy',
            'last_name' => 'User',
            'email' => 'follower@example.com',
            'password' => Hash::make('password'),
        ]);

        TradingAccount::create([
            'user_id' => $proUser->id,
            'account_type' => 'futures',
            'account_status' => 'active',
            'balance' => 1000,
            'currency' => 'USDT',
            'mode' => 'live',
            'equity' => 0,
            'level' => 'standard',
            'margin_call' => 100,
        ]);

        $followerAccount = TradingAccount::create([
            'user_id' => $follower->id,
            'account_type' => 'futures',
            'account_status' => 'active',
            'balance' => 1000,
            'currency' => 'USDT',
            'mode' => 'live',
            'equity' => 0,
            'level' => 'standard',
            'margin_call' => 100,
        ]);

        $pro = CopyTradingProTrader::create([
            'user_id' => $proUser->id,
            'status' => 'active',
        ]);

        $relationship = CopyTradingRelationship::create([
            'pro_trader_id' => $pro->id,
            'follower_id' => $follower->id,
            'market_type' => 'futures',
            'allocation_type' => 'percent',
            'allocation_value' => 10,
            'max_leverage' => 50,
            'margin_order_mode' => 'normal',
            'status' => 'active',
        ]);

        $sourceOrder = FuturesTradingOrders::create([
            'user_id' => $proUser->id,
            'type' => 'market',
            'ticker' => 'BTCUSDT',
            'side' => 'buy',
            'size' => 1,
            'price' => 100,
            'status' => 'filled',
            'order_id' => 'ORD-TEST',
            'timestamp' => (string) now()->valueOf(),
            'take_profit' => 0,
            'stop_loss' => 0,
            'locked_margin' => 10,
            'leverage' => 10,
        ]);

        $service = $this->app->make(CopyTradingService::class);
        $service->handleFuturesOrderCreated($sourceOrder);

        $this->assertDatabaseHas('futures_trading_orders', [
            'user_id' => $follower->id,
            'ticker' => 'BTCUSDT',
            'side' => 'buy',
            'is_copy' => 1,
            'copied_from_user_id' => $proUser->id,
            'copied_from_order_id' => $sourceOrder->id,
            'copy_relationship_id' => $relationship->id,
        ]);

        $this->assertNotNull(FuturesTradingPositions::where('user_id', $follower->id)->where('ticker', 'BTCUSDT')->first());
        $this->assertTrue((float) $followerAccount->fresh()->balance < 1000);
    }

    public function test_it_copies_margin_market_orders(): void
    {
        $lozand = Mockery::mock(LozandServices::class);
        $lozand->shouldReceive('margin')->andReturn([
            'status' => 'success',
            'data' => ['current_price' => 100],
        ]);
        $this->app->instance(LozandServices::class, $lozand);

        $proUser = User::create([
            'first_name' => 'Pro',
            'last_name' => 'Trader',
            'email' => 'pro2@example.com',
            'password' => Hash::make('password'),
        ]);
        $follower = User::create([
            'first_name' => 'Copy',
            'last_name' => 'User',
            'email' => 'follower2@example.com',
            'password' => Hash::make('password'),
        ]);

        TradingAccount::create([
            'user_id' => $proUser->id,
            'account_type' => 'margin',
            'account_status' => 'active',
            'balance' => 1000,
            'currency' => 'USDT',
            'mode' => 'live',
            'equity' => 0,
            'level' => 'standard',
            'margin_call' => 100,
        ]);

        $followerAccount = TradingAccount::create([
            'user_id' => $follower->id,
            'account_type' => 'margin',
            'account_status' => 'active',
            'balance' => 1000,
            'currency' => 'USDT',
            'mode' => 'live',
            'equity' => 0,
            'level' => 'standard',
            'margin_call' => 100,
        ]);

        $pro = CopyTradingProTrader::create([
            'user_id' => $proUser->id,
            'status' => 'active',
        ]);

        $relationship = CopyTradingRelationship::create([
            'pro_trader_id' => $pro->id,
            'follower_id' => $follower->id,
            'market_type' => 'margin',
            'allocation_type' => 'percent',
            'allocation_value' => 10,
            'max_leverage' => 50,
            'margin_order_mode' => 'normal',
            'status' => 'active',
        ]);

        $sourceOrder = MarginTradingOrder::create([
            'user_id' => $proUser->id,
            'type' => 'market',
            'order_mode' => 'normal',
            'ticker' => 'BTCUSDT',
            'side' => 'buy',
            'size' => 1,
            'price' => 100,
            'locked_margin' => 10,
            'leverage' => 10,
            'take_profit' => 0,
            'stop_loss' => 0,
            'status' => 'filled',
            'timestamp' => (string) now()->valueOf(),
        ]);

        $service = $this->app->make(CopyTradingService::class);
        $service->handleMarginOrderCreated($sourceOrder);

        $this->assertDatabaseHas('margin_trading_orders', [
            'user_id' => $follower->id,
            'ticker' => 'BTCUSDT',
            'side' => 'buy',
            'is_copy' => 1,
            'copied_from_user_id' => $proUser->id,
            'copied_from_order_id' => $sourceOrder->id,
            'copy_relationship_id' => $relationship->id,
        ]);

        $this->assertNotNull(MarginTradingPosition::where('user_id', $follower->id)->where('ticker', 'BTCUSDT')->first());
        $this->assertTrue((float) $followerAccount->fresh()->balance < 1000);
    }
}

