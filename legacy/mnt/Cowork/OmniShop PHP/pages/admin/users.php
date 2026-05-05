<?php
/**
 * Admin — User Management
 * Route: /admin/users
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();
require_super_admin();
require_once BASE_PATH . '/includes/admin_layout.php';

$roleLabels = get_role_labels();
$errors     = [];

// ── Handle add user ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $name     = trim($_POST['name']      ?? '');
    $password = trim($_POST['password']  ?? '');
    $role     = trim($_POST['role']      ?? 'viewer');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($name) < 2)     $errors[] = 'Name must be at least 2 characters.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!isset($roleLabels[$role])) $errors[] = 'Invalid role selected.';

    if (empty($errors)) {
        try {
            create_admin_user($email, $name, $password, $role);
            set_flash('success', "User $email created successfully.");
            redirect('/admin/users');
        } catch (\Exception $e) {
            $errors[] = 'Could not create user: ' . $e->getMessage();
        }
    }
}

// ── Handle change password ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $uid      = (int)($_POST['user_id']      ?? 0);
    $password = trim($_POST['new_password']  ?? '');

    if (strlen($password) < 6) {
        set_flash('danger', 'Password must be at least 6 characters.');
    } else {
        update_admin_user_password($uid, $password);
        set_flash('success', 'Password updated.');
    }
    redirect('/admin/users');
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $uid = (int)($_POST['user_id'] ?? 0);
    // Don't allow deleting yourself
    $me = get_admin_user();
    if ($uid === (int)($me['id'] ?? 0)) {
        set_flash('danger', 'You cannot delete your own account.');
    } else {
        delete_admin_user($uid);
        set_flash('success', 'User deleted.');
    }
    redirect('/admin/users');
}

$users = get_all_admin_users();

admin_header('User Management', 'users');
?>

<?php admin_flash(); ?>
<?php if ($errors): ?>
  <div class="alert alert-danger" style="margin-bottom:16px;">
    <?php foreach ($errors as $err): ?>
      <div><?= e($err) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Add User -->
<div class="card mb-2">
  <div class="card-header">Add Admin User</div>
  <div class="card-body">
    <form method="POST" action="/admin/users" style="display:grid;grid-template-columns:1fr 1fr 1fr 140px 140px;gap:12px;align-items:flex-end;">
      <input type="hidden" name="action" value="add_user">
      <div class="form-group" style="margin:0;">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" required
               value="<?= e($_POST['name'] ?? '') ?>" placeholder="Jane Smith">
      </div>
      <div class="form-group" style="margin:0;">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" required
               value="<?= e($_POST['email'] ?? '') ?>" placeholder="jane@omnispace3d.com">
      </div>
      <div class="form-group" style="margin:0;">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required
               placeholder="Min. 6 characters">
      </div>
      <div class="form-group" style="margin:0;">
        <label>Role</label>
        <select name="role" class="form-control">
          <?php foreach ($roleLabels as $rk => $rv): ?>
          <option value="<?= e($rk) ?>"><?= e($rv) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button type="submit" class="btn btn-primary btn-sm">Add User</button>
      </div>
    </form>
    <div style="margin-top:12px;font-size:12px;color:#6E6E6E;">
      <strong>Roles:</strong>
      Super Admin — full access including settings and users |
      Admin — orders, products, images, stock |
      Viewer — read-only access to orders and packing lists
    </div>
  </div>
</div>

<!-- Users table -->
<div class="card">
  <div class="card-header">Admin Users (<?= count($users) ?>)</div>
  <div class="card-body" style="padding:0;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Created</th>
          <th>Change Password</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $me = get_admin_user(); ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <strong><?= e($u['display_name']) ?></strong>
            <?php if ((int)$u['id'] === (int)($me['id'] ?? 0)): ?>
              <span class="badge badge-info" style="font-size:9px;">YOU</span>
            <?php endif; ?>
          </td>
          <td><?= e($u['username']) ?></td>
          <td>
            <span class="badge badge-<?= $u['role'] === 'super_admin' ? 'success' : ($u['role'] === 'admin' ? 'info' : 'default') ?>">
              <?= e($roleLabels[$u['role']] ?? $u['role']) ?>
            </span>
          </td>
          <td style="font-size:11px;color:#6E6E6E;">
            <?= e(date('d M Y', strtotime($u['created_at'] ?? 'now'))) ?>
          </td>
          <td>
            <form method="POST" action="/admin/users" style="display:flex;gap:6px;">
              <input type="hidden" name="action" value="change_password">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <input type="password" name="new_password" class="form-control"
                     style="width:140px;font-size:12px;padding:4px 8px;"
                     placeholder="New password…" minlength="6">
              <button type="submit" class="btn btn-outline btn-sm">Set</button>
            </form>
          </td>
          <td>
            <?php if ((int)$u['id'] !== (int)($me['id'] ?? 0)): ?>
            <form method="POST" action="/admin/users"
                  onsubmit="return confirm('Delete user <?= e(addslashes($u['email'])) ?>? This cannot be undone.');">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
            <?php else: ?>
              <span style="color:#ccc;font-size:11px;">(current user)</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_footer(); ?>
