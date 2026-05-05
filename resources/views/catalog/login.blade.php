<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event['name'] }} — Exhibitor Services Catalogue</title>
    <link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <img src="{{ asset('static/images/logo_white_background.jpg') }}" alt="OmniSpace 3D" class="logo"
             onerror="this.style.display='none'">
        <h2>Exhibitor Services Catalogue</h2>
        <p class="event-name">{{ $event['name'] }}</p>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('catalog.login.post', $event['slug']) }}">
            @csrf
            <div class="form-group">
                <label>Access Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your access code" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Enter Catalogue →</button>
        </form>

        <p style="margin-top:24px;font-size:12px;color:#6E6E6E;">
            Need access? Contact us at
            <a href="mailto:{{ $event['contact_email'] }}">{{ $event['contact_email'] }}</a>
        </p>

        <div style="margin-top:30px;padding-top:20px;border-top:1px solid #eee;font-size:11px;color:#aaa;">
            Powered by OmniSpace 3D Events Ltd · www.omnispace3d.com
        </div>
    </div>
</div>
</body>
</html>
