<?php
/**
 * Admin — Settings Page
 * Route: /admin/settings
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_super_admin();
require_once BASE_PATH . '/includes/admin_layout.php';
require_once BASE_PATH . '/includes/events.php';

$settings = get_all_settings();
$events   = get_events();
$saved    = false;

// ── Handle save ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsToSave = [
        'company_name'          => trim($_POST['company_name']          ?? ''),
        'company_address'       => trim($_POST['company_address']       ?? ''),
        'company_phone'         => trim($_POST['company_phone']         ?? ''),
        'company_whatsapp'      => trim($_POST['company_whatsapp']      ?? ''),
        'company_email'         => trim($_POST['company_email']         ?? ''),
        'company_website'       => trim($_POST['company_website']       ?? ''),
        'company_pin'           => trim($_POST['company_pin']           ?? ''),
        'smtp_host'             => trim($_POST['smtp_host']             ?? ''),
        'smtp_port'             => trim($_POST['smtp_port']             ?? '587'),
        'smtp_user'             => trim($_POST['smtp_user']             ?? ''),
        'smtp_from_email'       => trim($_POST['smtp_from_email']       ?? ''),
        'smtp_from_name'        => trim($_POST['smtp_from_name']        ?? ''),
        'payment_instructions'  => trim($_POST['payment_instructions']  ?? ''),
        'invoice_tc'            => trim($_POST['invoice_tc']            ?? ''),
    ];

    // Only update SMTP password if provided
    if (!empty($_POST['smtp_password'])) {
        $settingsToSave['smtp_password'] = trim($_POST['smtp_password']);
    }

    // Catalog passwords per event
    foreach ($events as $ev) {
        $slug = $ev['slug'];
        $pw = trim($_POST['catalog_password_' . $slug] ?? '');
        if ($pw !== '') {
            $settingsToSave['catalog_password_' . $slug] = $pw;
        }
        $dpw = trim($_POST['catalog_demo_password_' . $slug] ?? '');
        if ($dpw !== '') {
            $settingsToSave['catalog_demo_password_' . $slug] = $dpw;
        }
    }

    foreach ($settingsToSave as $key => $val) {
        set_setting($key, $val);
    }

    set_flash('success', 'Settings saved successfully.');
    redirect('/admin/settings');
}

// Reload after save
$settings = get_all_settings();

admin_header('Settings', 'settings');
?>

<?php admin_flash(); ?>

<form method="POST" action="/admin/settings">

  <!-- Company Details -->
  <div class="card mb-2">
    <div class="card-header">Company Details</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label>Company Name</label>
          <input type="text" name="company_name" class="form-control"
                 value="<?= e($settings['company_name'] ?? 'OmniSpace 3D Events Ltd') ?>">
        </div>
        <div class="form-group">
          <label>Company Website</label>
          <input type="text" name="company_website" class="form-control"
                 value="<?= e($settings['company_website'] ?? 'www.omnispace3d.com') ?>">
        </div>
        <div class="form-group">
          <label>Company Address</label>
          <input type="text" name="company_address" class="form-control"
                 value="<?= e($settings['company_address'] ?? '') ?>"
                 placeholder="e.g. Nairobi, Kenya">
        </div>
        <div class="form-group">
          <label>PIN / VAT Number</label>
          <input type="text" name="company_pin" class="form-control"
                 value="<?= e($settings['company_pin'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" name="company_phone" class="form-control"
                 value="<?= e($settings['company_phone'] ?? '+254 731 001 723') ?>">
        </div>
        <div class="form-group">
          <label>WhatsApp Number</label>
          <input type="text" name="company_whatsapp" class="form-control"
                 value="<?= e($settings['company_whatsapp'] ?? '+254731001723') ?>">
        </div>
        <div class="form-group">
          <label>Company Email (receives BCC of all orders)</label>
          <input type="email" name="company_email" class="form-control"
                 value="<?= e($settings['company_email'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- SMTP / Email -->
  <div class="card mb-2">
    <div class="card-header">Email / SMTP Settings</div>
    <div class="card-body">
      <div class="alert alert-info" style="font-size:12px;">
        OmniShop uses Gmail SMTP. Use an App Password (not your Gmail password) — enable 2FA first,
        then generate at <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>.
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label>SMTP Host</label>
          <input type="text" name="smtp_host" class="form-control"
                 value="<?= e($settings['smtp_host'] ?? 'smtp.gmail.com') ?>">
        </div>
        <div class="form-group">
          <label>SMTP Port</label>
          <input type="number" name="smtp_port" class="form-control"
                 value="<?= e($settings['smtp_port'] ?? '587') ?>">
        </div>
        <div class="form-group">
          <label>Gmail Address (SMTP Username)</label>
          <input type="email" name="smtp_user" class="form-control"
                 value="<?= e($settings['smtp_user'] ?? '') ?>"
                 placeholder="yourname@gmail.com">
        </div>
        <div class="form-group">
          <label>Gmail App Password</label>
          <input type="password" name="smtp_password" class="form-control"
                 placeholder="Leave blank to keep current password">
          <small style="color:#6E6E6E;">Leave blank to keep existing password.</small>
        </div>
        <div class="form-group">
          <label>From Email</label>
          <input type="email" name="smtp_from_email" class="form-control"
                 value="<?= e($settings['smtp_from_email'] ?? $settings['smtp_user'] ?? '') ?>"
                 placeholder="orders@omnispace3d.com">
        </div>
        <div class="form-group">
          <label>From Name</label>
          <input type="text" name="smtp_from_name" class="form-control"
                 value="<?= e($settings['smtp_from_name'] ?? 'OmniSpace 3D Events') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Catalog Passwords per Event -->
  <div class="card mb-2">
    <div class="card-header">Catalog Access Passwords</div>
    <div class="card-body">
      <p style="font-size:13px;color:#6E6E6E;margin-bottom:16px;">
        Set the password clients use to access each event catalog. The demo password is for your team / testing.
        Leave blank to keep the existing password.
      </p>
      <?php foreach ($events as $ev): ?>
      <div style="border:1px solid #D6F0EF;border-radius:6px;padding:14px;margin-bottom:12px;">
        <strong style="color:#0A9696;"><?= e($ev['name']) ?></strong>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
          <div class="form-group">
            <label>Client Password</label>
            <input type="text" name="catalog_password_<?= e($ev['slug']) ?>" class="form-control"
                   placeholder="Leave blank to keep current"
                   value="">
            <small style="color:#6E6E6E;">
              Current: <?= !empty($settings['catalog_password_' . $ev['slug']]) ? '●●●●●●●●' : '(using event default: ' . e($ev['catalog_password_default'] ?? 'not set') . ')' ?>
            </small>
          </div>
          <div class="form-group">
            <label>Demo / Staff Password</label>
            <input type="text" name="catalog_demo_password_<?= e($ev['slug']) ?>" class="form-control"
                   placeholder="Leave blank to keep current"
                   value="">
            <small style="color:#6E6E6E;">
              Current: <?= !empty($settings['catalog_demo_password_' . $ev['slug']]) ? '●●●●●●●●' : '(not set)' ?>
            </small>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Invoice Content -->
  <div class="card mb-2">
    <div class="card-header">Invoice Content</div>
    <div class="card-body">
      <div class="form-group">
        <label>Payment Instructions (shown on PDF invoice and in order confirmation email)</label>
        <textarea name="payment_instructions" class="form-control" rows="4"><?= e($settings['payment_instructions'] ?? "Payment is due within 7 days of invoice date.\nBank transfer details will be provided upon request.\nPlease quote your Order Reference when making payment.\nFor enquiries: WhatsApp +254731001723") ?></textarea>
      </div>
      <div class="form-group">
        <label>Terms & Conditions (shown on PDF invoice)</label>
        <textarea name="invoice_tc" class="form-control" rows="5"><?= e($settings['invoice_tc'] ?? "1. All prices are in USD and subject to applicable taxes.\n2. Orders are confirmed upon receipt of payment.\n3. Cancellation must be requested in writing at least 72 hours before the event.\n4. OmniSpace 3D Events Ltd reserves the right to substitute products of equal or greater value.\n5. Delivery and set-up are included unless otherwise stated.") ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>

</form>

<?php admin_footer(); ?>
