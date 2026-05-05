@extends('catalog.layout')

@section('title', 'Order Confirmed — ' . $order->order_id)
@section('event_name', $event['name'] ?? '')

@section('content')
<div class="confirmation-page">
    <div class="confirmation-icon">✅</div>
    <h1>Order Confirmed!</h1>
    <div class="order-ref">{{ $order->order_id }}</div>

    <p style="color:#6E6E6E;margin-bottom:20px;">
        Thank you, <strong>{{ $order->contact_name }}</strong>!
        Your order has been received and a PDF invoice has been sent to
        <strong>{{ $order->email }}</strong>.
    </p>

    <div class="confirmation-details">
        <div style="margin-bottom:10px;">
            <strong>{{ $event['name'] ?? '' }}</strong><br>
            Stand: <strong>{{ $order->booth_number }}</strong> &nbsp;·&nbsp;
            Company: <strong>{{ $order->company_name }}</strong>
        </div>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <thead>
                <tr style="font-size:11px;color:#6E6E6E;text-transform:uppercase;">
                    <td style="padding:5px 0;">Item</td>
                    <td style="text-align:right;padding:5px 0;">Qty</td>
                    <td style="text-align:right;padding:5px 0;">Total</td>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr style="border-bottom:1px solid #c0e0e0;">
                    <td style="padding:6px 0;">
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->color_name)
                            <span style="color:#6E6E6E;font-size:11px;"> — {{ $item->color_name }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;padding:6px 0;">{{ (int)$item->quantity }}</td>
                    <td style="text-align:right;padding:6px 0;">${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align:right;padding:8px 0;color:#555;">Subtotal</td>
                    <td style="text-align:right;padding:8px 0;">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right;padding:4px 0;color:#555;">VAT ({{ config('app.vat_rate', 16) }}%)</td>
                    <td style="text-align:right;padding:4px 0;">${{ number_format($order->vat, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right;padding:8px 0;font-weight:800;color:#0A9696;font-size:15px;">TOTAL (USD)</td>
                    <td style="text-align:right;padding:8px 0;font-weight:800;color:#0A9696;font-size:15px;">
                        ${{ number_format($order->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p style="font-size:13px;color:#555;margin-bottom:20px;">
        Your invoice has been emailed. Payment instructions are included in the invoice.<br>
        For questions, WhatsApp us:
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('app.company_whatsapp', '+254731001723')) }}"
           style="color:#0A9696;font-weight:600;">
            {{ config('app.company_whatsapp', '+254 731 001 723') }}
        </a>
    </p>

    <a href="{{ route('catalog', $event['slug']) }}" class="btn btn-outline">
        ← Return to Catalogue
    </a>

    <div style="margin-top:30px;font-size:11px;color:#aaa;">
        OmniSpace 3D Events Ltd · www.omnispace3d.com<br>
        Order reference: {{ $order->order_id }} · Placed: {{ $order->created_at->format('d M Y, H:i') }}
    </div>
</div>
@endsection
