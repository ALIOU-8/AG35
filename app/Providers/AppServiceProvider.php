<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        Gate::define('manage-members', fn (User $user) => $user->isActive() && $user->canManageMembers());
        Gate::define('admin', fn (User $user) => $user->isActive() && $user->isAdmin());

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with([
                'unreadMessages' => $user?->isActive() ? self::unreadMessagesFor($user) : 0,
                'unreadNotifications' => $user?->isActive() ? $user->unreadNotifications()->count() : 0,
                'pendingMembers' => $user?->canManageMembers() ? User::where('status', 'pending')->count() : 0,
            ]);
        });

        View::composer(['layouts.site', 'site.*'], function ($view) {
            $view->with('site', Setting::values());
        });
    }

    protected static function unreadMessagesFor(User $user): int
    {
        return Message::query()
            ->join('conversation_user', function ($join) use ($user) {
                $join->on('conversation_user.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_user.user_id', $user->id);
            })
            ->where('messages.user_id', '!=', $user->id)
            ->whereRaw('messages.created_at > coalesce(conversation_user.last_read_at, ?)', ['1970-01-01 00:00:00'])
            ->count();
    }
}
