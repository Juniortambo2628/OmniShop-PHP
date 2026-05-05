<?php
/**
 * Admin Dashboard — Orders list with filters + stats
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_once BASE_PATH . '/includes/admin_layout.php';

// ── Filters from GET ──────────────────────────────────────────────────────────
$filterEvent  = $_GET['event']  ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q']      ?? '';

// ── Load data ─────────────────────────────────────────────────────────────────
$events   = get_events();
$statuses = get_order_statuses();
$stats    = get_order_stats($filterEvent ?: null);
$orders   = get_all_orders(
    $filterEvent  ?: null,
    $filterStatus ?: null,
    $filterSearch ?: null
);

admin_header('Dashboard', 'dashboard');
?>

<?php admin_flash(); ?>

<!-- ── STAT CARDS ─────────────────────────────────────────────────────── -->
<div class="stat-cards">
  <div class="stat-card">
    <div class="stat-value"><?= (int)($stats['total_orders'] ?? 0) ?></div>
    <div class="stat-label">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-value">$<?= number_format((float)($stats['total_revenue'] ?? 0), 0) ?></div>
    <div class="stat-label">Total Revenue (USD)</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= (int)($stats['pending_orders'] ?? 0) ?></div>
    <div class="stat-label">Pending Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= (int)($stats['paid_orders'] ?? 0) ?></div>
    <div class="stat-label">Paid / Confirmed</div>
  </div>
</div>

<!-- ── FILTER BAR ─────────────────────────────────────────────────────── -->
<div class="card mb-2">
  <div class="card-body" style="padding:12px 16px;">
    <form method="GET" action="<?= SITE_URL ?>/admin" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">EVENT</label>
        <select name="event" class="form-control" style="min-width:180px;" onchange="this.form.submit()">
          <option value="">All Events</option>
          <?php foreach ($events as $ev): ?>
          <option value="<?= e($ev['slug']) ?>" <?= $filterEvent === $ev['slug'] ? 'selected' : '' ?>>
            <?= e($ev['short_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">STATUS</label>
        <select name="status" class="form-control" style="min-width:140px;" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <?php foreach ($statuses as $sk => $sv): ?>
          <option value="<?= e($sk) ?>" <?= $filterStatus === $sk ? 'selected' : '' ?>>
            <?= e($sv) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex:1;min-width:200px;">
        <label style="font-size:11px;color:#6E6E6E;display:block;margin-bottom:3px;">SEARCH</label>
        <input type="text" name="q" class="form-control"
               placeholder="Order ID, company, contact, email…"
               value="<?= e($filterSearch) ?>">
      </div>
      <div>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="<?= SITE_URL ?>/admin" class="btn btn-outline" style="margin-left:4px;">Clear</a>
      </div>
      <?php if ($filterEvent): ?>
      <div style="margin-left:auto;display:flex;gap:6px;">
        <a href="<?= SITE_URL ?>/admin/packing/<?= e($filterEvent) ?>" class="btn btn-secondary btn-sm">
          📋 Packing List
        </a>
        <a href="<?= SITE_URL ?>/admin/packing-booth/<?= e($filterEvent) ?>" class="btn btn-secondary btn-sm">
          🏢 By Booth
        </a>
        <a href="<?= SITE_URL ?>/admin/export/<?= e($filterEvent) ?>" class="btn btn-secondary btn-sm">
          ⬇ Export CSV
        </a>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ── ORDERS TABLE ───────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
    <span>Orders (<?= count($orders) ?>)</span>
    <?php if (!$filterEvent): ?>
    <div style="display:flex;gap:6px;">
      <?php foreach ($events as $ev): ?>
      <a href="<?= SITE_URL ?>/admin/export/<?= e($ev['slug']) ?>" class="btn btn-outline btn-sm">
        ⬇ <?= e($ev['short_name']) ?> CSV
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($orders)): ?>
      <div style="padding:30px;text-align:center;color:#6E6E6E;">
        No orders found.
        <?php if ($filterSearch || $filterStatus || $filterEvent): ?>
          <a href="<?= SITE_URL ?>/admin">Clear filters</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Event</th>
            <th>Company</th>
            <th>Stand</th>
            <th>Contact</th>
            <th style="text-align:right;">Total (USD)</th>
            <th>Status</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $ord): ?>
          <tr>
            <td>
              <a href="<?= SITE_URL ?>/admin/order/<?= urlencode($ord['order_id']) ?>"
                 style="font-weight:700;color:#0A9696;font-family:monospace;font-size:12px;">
                <?= e($ord['order_id']) ?>
              </a>
            </td>
            <td style="font-size:12px;"><?= e($ord['event_slug'] ?? '') ?></td>
            <td><?= e($ord['company_name']) ?></td>
            <td><?= e($ord['booth_number']) ?></td>
            <td style="font-size:12px;">
              <?= e($ord['contact_name']) ?><br>
              <span style="color:#6E6E6E;"><?= e($ord['email']) ?></span>
            </td>
            <td style="text-align:right;font-weight:700;">
              $<?= number_format((float)$ord['total'], 2) ?>
            </td>
            <td>
              <span class="badge badge-<?= status_badge_class($ord['status']) ?>">
                <?= e(ucfirst(str_replace('_', ' ', $ord['status']))) ?>
              </span>
            </td>
            <td style="font-size:11px;color:#6E6E6E;white-space:nowrap;">
              <?= e(date('d M Y', strtotime($ord['created_at']))) ?><br>
              <?= e(date('H:i', strtotime($ord['created_at']))) ?>
            </td>
            <td>
              <a href="<?= SITE_URL ?>/admin/order/<?= urlencode($ord['order_id']) ?>"
                 class="btn btn-outline btn-sm">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php admin_footer(); ?>
