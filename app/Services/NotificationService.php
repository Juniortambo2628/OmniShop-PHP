<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a notification to all admins or a specific user
     */
    public static function notify(string $type, string $title, string $message, ?string $link = null, ?int $userId = null)
    {
        // If no specific user is provided, notify all users (admins)
        $users = $userId ? [User::find($userId)] : User::all();

        foreach ($users as $user) {
            if (!$user) continue;
            
            $notif = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]);

            event(new \App\Events\NewNotification($notif));
        }
    }

    public static function notifyNewOrder($order)
    {
        self::notify(
            'order',
            'New Order Received',
            "Order #{$order->order_id} from {$order->company_name}.",
            "/admin/orders/{$order->id}"
        );
    }

    public static function notifyPaymentReceived($payment)
    {
        self::notify(
            'payment',
            'Payment Recorded',
            "Payment of $" . number_format($payment->amount, 2) . " received for order #{$payment->order_id}.",
            "/admin/invoices"
        );
    }

    public static function notifyLowStock($product, $remaining)
    {
        self::notify(
            'stock',
            'Low Stock Alert',
            "{$product->name} has only {$remaining} items left in stock.",
            "/admin/stock"
        );
    }
}
