<?php
/**
 * Catalog Password Gate
 */
$eventSlug = $_GET['event'] ?? 'solarandstorage';
$event     = get_event($eventSlug);
if (!$event) { http_response_code(404); echo "Event not found"; exit; }

// Already authenticated?
if (is_catalog_authenticated($eventSlug)) {
    redirect('/' . $eventSlug);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered  = trim($_POST['password'] ?? '');
    $correct  = get_catalog_password($eventSlug);
    $demo     = get_catalog_demo_password($eventSlug);
    $valid    = array_filter([$correct, $demo]);

    if ($entered && in_array($entered, $valid)) {
        set_catalog_auth_cookie($eventSlug);
        redirect('/' . $eventSlug);
    } else {
        $error = 'Incorrect password. Please try again.';
    }
}

$logoPath = SITE_URL . '/static/images/logo_white_background.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($event['name']) ?> — Exhibitor Services Catalogue</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/static/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <img src="<?= SITE_URL ?>/static/images/logo_white_background.jpg" alt="OmniSpace 3D" class="logo"
         onerror="this.style.display='none'">
    <h2>Exhibitor Services Catalogue</h2>
    <p class="event-name"><?= e($event['name']) ?></p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/<?= e($eventSlug) ?>/login">
      <div class="form-group">
        <label>Access Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Enter your access code" autofocus required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Enter Catalogue →</button>
    </form>

    <p style="margin-top:24px;font-size:12px;color:#6E6E6E;">
      Need access? Contact us at
      <a href="mailto:<?= e($event['contact_email']) ?>"><?= e($event['contact_email']) ?></a>
    </p>

    <div style="margin-top:30px;padding-top:20px;border-top:1px solid #eee;font-size:11px;color:#aaa;">
      Powered by OmniSpace 3D Events Ltd · www.omnispace3d.com
    </div>
  </div>
</div>
</body>
</html>
