<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — OmniShop Admin</title>
    <link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-logo-box {
            background: #fff;
            color: #0A9696;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            border-radius: 8px;
            margin: 0 auto;
        }
        .admin-sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
        }
    </style>
    @stack('styles')
</head>
<body class="admin-body">
<div class="admin-shell">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo">
            <div class="admin-logo-box">OS</div>
            <div style="font-size:10px;color:#D6F0EF;margin-top:4px;letter-spacing:1px;">ADMIN PANEL</div>
        </div>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Dashboard</a>
            <a href="{{ route('admin.orders') }}" class="admin-nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">📦 Orders</a>
            <a href="{{ route('admin.products') }}" class="admin-nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">🛋️ Products</a>
            <a href="{{ route('admin.stock') }}" class="admin-nav-item {{ request()->routeIs('admin.stock*') ? 'active' : '' }}">📋 Stock Limits</a>
            <a href="{{ route('admin.settings') }}" class="admin-nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">⚙️ Settings</a>
        </nav>
        <div class="admin-sidebar-footer">
            <div style="font-size:11px;color:#D6F0EF;margin-bottom:6px;">
                Logged in as: <strong>{{ Auth::user()->name }}</strong><br>
                <span style="color:#19AFAC;">Administrator</span>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm"
                   style="color:#fff;border-color:#fff;font-size:11px;width:100%;text-align:left;">Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main content area -->
    <main class="admin-main">
        <div class="admin-topbar">
            <h1 class="admin-page-title">@yield('title')</h1>
        </div>
        <div class="admin-content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
