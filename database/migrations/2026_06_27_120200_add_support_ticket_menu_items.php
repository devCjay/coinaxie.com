<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $items = [
            [
                'label' => 'Support Tickets',
                'route_name' => 'user.tickets.index',
                'route_wildcard' => 'user.tickets.*',
                'params' => null,
                'url' => null,
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>',
                'type' => 'user',
                'parent_id' => null,
                'sort_order' => 12,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Support Tickets',
                'route_name' => 'admin.tickets.index',
                'route_wildcard' => 'admin.tickets.*',
                'params' => null,
                'url' => null,
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>',
                'type' => 'admin',
                'parent_id' => null,
                'sort_order' => 7,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($items as $item) {
            DB::table('menu_items')->updateOrInsert(
                [
                    'route_name' => $item['route_name'],
                    'type' => $item['type'],
                ],
                $item,
            );
        }

        Cache::forget('user_menu_items');
        Cache::forget('admin_menu_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')
            ->whereIn('route_name', ['user.tickets.index', 'admin.tickets.index'])
            ->delete();

        Cache::forget('user_menu_items');
        Cache::forget('admin_menu_items');
    }
};
