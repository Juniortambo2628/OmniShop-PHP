<?php
/**
 * OmniShop Database Layer
 * ─────────────────────────────────────────────────────────────────────────────
 * All database access goes through this file.
 * Uses PDO with MySQL. Connection is created once and reused.
 */

$_pdo = null;

function get_pdo() {
    global $_pdo;
    if ($_pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $_pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $_pdo;
}

// ── SETTINGS ──────────────────────────────────────────────────────────────────

function get_setting($key, $default = '') {
    try {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function set_setting($key, $value) {
    $pdo = get_pdo();
    $pdo->prepare(
        'INSERT INTO settings (`key`, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    )->execute([$key, $value]);
}

function get_all_settings() {
    try {
        $pdo  = get_pdo();
        $stmt = $pdo->query('SELECT `key`, value FROM settings');
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $r['value'];
        }
        return $out;
    } catch (Exception $e) {
        return [];
    }
}

// ── CATALOG PASSWORD ──────────────────────────────────────────────────────────

function get_catalog_password($eventSlug) {
    $db = get_setting("catalog_password_{$eventSlug}");
    if ($db) return $db;
    $event = get_event($eventSlug);
    return $event['catalog_password_default'] ?? '';
}

function get_catalog_demo_password($eventSlug) {
    return get_setting("catalog_demo_password_{$eventSlug}");
}

// ── ORDERS ───────────────────────────────────────────────────────────────────

function get_next_order_number($pdo = null) {
    if (!$pdo) $pdo = get_pdo();
    $pdo->exec('INSERT INTO order_sequence (stub) VALUES (1)');
    return (int)$pdo->lastInsertId();
}

function create_order($data) {
    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders
              (order_id, event_slug, company_name, contact_name, email, phone,
               booth_number, subtotal, vat, total, status, notes, created_at)
            VALUES
              (:order_id, :event_slug, :company_name, :contact_name, :email, :phone,
               :booth_number, :subtotal, :vat, :total, 'Pending', :notes, NOW())
        ");
        $stmt->execute([
            ':order_id'     => $data['order_id'],
            ':event_slug'   => $data['event_slug'],
            ':company_name' => $data['company_name'],
            ':contact_name' => $data['contact_name'],
            ':email'        => $data['email'],
            ':phone'        => $data['phone'] ?? '',
            ':booth_number' => $data['booth_number'],
            ':subtotal'     => $data['subtotal'],
            ':vat'          => $data['vat'],
            ':total'        => $data['total'],
            ':notes'        => $data['notes'] ?? '',
        ]);

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items
              (order_id, product_code, product_name, color_name, category,
               quantity, unit_price, total_price, dimensions)
            VALUES
              (:order_id, :product_code, :product_name, :color_name, :category,
               :quantity, :unit_price, :total_price, :dimensions)
        ");
        foreach ($data['items'] as $item) {
            $itemStmt->execute([
                ':order_id'     => $data['order_id'],
                ':product_code' => $item['product_code'] ?? $item['code'] ?? '',
                ':product_name' => $item['product_name'] ?? $item['name'] ?? '',
                ':color_name'   => $item['color_name'] ?? '',
                ':category'     => $item['category'] ?? '',
                ':quantity'     => (int)($item['quantity'] ?? 1),
                ':unit_price'   => (float)($item['unit_price'] ?? 0),
                ':total_price'  => (float)($item['total_price'] ?? 0),
                ':dimensions'   => $item['dimensions'] ?? '',
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_order($orderId) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return null;

    $istmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $istmt->execute([$orderId]);
    $items = $istmt->fetchAll();

    return ['order' => $order, 'items' => $items];
}

function get_all_orders($eventSlug = null, $status = null, $search = null) {
    $pdo    = get_pdo();
    $where  = [];
    $params = [];

    if ($eventSlug) {
        $where[]  = 'event_slug = ?';
        $params[] = $eventSlug;
    }
    if ($status) {
        $where[]  = 'status = ?';
        $params[] = $status;
    }
    if ($search) {
        $where[]  = '(order_id LIKE ? OR company_name LIKE ? OR contact_name LIKE ? OR booth_number LIKE ?)';
        $like     = "%{$search}%";
        $params   = array_merge($params, [$like, $like, $like, $like]);
    }

    $sql  = 'SELECT * FROM orders';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function update_order_status($orderId, $status) {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?')
        ->execute([$status, $orderId]);
}

function get_order_stats($eventSlug) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("
        SELECT
          COUNT(*) AS total_orders,
          SUM(CASE WHEN status = 'Pending'   THEN 1 ELSE 0 END) AS pending,
          SUM(CASE WHEN status = 'Fulfilled' THEN 1 ELSE 0 END) AS fulfilled,
          SUM(total) AS total_revenue
        FROM orders WHERE event_slug = ?
    ");
    $stmt->execute([$eventSlug]);
    return $stmt->fetch();
}

// ── PACKING LISTS ─────────────────────────────────────────────────────────────

function get_packing_list($eventSlug) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("
        SELECT
          oi.category,
          oi.product_code,
          oi.product_name,
          oi.color_name,
          oi.dimensions,
          SUM(oi.quantity) AS total_qty,
          GROUP_CONCAT(DISTINCT o.booth_number ORDER BY o.booth_number SEPARATOR ', ') AS booths
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.event_slug = ? AND o.status != 'Cancelled'
        GROUP BY oi.category, oi.product_code, oi.color_name
        ORDER BY oi.category, oi.product_name
    ");
    $stmt->execute([$eventSlug]);
    return $stmt->fetchAll();
}

function get_packing_list_by_booth($eventSlug) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("
        SELECT
          o.booth_number,
          o.company_name,
          o.order_id,
          oi.product_code,
          oi.product_name,
          oi.color_name,
          oi.category,
          oi.dimensions,
          oi.quantity
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.event_slug = ? AND o.status != 'Cancelled'
        ORDER BY o.booth_number, oi.category, oi.product_name
    ");
    $stmt->execute([$eventSlug]);
    $rows = $stmt->fetchAll();

    // Group by booth
    $grouped = [];
    foreach ($rows as $row) {
        $booth = $row['booth_number'];
        if (!isset($grouped[$booth])) {
            $grouped[$booth] = [
                'company_name' => $row['company_name'],
                'order_id'     => $row['order_id'],
                'items'        => [],
            ];
        }
        $grouped[$booth]['items'][] = $row;
    }
    return $grouped;
}

function export_orders_csv($eventSlug) {
    $orders = get_all_orders($eventSlug);
    $lines  = ["Order ID,Event,Company,Contact,Email,Phone,Stand,Subtotal,VAT,Total,Status,Date"];
    foreach ($orders as $o) {
        $lines[] = implode(',', array_map(function($v) {
            return '"' . str_replace('"', '""', $v) . '"';
        }, [
            $o['order_id'], $o['event_slug'], $o['company_name'], $o['contact_name'],
            $o['email'], $o['phone'], $o['booth_number'],
            $o['subtotal'], $o['vat'], $o['total'], $o['status'], $o['created_at'],
        ]));
    }
    return implode("\n", $lines);
}

// ── PRODUCTS (Admin-managed) ──────────────────────────────────────────────────

function get_admin_products() {
    $pdo  = get_pdo();
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category_id, name');
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['colors']  = json_decode($r['colors_json'] ?? '[]', true) ?: [];
        $r['is_poa']  = (bool)$r['is_poa'];
    }
    return $rows;
}

function get_admin_product($prodId) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE prod_id = ? LIMIT 1');
    $stmt->execute([$prodId]);
    $r = $stmt->fetch();
    if (!$r) return null;
    $r['colors'] = json_decode($r['colors_json'] ?? '[]', true) ?: [];
    $r['is_poa'] = (bool)$r['is_poa'];
    return $r;
}

function create_admin_product($data, $createdBy = 'admin') {
    $pdo = get_pdo();
    $pdo->prepare("
        INSERT INTO products
          (prod_id, code, name, category_id, colors_json, dimensions,
           price, price_display, description, unit, is_poa, is_override,
           is_active, created_by, created_at)
        VALUES
          (:prod_id, :code, :name, :category_id, :colors_json, :dimensions,
           :price, :price_display, :description, :unit, :is_poa, :is_override,
           1, :created_by, NOW())
    ")->execute([
        ':prod_id'       => $data['prod_id'],
        ':code'          => $data['code'],
        ':name'          => $data['name'],
        ':category_id'   => $data['category_id'],
        ':colors_json'   => json_encode($data['colors'] ?? []),
        ':dimensions'    => $data['dimensions'] ?? '',
        ':price'         => $data['price'] ?? 0,
        ':price_display' => $data['price_display'] ?? '',
        ':description'   => $data['description'] ?? '',
        ':unit'          => $data['unit'] ?? 'per event',
        ':is_poa'        => $data['is_poa'] ? 1 : 0,
        ':is_override'   => $data['is_override'] ? 1 : 0,
        ':created_by'    => $createdBy,
    ]);
}

function update_admin_product($prodId, $fields, $allowPriceChange = true) {
    $pdo  = get_pdo();
    $sets = ['name = :name', 'category_id = :category_id', 'colors_json = :colors_json',
             'dimensions = :dimensions', 'description = :description', 'unit = :unit',
             'is_poa = :is_poa', 'updated_at = NOW()'];
    $params = [
        ':prod_id'     => $prodId,
        ':name'        => $fields['name'],
        ':category_id' => $fields['category_id'],
        ':colors_json' => json_encode($fields['colors'] ?? []),
        ':dimensions'  => $fields['dimensions'] ?? '',
        ':description' => $fields['description'] ?? '',
        ':unit'        => $fields['unit'] ?? 'per event',
        ':is_poa'      => $fields['is_poa'] ? 1 : 0,
    ];
    if ($allowPriceChange) {
        $sets[]              = 'price = :price';
        $sets[]              = 'price_display = :price_display';
        $sets[]              = 'code = :code';
        $params[':price']         = $fields['price'] ?? 0;
        $params[':price_display'] = $fields['price_display'] ?? '';
        $params[':code']          = $fields['code'] ?? '';
    }
    $pdo->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE prod_id = :prod_id')
        ->execute($params);
}

function delete_admin_product($prodId) {
    get_pdo()->prepare('DELETE FROM products WHERE prod_id = ?')->execute([$prodId]);
}

function toggle_product_active($prodId, $active) {
    get_pdo()->prepare('UPDATE products SET is_active = ? WHERE prod_id = ?')
             ->execute([$active ? 1 : 0, $prodId]);
}

// ── ADMIN USERS ───────────────────────────────────────────────────────────────

function get_all_admin_users() {
    $pdo  = get_pdo();
    $stmt = $pdo->query('SELECT id, username, display_name, role, created_at FROM admin_users ORDER BY created_at');
    return $stmt->fetchAll();
}

function verify_admin_user($username, $password) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([strtolower(trim($username))]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}

function create_admin_user($username, $displayName, $password, $role) {
    $pdo  = get_pdo();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("
        INSERT INTO admin_users (username, display_name, password_hash, role, is_active, created_at)
        VALUES (?, ?, ?, ?, 1, NOW())
    ")->execute([strtolower(trim($username)), $displayName, $hash, $role]);
}

function delete_admin_user($id) {
    get_pdo()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
}

function update_admin_user_password($id, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    get_pdo()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
             ->execute([$hash, $id]);
}

// ── STOCK ─────────────────────────────────────────────────────────────────────

function get_stock_levels() {
    $pdo  = get_pdo();
    $stmt = $pdo->query('SELECT * FROM stock_limits');
    $rows = $stmt->fetchAll();
    $out  = [];
    foreach ($rows as $r) {
        $out[$r['product_code']] = $r;
    }
    return $out;
}

function set_stock_level($code, $name, $categoryId, $limit) {
    $pdo = get_pdo();
    $pdo->prepare("
        INSERT INTO stock_limits (product_code, product_name, category_id, stock_limit, updated_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE product_name = VALUES(product_name),
          category_id = VALUES(category_id), stock_limit = VALUES(stock_limit), updated_at = NOW()
    ")->execute([$code, $name, $categoryId, $limit]);
}

function get_stock_usage($eventSlug) {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("
        SELECT oi.product_code, SUM(oi.quantity) AS total_ordered
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        WHERE o.event_slug = ? AND o.status != 'Cancelled'
        GROUP BY oi.product_code
    ");
    $stmt->execute([$eventSlug]);
    $rows = $stmt->fetchAll();
    $out  = [];
    foreach ($rows as $r) {
        $out[$r['product_code']] = $r;
    }
    return $out;
}
