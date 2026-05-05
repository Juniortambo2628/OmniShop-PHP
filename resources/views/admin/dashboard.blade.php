@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<!-- ── STAT CARDS ─────────────────────────────────────────────────────── -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-num">{{ number_format($stats['total_orders']) }}</div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">${{ number_format($stats['total_revenue'], 2) }}</div>
        <div class="stat-label">Total Revenue (USD)</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">{{ number_format($stats['pending_orders']) }}</div>
        <div class="stat-label">Pending Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">{{ number_format($stats['paid_orders']) }}</div>
        <div class="stat-label">Paid / Confirmed</div>
    </div>
</div>

<!-- ── FILTER BAR ─────────────────────────────────────────────────────── -->
<div class="card mb-2">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="{{ route('admin.dashboard') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">EVENT</label>
                <select name="event" class="form-control" style="min-width:180px;" onchange="this.form.submit()">
                    <option value="">All Events</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev['slug'] }}" {{ $filterEvent === $ev['slug'] ? 'selected' : '' }}>
                            {{ $ev['short_name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">STATUS</label>
                <select name="status" class="form-control" style="min-width:140px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $filterStatus === $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:200px;">
                <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">SEARCH</label>
                <input type="text" name="q" class="form-control"
                       placeholder="Order ID, company, contact, email…"
                       value="{{ $filterSearch }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline" style="margin-left:4px;">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- ── ORDERS TABLE ───────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span>Orders ({{ count($orders) }})</span>
    </div>
    <div class="card-body" style="padding:0;">
        @if(count($orders) === 0)
            <div style="padding:30px;text-align:center;color:#6E6E6E;">
                No orders found.
                @if($filterSearch || $filterStatus || $filterEvent)
                    <a href="{{ route('admin.dashboard') }}">Clear filters</a>
                @endif
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Event</th>
                            <th>Company</th>
                            <th>Stand</th>
                            <th>Contact</th>
                            <th style="text-align:right;">Total (USD)</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $ord)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $ord->order_id) }}"
                                       style="font-weight:700;color:#0A9696;font-family:monospace;font-size:12px;">
                                        {{ $ord->order_id }}
                                    </a>
                                </td>
                                <td style="font-size:12px;">{{ $ord->event_slug }}</td>
                                <td>{{ $ord->company_name }}</td>
                                <td>{{ $ord->booth_number }}</td>
                                <td style="font-size:12px;">
                                    {{ $ord->contact_name }}<br>
                                    <span style="color:#6E6E6E;">{{ $ord->email }}</span>
                                </td>
                                <td style="text-align:right;font-weight:700;">
                                    ${{ number_format($ord->total, 2) }}
                                </td>
                                <td>
                                    @php
                                        $badgeClass = [
                                            'Pending'   => 'badge-warning',
                                            'Approved'  => 'badge-info',
                                            'Invoiced'  => 'badge-primary',
                                            'Fulfilled' => 'badge-success',
                                            'Cancelled' => 'badge-danger',
                                        ][$ord->status] ?? 'badge-secondary';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucfirst($ord->status) }}
                                    </span>
                                </td>
                                <td style="font-size:11px;color:#6E6E6E;white-space:nowrap;">
                                    {{ $ord->created_at->format('d M Y') }}<br>
                                    {{ $ord->created_at->format('H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $ord->order_id) }}"
                                       class="btn btn-outline btn-sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
