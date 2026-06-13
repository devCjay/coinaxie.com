<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the menu item already exists
        $exists = DB::table('menu_items')->where('route_name', 'user.contact')->exists();

        if (!$exists) {
            // First, increment sort_order for all menu items after KYC (sort_order >= 3)
            DB::table('menu_items')
                ->where('type', 'user')
                ->where('sort_order', '>=', 3)
                ->increment('sort_order');

            // Insert the new Contact Support menu item
            DB::table('menu_items')->insert([
                'label' => 'Contact Support',
                'route_name' => 'user.contact',
                'route_wildcard' => 'user.contact',
                'url' => null,
                'type' => 'user',
                'sort_order' => 3,
                'is_active' => true,
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                'parent_id' => null,
                'params' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')->where('route_name', 'user.contact')->delete();
    }
};
