@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<!-- ── Stats bar ────────────────────────────────────────────────────────────── -->
<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    @php
        $totalCatalog = count(config('catalog.products'));
        $totalModified = collect($displayProducts)->where('source', 'modified')->count();
        $totalCustom = collect($displayProducts)->where('source', 'custom')->count();
    @endphp
    <div style="background:#0A9696;color:#fff;border-radius:8px;padding:12px 20px;min-width:120px;">
        <div style="font-size:24px;font-weight:700;line-height:1;">{{ $totalCatalog }}</div>
        <div style="font-size:12px;opacity:.85;margin-top:2px;">Standard products</div>
    </div>
    <div style="background:{{ $totalModified ? '#19AFAC' : '#D6F0EF' }};color:{{ $totalModified ? '#fff' : '#0A9696' }};border-radius:8px;padding:12px 20px;min-width:120px;">
        <div style="font-size:24px;font-weight:700;line-height:1;">{{ $totalModified }}</div>
        <div style="font-size:12px;opacity:.85;margin-top:2px;">Modified</div>
    </div>
    <div style="background:{{ $totalCustom ? '#19AFAC' : '#D6F0EF' }};color:{{ $totalCustom ? '#fff' : '#0A9696' }};border-radius:8px;padding:12px 20px;min-width:120px;">
        <div style="font-size:24px;font-weight:700;line-height:1;">{{ $totalCustom }}</div>
        <div style="font-size:12px;opacity:.85;margin-top:2px;">Custom additions</div>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            + Add New Product
        </a>
    </div>
</div>

<!-- ── Search + category tabs ───────────────────────────────────────────────── -->
<div style="background:#fff;border:1px solid #E0E0E0;border-radius:8px;padding:14px 16px;margin-bottom:16px;">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:8px;align-items:center;flex:1;min-width:220px;">
            @if($filterCat)
                <input type="hidden" name="cat" value="{{ $filterCat }}">
            @endif
            <input type="text" name="q" class="form-control" style="max-width:280px;"
                   placeholder="Search by code or name…" value="{{ $filterQ }}">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            @if($filterQ || $filterCat)
                <a href="{{ route('admin.products') }}" class="btn btn-outline btn-sm">Clear filters</a>
            @endif
        </form>
    </div>
    <!-- Category tabs -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
        <a href="{{ route('admin.products', ['q' => $filterQ]) }}"
           style="padding:5px 12px;border-radius:20px;font-size:12px;text-decoration:none;
                  background:{{ !$filterCat ? '#0A9696' : '#F5F5F5' }};
                  color:{{ !$filterCat ? '#fff' : '#333' }};border:1px solid {{ !$filterCat ? '#0A9696' : '#DDD' }};">
            All ({{ $totalCatalog + $totalCustom }})
        </a>
        @foreach($categoryLabels as $cid => $label)
            <a href="{{ route('admin.products', ['cat' => $cid, 'q' => $filterQ]) }}"
               style="padding:5px 12px;border-radius:20px;font-size:12px;text-decoration:none;
                      background:{{ $filterCat === $cid ? '#0A9696' : '#F5F5F5' }};
                      color:{{ $filterCat === $cid ? '#fff' : '#333' }};border:1px solid {{ $filterCat === $cid ? '#0A9696' : '#DDD' }};">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<!-- ── Products table ────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        @if($filterCat)
            {{ $categoryLabels[$filterCat] ?? ucfirst($filterCat) }}
        @else
            All Products
        @endif
        — {{ count($displayProducts) }} shown
        <span style="font-size:11px;font-weight:400;margin-left:12px;color:#D6F0EF;">
            🟢 Standard &nbsp;|&nbsp; 🟡 Modified &nbsp;|&nbsp; 🔵 Custom addition
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        @if(empty($displayProducts))
            <div style="padding:30px;text-align:center;color:#6E6E6E;">
                No products found. @if($filterQ || $filterCat)
                    <a href="{{ route('admin.products') }}">Clear filters</a>
                @endif
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:24px;"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th style="text-align:right;">Price</th>
                            <th>Colours</th>
                            <th style="text-align:center;">Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($displayProducts as $p)
                            @php
                                $srcColour = match($p['source']) {
                                    'modified' => '#FFA500',
                                    'custom'   => '#19AFAC',
                                    default    => '#28A745',
                                };
                                $srcTip = match($p['source']) {
                                    'modified' => 'Standard product — you have modified it',
                                    'custom'   => 'Your custom addition (not in standard catalog)',
                                    default    => 'Standard catalog product',
                                };
                                $catLabel = $categoryLabels[$p['category_id']] ?? ucfirst($p['category_id'] ?: '—');
                            @endphp
                            <tr style="{{ !$p['is_active'] ? 'opacity:0.5;' : '' }}">
                                <td style="text-align:center;" title="{{ $srcTip }}">
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                                                 background:{{ $srcColour }};"></span>
                                </td>
                                <td style="font-family:monospace;font-size:12px;font-weight:600;">
                                    {{ $p['code'] }}
                                </td>
                                <td>
                                    <strong>{{ $p['name'] }}</strong>
                                    @if($p['source'] === 'modified')
                                        <span style="font-size:10px;color:#FFA500;margin-left:6px;">● modified</span>
                                    @elseif($p['source'] === 'custom')
                                        <span style="font-size:10px;color:#19AFAC;margin-left:6px;">● custom</span>
                                    @endif
                                    @if($p['dimensions'])
                                        <div style="font-size:11px;color:#6E6E6E;margin-top:2px;">{{ $p['dimensions'] }}</div>
                                    @endif
                                </td>
                                <td style="font-size:12px;color:#6E6E6E;">{{ $catLabel }}</td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if($p['is_poa'])
                                        <em style="color:#6E6E6E;font-size:12px;">POA</em>
                                    @else
                                        <strong>${{ number_format($p['price'], 2) }}</strong>
                                    @endif
                                </td>
                                <td style="font-size:11px;max-width:160px;">
                                    @php
                                        $colors = $p['colors'];
                                        if (empty($colors)) {
                                            echo '<span style="color:#bbb;">—</span>';
                                        } else {
                                            $names = array_column($colors, 'name');
                                            echo e(implode(', ', $names));
                                        }
                                    @endphp
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $p['is_active'] ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $p['is_active'] ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">
                                    @if($p['source'] === 'builtin')
                                        <a href="{{ route('admin.products.create', ['from_catalog' => $p['catalog_id']]) }}"
                                           class="btn btn-outline btn-sm">Edit</a>
                                    @else
                                        <a href="{{ route('admin.products.edit', $p['db_id']) }}"
                                           class="btn btn-outline btn-sm">Edit</a>
                                    @endif
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
