<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Page des notifications, ou les dernières en JSON pour le menu de la cloche.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json([
                'notifications' => $user->notifications()->limit(15)->get()->map(fn ($notification) => NotificationPresenter::present($notification)),
                'unread' => $user->unreadNotifications()->count(),
            ]);
        }

        $notifications = $user->notifications()->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'items' => $notifications->getCollection()->map(fn ($notification) => NotificationPresenter::present($notification)),
        ]);
    }

    /**
     * Clic sur une notification : marquée comme lue, puis redirection vers la publication ou la réunion.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return redirect()->to($record->data['url'] ?? route('feed'));
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['unread' => 0]);
        }

        return back()->with('status', 'Toutes les notifications sont marquées comme lues.');
    }
}
