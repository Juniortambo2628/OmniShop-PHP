<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($event = $request->input('event')) {
            $query->where('event_slug', $event);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('order_id', 'LIKE', "%{$q}%")
                   ->orWhere('company_name', 'LIKE', "%{$q}%")
                   ->orWhere('contact_name', 'LIKE', "%{$q}%")
                   ->orWhere('email', 'LIKE', "%{$q}%");
            });
        }

        $perPage = $request->input('per_page', 20);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with('items')->where('order_id', $id)->firstOrFail();
        $event = config("events.{$order->event_slug}");

        return response()->json([
            'order' => $order,
            'event' => $event,
            'statuses' => ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::where('order_id', $id)->firstOrFail();
        $order->update(['status' => $request->status]);

        $this->triggerStatusEmail($order, $request->status);

        return response()->json(['message' => 'Status updated']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Order::whereIn('order_id', $ids)->delete();
        return response()->json(['message' => count($ids) . ' orders deleted']);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        $orders = Order::whereIn('order_id', $ids)->get();
        
        Order::whereIn('order_id', $ids)->update(['status' => $status]);

        foreach ($orders as $order) {
            $this->triggerStatusEmail($order, $status);
        }

        return response()->json(['message' => count($ids) . ' orders updated to ' . $status]);
    }

    private function triggerStatusEmail($order, $status)
    {
        try {
            if ($status === 'Invoiced') {
                Mail::to($order->email)->send(new OrderConfirmationMail($order, 'invoice'));
            } elseif ($status === 'Fulfilled') {
                // Assuming you have a generic template, falling back to 'confirmation' type or a dedicated 'fulfilled' if created
                // Mail::to($order->email)->send(new OrderConfirmationMail($order, 'fulfilled'));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send status update email: " . $e->getMessage());
        }
    }

    /**
     * Send an email to the client for a given order.
     */
    public function sendEmail(Request $request, $id)
    {
        $order = Order::where('order_id', $id)->firstOrFail();
        $templateType = $request->input('type', 'confirmation');

        try {
            Mail::to($order->email)->send(new OrderConfirmationMail($order, $templateType));

            return response()->json([
                'message' => "Email sent to {$order->email}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a test email to the current admin user.
     */
    public function sendTestEmail(Request $request)
    {
        $email = $request->user()->email;
        $templateType = $request->input('type', 'confirmation');

        // Create a fake order for preview
        $fakeOrder = new Order([
            'order_id' => 'OMN-TEST-001',
            'event_slug' => array_key_first(config('events', ['demo' => []])),
            'company_name' => 'Test Company',
            'contact_name' => 'John Doe',
            'email' => $email,
            'phone' => '+254 700 000 000',
            'booth_number' => 'A1',
            'subtotal' => 1000.00,
            'vat' => 160.00,
            'total' => 1160.00,
            'status' => 'Pending',
        ]);

        try {
            Mail::to($email)->send(new OrderConfirmationMail($fakeOrder, $templateType));

            return response()->json([
                'message' => "Test email sent to {$email}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }
}

