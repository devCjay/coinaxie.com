<?php

use App\Models\MenuItem;
use App\Models\CustomMarketToken;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_market_tokens')) {
            Schema::create('custom_market_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('ticker', 32)->unique();
                $table->enum('market', ['futures', 'margin', 'both'])->default('both');
                $table->decimal('current_price', 28, 12)->default(0);
                $table->decimal('open_price', 28, 12)->nullable();
                $table->decimal('high', 28, 12)->nullable();
                $table->decimal('low', 28, 12)->nullable();
                $table->decimal('volume', 28, 12)->nullable();
                $table->decimal('change_1d_percentage', 12, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('custom_market_tokens') && Schema::hasTable('settings')) {
            $hasAny = CustomMarketToken::query()->exists();
            if (!$hasAny) {
                $raw = Setting::query()->where('key', 'trading_custom_market_prices')->value('value');
                $items = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($items)) {
                    foreach ($items as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $ticker = strtoupper(trim((string) ($row['ticker'] ?? '')));
                        $market = (string) ($row['market'] ?? 'both');
                        if (!in_array($market, ['futures', 'margin', 'both'], true)) {
                            $market = 'both';
                        }
                        $price = (float) ($row['current_price'] ?? 0);
                        if ($ticker === '' || $price <= 0) {
                            continue;
                        }
                        CustomMarketToken::query()->updateOrCreate(
                            ['ticker' => $ticker],
                            [
                                'market' => $market,
                                'current_price' => $price,
                                'open_price' => isset($row['open_price']) ? (float) ($row['open_price'] ?? 0) : null,
                                'high' => isset($row['high']) ? (float) ($row['high'] ?? 0) : null,
                                'low' => isset($row['low']) ? (float) ($row['low'] ?? 0) : null,
                                'volume' => isset($row['volume']) ? (float) ($row['volume'] ?? 0) : null,
                                'change_1d_percentage' => (float) ($row['change_1d_percentage'] ?? 0),
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }

        if (Schema::hasTable('menu_items')) {
            $exists = MenuItem::query()
                ->where('type', 'admin')
                ->where('route_name', 'admin.custom-tokens.index')
                ->exists();

            if (!$exists) {
                MenuItem::query()->create([
                    'label' => 'Custom Tokens',
                    'route_name' => 'admin.custom-tokens.index',
                    'route_wildcard' => 'admin.custom-tokens.*',
                    'url' => null,
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l3-3 4 4 7-7"/></svg>',
                    'type' => 'admin',
                    'parent_id' => null,
                    'sort_order' => 8,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_items')) {
            MenuItem::query()
                ->where('type', 'admin')
                ->where('route_name', 'admin.custom-tokens.index')
                ->delete();
        }

        Schema::dropIfExists('custom_market_tokens');
    }
};
