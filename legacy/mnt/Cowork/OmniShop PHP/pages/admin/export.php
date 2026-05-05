<?php
/**
 * Admin — Export Orders as CSV
 * Route: /admin/export/{event_slug}
 * Outputs CSV directly (no HTML wrapper).
 */
if (!defined('BASE_PATH')) { exit; }
require_admin_auth();

$eventSlug = $_GET['event_slug'] ?? '';
$event     = $eventSlug ? get_event($eventSlug) : null;

if (!$event) {
    http_response_code(404);
    echo 'Event not found.';
    exit;
}

$csvData = export_orders_csv($eventSlug);

$filename = 'OmniShop-Orders-' . strtoupper($eventSlug) . '-' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";
echo $csvData;
exit;
