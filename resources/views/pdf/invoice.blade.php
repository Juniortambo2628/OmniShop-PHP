<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { font-size: 28px; font-weight: bold; color: #0d2e2e; }
        .company-info { text-align: right; font-size: 12px; color: #666; }
        .invoice-details { margin-bottom: 30px; }
        .invoice-details h2 { margin: 0; color: #0d2e2e; }
        .bill-to { margin-bottom: 30px; }
        .bill-to h4 { margin: 0 0 5px 0; font-size: 12px; color: #999; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f9f9f9; text-align: left; padding: 10px; border-bottom: 2px solid #eee; font-size: 12px; text-transform: uppercase; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        .totals { float: right; width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 5px 0; }
        .grand-total { border-top: 2px solid #0d2e2e; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 18px; color: #0d2e2e; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="logo">OMNISHOP</div>
            <div class="company-info">
                <strong>Omnispace 3D</strong><br>
                Nairobi, Kenya<br>
                support@omnispace3d.com
            </div>
        </div>

        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p>
                <strong>Order ID:</strong> {{ $order->order_id }}<br>
                <strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}<br>
                <strong>Status:</strong> {{ $order->status }}
            </p>
        </div>

        <div class="bill-to">
            <h4>Bill To:</h4>
            <strong>{{ $order->company_name ?: $order->contact_name }}</strong><br>
            {{ $order->contact_name }}<br>
            {{ $order->email }}<br>
            {{ $order->phone }}<br>
            Booth: {{ $order->booth_number }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Color</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->color_name ?: 'N/A' }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="overflow: hidden;">
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount_amount > 0)
                <div class="total-row" style="color: #e53e3e;">
                    <span>Discount:</span>
                    <span>-${{ number_format($order->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($order->delivery_cost > 0)
                <div class="total-row">
                    <span>Delivery:</span>
                    <span>${{ number_format($order->delivery_cost, 2) }}</span>
                </div>
                @endif
                <div class="total-row">
                    <span>VAT (16%):</span>
                    <span>${{ number_format($order->vat, 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            Thank you for your business! If you have any questions, please contact support@omnispace3d.com
        </div>
    </div>
</body>
</html>
