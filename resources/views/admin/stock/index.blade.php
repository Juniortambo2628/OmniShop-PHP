@extends('layouts.admin')

@section('title', 'Stock Limits')

@section('content')
<p style="color:#6E6E6E;font-size:13px;margin-bottom:16px;">
    Set maximum order quantities per product per event. Leave at 0 for unlimited.
    Current ordered quantities are shown for reference.
</p>

<!-- Event selector -->
<div style="margin-bottom:16px;">
    @foreach($events as $ev)
        <a href="{{ route('admin.stock', ['event' => $ev['slug']]) }}"
           class="btn btn-{{ $filterEvent === $ev['slug'] ? 'primary' : 'outline' }} btn-sm"
           style="margin-right:6px;">
            {{ $ev['short_name'] }}
        </a>
    @endforeach
</div>

@if($filterEvent)
<div class="card">
    <div class="card-header">
        Stock Limits — {{ $events[$filterEvent]['name'] ?? $filterEvent }}
    </div>
    <div class="card-body" style="padding:0;">
        <form method="POST" action="{{ route('admin.stock') }}">
            @csrf
            <input type="hidden" name="event_slug" value="{{ $filterEvent }}">

            @if(empty($stockData))
                <div style="padding:24px;text-align:center;color:#6E6E6E;">
                    No stock limits set yet. Add a product code below to create a limit.
                </div>
            @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th style="text-align:center;">Max Quantity (0 = unlimited)</th>
                        <th style="text-align:center;">Ordered So Far</th>
                        <th style="text-align:center;">Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockData as $code => $row)
                        @php
                            $pct = $row['limit'] > 0 ? min(100, round($row['used'] / $row['limit'] * 100)) : 0;
                            $pctColor = $pct >= 90 ? '#c00' : ($pct >= 70 ? '#e67e22' : '#0A9696');
                        @endphp
                        <tr>
                            <td style="font-family:monospace;font-size:12px;">{{ $code }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td style="text-align:center;">
                                <input type="number" name="limits[{{ $code }}]" class="form-control"
                                       value="{{ (int)$row['limit'] }}" min="0" max="9999"
                                       style="width:80px;text-align:center;margin:0 auto;">
                            </td>
                            <td style="text-align:center;font-weight:700;color:{{ $pctColor }};">
                                {{ (int)$row['used'] }}
                                @if($row['limit'] > 0)
                                    <span style="font-size:10px;font-weight:400;color:#999;">({{ $pct }}%)</span>
                                @endif
                            </td>
                            <td style="text-align:center;color:{{ $row['limit'] > 0 && $row['available'] === 0 ? '#c00' : '#0A9696' }};">
                                {{ $row['limit'] > 0 ? $row['available'] : '∞' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <!-- Add new limit -->
            <div style="padding:14px;border-top:1px solid #D6F0EF;background:#f9fffe;">
                <strong style="font-size:13px;">Add / Update a Product Limit:</strong>
                <div style="display:flex;gap:10px;margin-top:8px;align-items:center;">
                    <input type="text" name="limits[NEW_CODE]" id="new-code-input"
                           class="form-control" style="font-family:monospace;max-width:140px;text-transform:uppercase;"
                           placeholder="PRODUCT CODE">
                    <input type="number" name="limits_new" id="new-limit-input"
                           class="form-control" style="max-width:100px;" min="1" placeholder="Max qty">
                    <small style="color:#6E6E6E;">Type the product code exactly as it appears in the catalog.</small>
                </div>
            </div>

            <div style="padding:14px;">
                <button type="submit" class="btn btn-primary">Save Stock Limits</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
// Fix "new code" input to write into limits array properly
document.addEventListener('DOMContentLoaded', function() {
    var codeInput  = document.getElementById('new-code-input');
    var limitInput = document.getElementById('new-limit-input');
    if (!codeInput || !limitInput) return;
    codeInput.addEventListener('input', function() {
        var code = this.value.trim().toUpperCase();
        limitInput.name = 'limits[' + code + ']';
    });
});
</script>
@endpush
@endsection
