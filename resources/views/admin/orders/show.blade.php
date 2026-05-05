@extends('layouts.admin')

@section('title', 'Order: ' . $order->order_id)

@section('content')
<div style="margin-bottom:16px;">
    <a href="{{ route('admin.dashboard', ['event' => $order->event_slug]) }}"
       class="btn btn-outline btn-sm">← Back to Orders</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    <!-- Order info -->
    <div class="card">
        <div class="card-header">Order Details</div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <tr><td style="color:#6E6E6E;padding:4px 0;width:40%;">Order Reference</td>
                    <td><strong style="font-family:monospace;">{{ $order->order_id }}</strong></td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Event</td>
                    <td>{{ $event['name'] ?? $order->event_slug }}</td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Status</td>
                    <td>
                        @php
                            $badgeClass = [
                                'Pending'   => 'badge-warning',
                                'Approved'  => 'badge-info',
                                'Invoiced'  => 'badge-primary',
                                'Fulfilled' => 'badge-success',
                                'Cancelled' => 'badge-danger',
                            ][$order->status] ?? 'badge-secondary';
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Order Date</td>
                    <td>{{ $order->created_at->format('d M Y H:i') }}</td></tr>
                @if($order->notes)
                <tr><td style="color:#6E6E6E;padding:4px 0;vertical-align:top;">Notes</td>
                    <td style="font-style:italic;">{{ $order->notes }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Customer info -->
    <div class="card">
        <div class="card-header">Customer Details</div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <tr><td style="color:#6E6E6E;padding:4px 0;width:40%;">Company</td>
                    <td><strong>{{ $order->company_name }}</strong></td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Contact</td>
                    <td>{{ $order->contact_name }}</td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Stand / Booth</td>
                    <td><strong>{{ $order->booth_number }}</strong></td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Email</td>
                    <td><a href="mailto:{{ $order->email }}">{{ $order->email }}</a></td></tr>
                <tr><td style="color:#6E6E6E;padding:4px 0;">Phone</td>
                    <td>
                        @if($order->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->phone) }}">{{ $order->phone }}</a>
                        @else
                            —
                        @endif
                    </td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Items table -->
<div class="card mb-2">
    <div class="card-header">Items Ordered</div>
    <div class="card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Colour</th>
                    <th style="text-align:right;">Unit Price</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $i => $item)
                <tr>
                    <td style="color:#6E6E6E;">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_code)
                            <span style="font-size:10px;color:#6E6E6E;margin-left:4px;">({{ $item->product_code }})</span>
                        @endif
                    </td>
                    <td>{{ $item->color_name ?: '—' }}</td>
                    <td style="text-align:right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align:center;">{{ (int)$item->quantity }}</td>
                    <td style="text-align:right;font-weight:600;">${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;padding:8px;color:#6E6E6E;">Subtotal</td>
                    <td style="text-align:right;padding:8px;">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align:right;padding:4px 8px;color:#6E6E6E;">VAT ({{ config('app.vat_rate', 16) }}%)</td>
                    <td style="text-align:right;padding:4px 8px;">${{ number_format($order->vat, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align:right;padding:10px 8px;font-weight:800;color:#0A9696;font-size:15px;">TOTAL (USD)</td>
                    <td style="text-align:right;padding:10px 8px;font-weight:800;color:#0A9696;font-size:15px;">
                        ${{ number_format($order->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Action panels -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    <!-- Update status -->
    <div class="card">
        <div class="card-header">Update Status</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.orders.show', $order->order_id) }}">
                @csrf
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" class="form-control">
                        @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
            </form>
        </div>
    </div>

    <!-- Invoice Actions -->
    <div class="card">
        <div class="card-header">Invoice Actions</div>
        <div class="card-body">
            <p style="font-size:13px;color:#6E6E6E;margin-bottom:12px;">
                Regenerate and re-send the PDF invoice to <strong>{{ $order->email }}</strong>
            </p>
            <form method="POST" action="{{ route('admin.orders.show', $order->order_id) }}">
                @csrf
                <input type="hidden" name="action" value="resend_invoice">
                <button type="submit" class="btn btn-secondary btn-sm"
                        onclick="return confirm('Re-send invoice to {{ $order->email }}?');">
                    📧 Re-send Invoice
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
