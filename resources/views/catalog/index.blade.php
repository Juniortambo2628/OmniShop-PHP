@extends('catalog.layout')

@section('title', $event['name'])
@section('event_name', $event['name'])

@section('content')
<!-- DEADLINES BANNER -->
@if(!empty($event['deadlines']))
<div class="deadlines-bar">
    <strong>⏰ Order Deadlines:</strong>
    <div class="deadline-items">
        @foreach($event['deadlines'] as $dl)
            <span class="deadline-item"><strong>{{ $dl['category'] }}:</strong> {{ $dl['deadline'] }}</span>
        @endforeach
    </div>
</div>
@endif

<!-- CATALOG LAYOUT -->
<div class="catalog-shell">

    <!-- SIDEBAR -->
    <aside class="cat-sidebar" id="cat-sidebar">
        <h3>Categories</h3>
        @foreach($categories as $cat)
            @php
                $count = count($grouped[$cat['id']] ?? []);
            @endphp
            @if($count > 0)
                <div class="cat-link" data-cat="{{ $cat['id'] }}">
                    <span class="cat-icon">{{ $cat['icon'] }}</span>
                    <span>{{ $cat['name'] }}</span>
                    <span class="cat-count">{{ $count }}</span>
                </div>
            @endif
        @endforeach
    </aside>

    <!-- MAIN CONTENT -->
    <main class="catalog-main" id="catalog-main">
        @foreach($categories as $cat)
            @if(!empty($grouped[$cat['id']]))
                <section class="catalog-section" id="section-{{ $cat['id'] }}">
                    <h2 class="section-header">{{ $cat['icon'] }} {{ $cat['name'] }}</h2>
                    <div class="product-grid">
                        @foreach($grouped[$cat['id']] as $prod)
                            @php
                                $code = $prod['code'];
                                $prodId = $prod['id'];
                                $colors = $prod['colors'] ?? [];
                                $isPoa = !empty($prod['is_poa']);
                                $imgMap = $images[$prod['code']] ?? $images[$prod['id']] ?? [];
                                $defaultImg = $imgMap['default'] ?? (count($imgMap) ? reset($imgMap) : null);
                            @endphp
                            <div class="product-card" data-product-id="{{ $prodId }}"
                                 data-product-code="{{ $code }}"
                                 data-product-name="{{ $prod['name'] }}"
                                 data-price="{{ $isPoa ? 0 : (float)$prod['price'] }}"
                                 data-price-display="{{ $prod['price_display'] }}"
                                 data-category="{{ $cat['id'] }}"
                                 data-category-name="{{ $cat['name'] }}"
                                 data-dimensions="{{ $prod['dimensions'] ?? '' }}"
                                 data-is-poa="{{ $isPoa ? '1' : '0' }}"
                                 data-colors='@json($colors)'>

                                <!-- Product image -->
                                <div class="product-img-wrap" id="img-wrap-{{ $prodId }}">
                                    @if($defaultImg)
                                        <img src="{{ asset('static/images/products/' . $defaultImg) }}"
                                             alt="{{ $prod['name'] }}"
                                             id="prod-img-{{ $prodId }}"
                                             onerror="this.parentElement.innerHTML='<div class=\'no-image-placeholder\'>📦</div>'">
                                    @else
                                        <div class="no-image-placeholder">📦</div>
                                    @endif
                                </div>

                                <div class="product-info">
                                    <div class="product-code">{{ $code }}</div>
                                    <div class="product-name">{{ $prod['name'] }}</div>
                                    @if($prod['dimensions'])
                                        <div class="product-dims">{{ $prod['dimensions'] }}</div>
                                    @endif
                                    <div class="product-price {{ $isPoa ? 'poa' : '' }}">
                                        {!! $isPoa ? 'Price on Request' : $prod['price_display'] !!}
                                        @if(!$isPoa && $prod['unit'] !== 'per event')
                                            <span style="font-size:10px;color:#6E6E6E;font-weight:400;"> / {{ $prod['unit'] }}</span>
                                        @endif
                                    </div>

                                    <!-- Colour switcher -->
                                    @if(count($colors) > 1)
                                        <div class="color-switcher" id="colors-{{ $prodId }}">
                                            @foreach($colors as $i => $col)
                                                <button class="color-btn {{ $i === 0 ? 'active' : '' }}"
                                                        data-color-id="{{ $col['id'] }}"
                                                        data-color-name="{{ $col['name'] }}"
                                                        onclick="selectColor(this, '{{ $prodId }}')">
                                                    {{ $col['name'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @elseif(count($colors) === 1)
                                        <div style="font-size:11px;color:#6E6E6E;margin-bottom:8px;">
                                            Color: <strong>{{ $colors[0]['name'] }}</strong>
                                        </div>
                                    @endif

                                    <!-- Add to order -->
                                    <div class="add-to-order" id="add-{{ $prodId }}">
                                        @if(!$isPoa)
                                            <div class="qty-controls">
                                                <button class="qty-btn" onclick="changeQty('{{ $prodId }}', -1)">−</button>
                                                <input type="number" class="qty-input" id="qty-{{ $prodId }}" value="1" min="1" max="999">
                                                <button class="qty-btn" onclick="changeQty('{{ $prodId }}', 1)">+</button>
                                            </div>
                                            <button class="btn-add-to-order" id="btn-{{ $prodId }}"
                                                    onclick="addToOrder('{{ $prodId }}')">
                                                + Add to Order
                                            </button>
                                        @else
                                            <p style="font-size:11px;color:#6E6E6E;margin-top:8px;">
                                                Contact us for pricing:<br>
                                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', config('app.company_phone', '+254731001723')) }}">
                                                    {{ config('app.company_phone', '+254 731 001 723') }}
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
        <div style="height:80px;"></div><!-- spacer for cart bar -->
    </main>
</div>

<!-- CART BAR -->
<div class="cart-bar hidden" id="cart-bar">
    <div>
        <div class="cart-count" id="cart-count-label">0 items</div>
        <div class="cart-total" id="cart-total-label">$0.00</div>
    </div>
    <button class="btn-checkout" onclick="goToCheckout()">Review & Submit Order →</button>
</div>
@endsection

@push('scripts')
<script>
    var PRODUCTS    = @json(array_values(collect($grouped)->flatten(1)->toArray()));
    var CATEGORIES  = @json($categories);
    var IMAGES      = @json($images);
</script>
<script src="{{ asset('static/js/catalog.js') }}"></script>
@endpush
