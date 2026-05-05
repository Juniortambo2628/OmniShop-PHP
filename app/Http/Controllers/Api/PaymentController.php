<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('order')->latest()->get();
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'reference' => 'nullable|string',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'paid_at' => 'nullable|date',
        ]);

        $payment = Payment::create($validated);

        // Update order payment status
        $this->updateOrderPaymentStatus($validated['order_id']);

        if ($payment->status === 'confirmed') {
            NotificationService::notifyPaymentReceived($payment);
        }

        return response()->json($payment, 201);
    }

    public function show(string $id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        return response()->json($payment);
    }

    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        $orderId = $payment->order_id;
        $payment->delete();

        $this->updateOrderPaymentStatus($orderId);

        return response()->json(['message' => 'Payment deleted']);
    }

    private function updateOrderPaymentStatus($orderId)
    {
        $order = Order::where('order_id', $orderId)->first();
        if (!$order) return;

        $totalPaid = Payment::where('order_id', $orderId)->where('status', 'confirmed')->sum('amount');
        
        $status = 'unpaid';
        if ($totalPaid > 0 && $totalPaid < $order->total) {
            $status = 'partial';
        } elseif ($totalPaid >= $order->total && $order->total > 0) {
            $status = 'paid';
        }

        $order->payment_status = $status;
        $order->save();
    }
}
