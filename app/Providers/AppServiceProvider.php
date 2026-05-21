<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('website_settings', function () {
            return Cache::rememberForever('core_site_settings', function () {
                return (object) Setting::pluck('value', 'key')->toArray();
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        //Support for  older MySQL / MariaDB
        Schema::defaultStringLength(191);

        $this->app->booted(function () {
            if (!Schema::hasTable('menu_items')) {
                return;
            }

            $touched = false;

            $userItem = MenuItem::where('type', 'user')->where('route_name', 'user.launchpad.index')->first();
            if (!$userItem) {
                MenuItem::create([
                    'label' => 'Launchpad',
                    'route_name' => 'user.launchpad.index',
                    'route_wildcard' => 'user.launchpad.*',
                    'url' => null,
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13l3 3 9-9"/><path d="M9 16l-3.5 3.5a2 2 0 0 1-2.8 0 2 2 0 0 1 0-2.8L6 13"/><path d="M18 7l3.5-3.5a2 2 0 0 0 0-2.8 2 2 0 0 0-2.8 0L15 4"/></svg>',
                    'type' => 'user',
                    'sort_order' => 9,
                    'is_active' => true,
                    'parent_id' => null,
                ]);
                $touched = true;
            } else {
                $updates = [];
                if (!$userItem->is_active) {
                    $updates['is_active'] = true;
                }
                if ($userItem->parent_id !== null) {
                    $updates['parent_id'] = null;
                }
                if (($userItem->route_wildcard ?? '') !== 'user.launchpad.*') {
                    $updates['route_wildcard'] = 'user.launchpad.*';
                }
                if (!empty($updates)) {
                    $userItem->update($updates);
                    $touched = true;
                }
            }

            $adminItem = MenuItem::where('type', 'admin')->where('route_name', 'admin.launchpad.index')->first();
            if (!$adminItem) {
                MenuItem::create([
                    'label' => 'Launchpad',
                    'route_name' => 'admin.launchpad.index',
                    'route_wildcard' => 'admin.launchpad.*',
                    'url' => null,
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"/></svg>',
                    'type' => 'admin',
                    'sort_order' => 7,
                    'is_active' => true,
                    'parent_id' => null,
                ]);
                $touched = true;
            } else {
                $updates = [];
                if (!$adminItem->is_active) {
                    $updates['is_active'] = true;
                }
                if ($adminItem->parent_id !== null) {
                    $updates['parent_id'] = null;
                }
                if (($adminItem->route_wildcard ?? '') !== 'admin.launchpad.*') {
                    $updates['route_wildcard'] = 'admin.launchpad.*';
                }
                if (!empty($updates)) {
                    $adminItem->update($updates);
                    $touched = true;
                }
            }

            if ($touched) {
                Cache::forget('admin_menu_items');
                Cache::forget('user_menu_items');
            }

            $copyItem = MenuItem::where('type', 'user')->where('route_name', 'user.trading.copy-trading')->first();
            if ($copyItem) {
                $activityItem = MenuItem::where('type', 'user')->where('route_name', 'user.trading.copy-trading.activity')->first();
                if (!$activityItem) {
                    MenuItem::create([
                        'label' => 'Copy Activity',
                        'route_name' => 'user.trading.copy-trading.activity',
                        'route_wildcard' => 'user.trading.copy-trading.activity',
                        'url' => null,
                        'icon' => null,
                        'type' => 'user',
                        'sort_order' => 1,
                        'is_active' => true,
                        'parent_id' => $copyItem->id,
                    ]);
                    Cache::forget('user_menu_items');
                } else {
                    $updates = [];
                    if (!$activityItem->is_active) {
                        $updates['is_active'] = true;
                    }
                    if ((int) $activityItem->parent_id !== (int) $copyItem->id) {
                        $updates['parent_id'] = $copyItem->id;
                    }
                    if (($activityItem->route_wildcard ?? '') !== 'user.trading.copy-trading.activity') {
                        $updates['route_wildcard'] = 'user.trading.copy-trading.activity';
                    }
                    if (!empty($updates)) {
                        $activityItem->update($updates);
                        Cache::forget('user_menu_items');
                    }
                }
            }
        });

        // Sharing variables only to the user layouts
        View::composer('templates.*.blades.layouts.user', function ($view) {
            // share unread messages to the layout
            $view->with('unread_notification_messages', Auth::check()
                ? Auth::user()->notificationMessages()->where('status', 'unread')->latest()->get()
                : collect());

            // share user menu items to the user layout
            $view->with('user_menu_items', Cache::remember('user_menu_items', 60 * 60, function () {
                return MenuItem::with('children')->where('type', 'user')
                    ->where('is_active', true)
                    ->where('parent_id', null)
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }));
        });

        // Sharing variables only to the admin layout
        View::composer('templates.*.blades.admin.layouts.admin', function ($view) {
            // share admin menu items to the admin layout
            $view->with('admin_menu_items', Cache::remember('admin_menu_items', 60 * 60, function () {
                return MenuItem::with('children')->where('type', 'admin')
                    ->where('is_active', true)
                    ->where('parent_id', null)
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }));
        });


        // Append date to outgoing emails

        // Register missing translation handler
        app('translator')->handleMissingKeysUsing(new \App\Listeners\LogMissingTranslation);

        if (config('site.append_date_to_emails') == 'enabled') {
            Event::listen(MessageSending::class, function (MessageSending $event) {
                $message = $event->message;

                $subject = $message->getSubject();

                if ($subject) {
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $message->subject($subject . ' - ' . $timestamp);
                }
            });
        }

        //inject encrypted services for social login
        $services = ['google', 'github', 'facebook', 'twitter', 'linkedin', 'gitlab', 'bitbucket'];
        foreach ($services as $service) {

            $clientId = config("services.$service.client_id");
            $clientSecret = config("services.$service.client_secret");

            if ($clientId) {
                config()->set("services.$service.client_id", safeDecrypt($clientId));
            }

            if ($clientSecret) {
                config()->set("services.$service.client_secret", safeDecrypt($clientSecret));
            }
        }
    }
}
