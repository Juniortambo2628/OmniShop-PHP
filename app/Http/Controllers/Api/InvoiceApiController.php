<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceApiController extends Controller
{
    public function downloadPdf($orderId)
    {
        $order = Order::with('items')->where('order_id', $orderId)->firstOrFail();
        
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order]);
        
        return $pdf->download("invoice-{$order->order_id}.pdf");
    }

    public function streamPdf($orderId)
    {
        $order = Order::with('items')->where('order_id', $orderId)->firstOrFail();
        
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order]);
        
        return $pdf->stream("invoice-{$order->order_id}.pdf");
    }
}
