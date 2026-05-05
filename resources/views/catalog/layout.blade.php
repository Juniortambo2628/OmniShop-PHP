<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Exhibitor Services Catalogue</title>
    <link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
    <div class="topnav-logo">
        <img src="{{ asset('static/images/logo_white_background.jpg') }}" alt="OmniSpace"
             style="height:32px;" onerror="this.style.display='none'">
    </div>
    <div class="topnav-title">@yield('event_name')</div>
    <div class="topnav-actions">
        <a href="{{ route('catalog', $event['slug']) }}/checkout" id="nav-checkout-btn" style="display:none;"
           class="btn btn-outline" style="color:#fff;border-color:#fff;">
            🛒 View Order (<span id="nav-cart-count">0</span>)
        </a>
        <a href="{{ route('catalog.login', $event['slug']) }}"
           onclick="clearAuth();return true;" style="font-size:12px;">Logout</a>
    </div>
</nav>

@yield('content')

<script>
    var SITE_URL    = '{{ url('/') }}';
    var EVENT_SLUG  = '{{ $event['slug'] }}';
    var VAT_RATE    = {{ config('app.vat_rate', 0.16) }};
</script>
@stack('scripts')
</body>
</html>
