<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — OmniShop</title>
    <link rel="stylesheet" href="{{ asset('static/css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <img src="{{ asset('static/images/logo_white_background.jpg') }}"
             alt="OmniSpace 3D" class="logo" onerror="this.style.display='none'">
        <h2>Admin Panel</h2>
        <p class="event-name">OmniShop — OmniSpace 3D Events Ltd</p>

        @if(isset($errors) && count($errors) > 0)
            <div class="alert alert-danger">
                @foreach($errors as $error)
                    {{ $error }} @break
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}"
                       placeholder="admin@omnispace3d.com" autofocus required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Sign In →
            </button>
        </form>

        <div style="margin-top:30px;padding-top:20px;border-top:1px solid #eee;font-size:11px;color:#aaa;">
            OmniShop Admin · OmniSpace 3D Events Ltd
        </div>
    </div>
</div>
</body>
</html>
