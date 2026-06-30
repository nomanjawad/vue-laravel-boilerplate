<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tiny endpoint that powers the admin bell. Polled every 60s from
 * <NotificationBell>. Returns the latest 10 + an unread count.
 */
class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'recent' => $user->notifications()->latest()->take(10)->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? 'Notification',
                    'body' => $n->data['body'] ?? '',
                    'href' => $n->data['href'] ?? null,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
