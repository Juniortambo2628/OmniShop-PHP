<?php
/**
 * Admin Login Page
 */
if (!defined('BASE_PATH')) { exit; }

// Already logged in?
if (is_admin_logged_in()) {
    redirect('/admin');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        if (admin_login($email, $password)) {
            redirect('/admin');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } else {
        $error = 'Please enter your email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login — OmniShop</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/static/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <img src="<?= SITE_URL ?>/static/images/logo_white_background.jpg"
         alt="OmniSpace 3D" class="logo" onerror="this.style.display='none'">
    <h2>Admin Panel</h2>
    <p class="event-name">OmniShop — OmniSpace 3D Events Ltd</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control"
               value="<?= e($_POST['email'] ?? '') ?>"
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
