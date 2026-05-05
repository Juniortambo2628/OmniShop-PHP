<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) return response()->json([], 401);

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(50)
            ->get();
            
        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) return response()->json(['count' => 0]);

        $count = Notification::where('user_id', $user->id)->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function markAsRead(string $id)
    {
        $user = Auth::user();
        if (!$user) return response()->json([], 401);

        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead()
    {
        $user = Auth::user();
        if (!$user) return response()->json([], 401);

        Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'All marked as read']);
    }
}
