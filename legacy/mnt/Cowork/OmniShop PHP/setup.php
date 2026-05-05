<?php
/**
 * OmniShop Database Setup Script
 * ─────────────────────────────────────────────────────────────────────────────
 * ALEX: Run this ONCE after uploading to Bluehost.
 * Visit: https://shop.omnispace3d.com/setup.php
 * It will create all database tables and the default admin account.
 *
 * After running successfully, you can delete or rename this file for security.
 */

require_once __DIR__ . '/config.php';

$output = [];
$errors = [];

function log_ok($msg) { global $output; $output[] = "✅ $msg"; }
function log_err($msg) { global $errors; $errors[] = "❌ $msg"; }

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    log_ok("Connected to database: " . DB_NAME);
} catch (PDOException $e) {
    die("<h2 style='color:red'>❌ Cannot connect to database</h2><p>" . htmlspecialchars($e->getMessage()) . "</p><p>Please check your config.php settings.</p>");
}

// ── Create tables ─────────────────────────────────────────────────────────────

$tables = [

'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'order_sequence' => "CREATE TABLE IF NOT EXISTS `order_sequence` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `stub` TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL UNIQUE,
  `event_slug` VARCHAR(50) NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `contact_name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(200) NOT NULL,
  `phone` VARCHAR(50),
  `booth_number` VARCHAR(50) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `vat` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
  `notes` TEXT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME,
  INDEX `idx_event_slug` (`event_slug`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'order_items' => "CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `product_code` VARCHAR(30) NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `color_name` VARCHAR(100),
  `category` VARCHAR(50),
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `dimensions` VARCHAR(200),
  INDEX `idx_order_id` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'products' => "CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prod_id` VARCHAR(50) NOT NULL UNIQUE,
  `code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `category_id` VARCHAR(50) NOT NULL,
  `colors_json` TEXT,
  `dimensions` VARCHAR(200),
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price_display` VARCHAR(30),
  `description` TEXT,
  `unit` VARCHAR(50) DEFAULT 'per event',
  `is_poa` TINYINT NOT NULL DEFAULT 0,
  `is_override` TINYINT NOT NULL DEFAULT 0,
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_by` VARCHAR(100),
  `created_at` DATETIME,
  `updated_at` DATETIME,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'admin_users' => "CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'order_manager',
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'stock_limits' => "CREATE TABLE IF NOT EXISTS `stock_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_code` VARCHAR(30) NOT NULL UNIQUE,
  `product_name` VARCHAR(200),
  `category_id` VARCHAR(50),
  `stock_limit` INT,
  `updated_at` DATETIME,
  INDEX `idx_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        log_ok("Table '$name' ready");
    } catch (PDOException $e) {
        log_err("Table '$name': " . $e->getMessage());
    }
}

// ── Default admin user ────────────────────────────────────────────────────────
$defaultUser     = 'admin@omnispace3d.com';
$defaultPassword = 'OmniAdmin2026!';
$defaultDisplay  = 'Susan Mboya';
$defaultRole     = 'super_admin';

try {
    $existing = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
    $existing->execute([$defaultUser]);
    if (!$existing->fetch()) {
        $hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO admin_users (username, display_name, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())")
            ->execute([$defaultUser, $defaultDisplay, $hash, $defaultRole]);
        log_ok("Default admin user created: $defaultUser / $defaultPassword");
    } else {
        log_ok("Admin user already exists — not overwritten");
    }
} catch (PDOException $e) {
    log_err("Admin user: " . $e->getMessage());
}

// ── Default settings ──────────────────────────────────────────────────────────
//
// SMTP credentials — using orders@omnispace3d.com with a Gmail App Password.
// To generate a Gmail App Password:
//   1. Log in to orders@omnispace3d.com in a browser
//   2. Go to: myaccount.google.com/security
//   3. Under "How you sign in to Google" → turn on 2-Step Verification if not already on
//   4. Go to: myaccount.google.com/apppasswords
//   5. Select "Mail" and "Windows Computer", click Generate
//   6. Copy the 16-character password (shown once) and paste below for smtp_password
//
$defaults = [
    'company_name'    => 'OmniSpace 3D Events Ltd',
    'company_address' => 'P.O. Box 00200, Nairobi, Kenya',
    'company_phone'   => '+254 731 001 723 | +254 769 361 804',
    'company_whatsapp'=> '+254731001723',
    'company_email'   => 'info@omnispace3d.com',
    'company_website' => 'www.omnispace3d.com',
    'company_pin'     => 'P051469673L',
    'invoice_terms'   => "1. Location and event date as specified in this quotation/invoice.\n2. Set-up/set-down timeline — Set up to be complete 24 hours before handover; set down will begin within 12 hours of end of event.\n3. Quotation subject to agreed layout at a specific location. Subject to change if layout changes.\n4. Quotation may be part of a package offering inclusive of furniture.\n5. Client to book venue in advance to allow adequate time for set-up and set-down.\n6. The above quotation covers rental of our equipment for the specified period only.\n7. Following handover the client is responsible for safety and security of the products.",
    'invoice_payment_note' => "Payment: Bank transfer to OmniSpace 3D Events Ltd. Acc No: 1234567890, Bank: Equity Bank, Branch: Westlands.\nWhatsApp: +254731001723",
    // ── Email / SMTP ──────────────────────────────────────────────────
    'smtp_host'           => 'smtp.gmail.com',
    'smtp_port'           => '587',
    'smtp_user'           => 'orders@omnispace3d.com',
    'smtp_password'       => '',           // ← PASTE YOUR GMAIL APP PASSWORD HERE (16 chars, no spaces)
    'smtp_from_email'     => 'orders@omnispace3d.com',
    'smtp_from_name'      => 'OmniSpace 3D Events — Orders',
    'notifications_to'    => 'orders@omnispace3d.com',  // admin notification recipient
];
foreach ($defaults as $key => $val) {
    try {
        $pdo->prepare("INSERT IGNORE INTO settings (`key`, value) VALUES (?, ?)")
            ->execute([$key, $val]);
    } catch (PDOException $e) { /* ignore */ }
}
log_ok("Default settings inserted — including email configuration");
log_ok("⚠️  IMPORTANT: Go to Admin → Settings to paste your Gmail App Password (smtp_password).");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>OmniShop Setup</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 700px; margin: 50px auto; padding: 20px; }
  h1   { color: #0A9696; }
  .ok  { color: #2a7a2a; }
  .err { color: #cc0000; }
  .box { background: #f4fafa; border: 1px solid #0A9696; border-radius: 8px; padding: 20px; margin-top: 20px; }
  .cred { background: #fffbe6; border: 1px solid #e6c200; border-radius: 6px; padding: 15px; margin-top: 15px; }
  a.btn { display: inline-block; background: #0A9696; color: #fff; padding: 12px 28px; border-radius: 6px; text-decoration: none; margin-top: 20px; }
</style>
</head>
<body>
<h1>OmniShop — Database Setup</h1>

<?php if ($errors): ?>
  <div style="background:#fff0f0;border:1px solid red;padding:15px;border-radius:6px;">
    <strong>There were errors:</strong>
    <?php foreach ($errors as $e) echo "<p class='err'>$e</p>"; ?>
  </div>
<?php endif; ?>

<div class="box">
  <?php foreach ($output as $line) echo "<p class='ok'>$line</p>"; ?>
</div>

<?php if (!$errors): ?>
<div class="cred">
  <strong>Your default admin login:</strong><br><br>
  URL: <strong><?= SITE_URL ?>/admin/login</strong><br>
  Email: <strong>admin@omnispace3d.com</strong><br>
  Password: <strong>OmniAdmin2026!</strong><br><br>
  ⚠️ Please change this password after your first login (Admin → Users).
</div>
<a class="btn" href="<?= SITE_URL ?>/admin/login">Go to Admin Login →</a>
<p style="color:#888;margin-top:30px;">
  ✅ Setup complete. For security, you may delete or rename setup.php on your server after this.
</p>
<?php endif; ?>
</body>
</html>
