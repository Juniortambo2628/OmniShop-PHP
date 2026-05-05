<?php
/**
 * 403 Access Denied Page
 */
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Access Denied — OmniShop</title>
  <link rel="stylesheet" href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/static/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body style="background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="text-align:center;max-width:400px;padding:40px;">
  <div style="font-size:64px;margin-bottom:16px;">🔒</div>
  <h1 style="color:#0A9696;font-family:Montserrat,Arial,sans-serif;margin-bottom:8px;">Access Denied</h1>
  <p style="color:#6E6E6E;margin-bottom:24px;">
    You don't have permission to view this page.
  </p>
  <a href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/admin"
     style="display:inline-block;background:#0A9696;color:#fff;padding:10px 24px;border-radius:5px;
            text-decoration:none;font-weight:700;font-family:Montserrat,Arial,sans-serif;">
    ← Back to Admin
  </a>
  <div style="margin-top:30px;font-size:11px;color:#aaa;">
    OmniSpace 3D Events Ltd · OmniShop
  </div>
</div>
</body>
</html>
