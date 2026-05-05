<?php
/**
 * OmniShop — PHPMailer Email Sender
 *
 * Requires PHPMailer at: LIB_PATH . '/phpmailer/'
 * Files needed: Exception.php, PHPMailer.php, SMTP.php
 * Download: https://github.com/PHPMailer/PHPMailer/releases (v6.x)
 * Or: https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
 *
 * Usage:
 *   send_order_confirmation_email($order, $items, $event, $settings, $pdfBytes);
 *   send_payment_received_email($order, $event, $settings);
 */

if (!defined('BASE_PATH')) { exit; }

require_once LIB_PATH . '/phpmailer/Exception.php';
require_once LIB_PATH . '/phpmailer/PHPMailer.php';
require_once LIB_PATH . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── Internal: build a configured PHPMailer instance ──────────────────────────
function _make_mailer(array $settings): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0;  // Set to SMTP::DEBUG_SERVER for troubleshooting
    $mail->Host       = $settings['smtp_host']     ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_user']     ?? '';
    $mail->Password   = $settings['smtp_password'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($settings['smtp_port'] ?? 587);

    $fromEmail = $settings['smtp_from_email'] ?? $settings['smtp_user'] ?? 'noreply@omnispace3d.com';
    $fromName  = $settings['smtp_from_name']  ?? $settings['company_name'] ?? 'OmniSpace 3D Events Ltd';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addReplyTo($settings['company_email'] ?? $fromEmail, $fromName);

    return $mail;
}

// ── Shared HTML email wrapper ─────────────────────────────────────────────────
function _email_wrapper(string $body, array $settings, string $eventName = ''): string
{
    $companyName = htmlspecialchars($settings['company_name']   ?? 'OmniSpace 3D Events Ltd');
    $companyWA   = htmlspecialchars($settings['company_whatsapp'] ?? '+254731001723');
    $companyWeb  = htmlspecialchars($settings['company_website']  ?? 'www.omnispace3d.com');
    $waNum       = preg_replace('/[^0-9]/', '', $settings['company_whatsapp'] ?? '254731001723');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { margin:0; padding:0; background:#f5f5f5; font-family:Arial,sans-serif; color:#333; }
    .email-wrap { max-width:600px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden;
                  box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .email-header { background:#0A9696; padding:24px 30px; }
    .email-header h1 { margin:0; color:#fff; font-size:20px; font-weight:700; }
    .email-header p  { margin:4px 0 0; color:#D6F0EF; font-size:13px; }
    .email-body { padding:28px 30px; }
    .email-footer { background:#f5f5f5; padding:16px 30px; text-align:center;
                    font-size:11px; color:#999; border-top:1px solid #eee; }
    .badge { display:inline-block; background:#D6F0EF; color:#0A9696; font-weight:700;
             padding:2px 10px; border-radius:12px; font-size:13px; }
    table.items { width:100%; border-collapse:collapse; margin:16px 0; font-size:13px; }
    table.items th { background:#0A9696; color:#fff; padding:8px 10px; text-align:left; }
    table.items td { padding:7px 10px; border-bottom:1px solid #D6F0EF; }
    table.items tr:nth-child(even) td { background:#f9fffe; }
    .total-row td { font-weight:700; color:#0A9696; font-size:15px; border-top:2px solid #0A9696; }
    .btn { display:inline-block; background:#0A9696; color:#fff; text-decoration:none;
           padding:10px 22px; border-radius:5px; font-weight:700; font-size:13px; }
    .info-box { background:#D6F0EF; border-left:4px solid #0A9696; padding:12px 16px;
                border-radius:4px; margin:16px 0; font-size:13px; color:#333; }
  </style>
</head>
<body>
  <div class="email-wrap">
    <div class="email-header">
      <h1>$companyName</h1>
      <p>$eventName</p>
    </div>
    <div class="email-body">
      $body
    </div>
    <div class="email-footer">
      <p>$companyName &nbsp;|&nbsp; <a href="https://$companyWeb" style="color:#0A9696;">$companyWeb</a></p>
      <p>WhatsApp: <a href="https://wa.me/$waNum" style="color:#0A9696;">$companyWA</a></p>
      <p style="color:#bbb;font-size:10px;">This is an automated message. Please do not reply directly to this email.</p>
    </div>
  </div>
</body>
</html>
HTML;
}

/**
 * Send order confirmation email to customer with PDF invoice attached.
 *
 * @param array  $order    — order row from DB
 * @param array  $items    — order_items rows
 * @param array  $event    — event config from get_event()
 * @param array  $settings — all settings from get_all_settings()
 * @param string $pdfBytes — raw PDF bytes to attach
 * @return bool  true on success, false on failure
 */
function send_order_confirmation_email(
    array $order,
    array $items,
    array $event,
    array $settings,
    string $pdfBytes
): bool {
    try {
        $mail = _make_mailer($settings);

        // Recipients
        $mail->addAddress($order['email'], $order['contact_name'] ?? '');

        // BCC to company
        $companyEmail = $settings['company_email'] ?? '';
        if ($companyEmail) {
            $mail->addBCC($companyEmail, $settings['company_name'] ?? 'OmniSpace');
        }

        $orderId    = $order['order_id'] ?? '';
        $eventName  = $event['name'] ?? '';
        $companyWA  = $settings['company_whatsapp'] ?? '+254731001723';
        $waNum      = preg_replace('/[^0-9]/', '', $companyWA);

        $mail->Subject = 'Order Confirmed: ' . $orderId . ' — ' . $eventName;

        // Build items table rows
        $itemRows   = '';
        $subtotal   = 0;
        foreach ($items as $item) {
            $name      = htmlspecialchars($item['product_name'] ?? '');
            $color     = !empty($item['color_name']) ? ' <span style="color:#999;font-size:11px;">— ' . htmlspecialchars($item['color_name']) . '</span>' : '';
            $qty       = (int)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price']  ?? 0);
            $lineTotal = (float)($item['total_price'] ?? ($unitPrice * $qty));
            $subtotal += $lineTotal;
            $itemRows .= '<tr>
                <td>' . $name . $color . '</td>
                <td style="text-align:center;">' . $qty . '</td>
                <td style="text-align:right;">$' . number_format($unitPrice, 2) . '</td>
                <td style="text-align:right;">$' . number_format($lineTotal, 2) . '</td>
              </tr>';
        }

        $vatRate = defined('VAT_RATE') ? (int)VAT_RATE : 16;
        $vat     = (float)($order['vat']   ?? 0);
        $total   = (float)($order['total'] ?? 0);

        $booth     = htmlspecialchars($order['booth_number']  ?? '');
        $company   = htmlspecialchars($order['company_name']  ?? '');
        $contact   = htmlspecialchars($order['contact_name']  ?? '');

        $body = <<<HTML
      <p style="font-size:15px;">Hi <strong>$contact</strong>,</p>
      <p>Thank you! Your order has been received and confirmed.</p>

      <div class="info-box">
        <strong>Order Reference:</strong> <span class="badge">$orderId</span><br>
        <strong>Company:</strong> $company &nbsp;&nbsp;
        <strong>Stand:</strong> $booth<br>
        <strong>Event:</strong> $eventName
      </div>

      <h3 style="color:#0A9696;margin-bottom:8px;">Order Summary</h3>
      <table class="items">
        <thead>
          <tr>
            <th>Item</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Unit Price</th>
            <th style="text-align:right;">Total</th>
          </tr>
        </thead>
        <tbody>
          $itemRows
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="text-align:right;padding:6px 10px;color:#555;">Subtotal</td>
            <td style="text-align:right;padding:6px 10px;">$$subtotalFmt</td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:right;padding:4px 10px;color:#555;">VAT ($vatRate%)</td>
            <td style="text-align:right;padding:4px 10px;">$$vatFmt</td>
          </tr>
          <tr class="total-row">
            <td colspan="3" style="text-align:right;padding:8px 10px;">TOTAL (USD)</td>
            <td style="text-align:right;padding:8px 10px;">$$totalFmt</td>
          </tr>
        </tfoot>
      </table>

      <div class="info-box">
        <strong>📎 Your PDF invoice is attached.</strong><br>
        Payment instructions are included in the invoice. Please make payment within 7 days.
      </div>

      <p style="font-size:13px;">For any questions, please WhatsApp us:<br>
        <a href="https://wa.me/$waNum" class="btn" style="margin-top:8px;">
          💬 WhatsApp $companyWA
        </a>
      </p>
      <p style="font-size:12px;color:#999;margin-top:20px;">
        Please quote your order reference <strong>$orderId</strong> in all correspondence.
      </p>
HTML;

        // Replace PHP heredoc placeholders that won't interpolate in this context
        $subtotalFmt = number_format($subtotal, 2);
        $vatFmt      = number_format($vat, 2);
        $totalFmt    = number_format($total, 2);
        $body = str_replace('$$subtotalFmt', $subtotalFmt, $body);
        $body = str_replace('$$vatFmt', $vatFmt, $body);
        $body = str_replace('$$totalFmt', $totalFmt, $body);

        $mail->isHTML(true);
        $mail->Body    = _email_wrapper($body, $settings, $eventName);
        $mail->AltBody = "Order Confirmed: $orderId\n$eventName\n\nThank you, $contact!\n\nYour PDF invoice is attached. Please make payment within 7 days.\n\nFor questions: WhatsApp $companyWA";

        // Attach PDF
        $filename = 'Invoice-' . preg_replace('/[^A-Z0-9\-]/', '', strtoupper($orderId)) . '.pdf';
        $mail->addStringAttachment($pdfBytes, $filename, 'base64', 'application/pdf');

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('[OmniShop Email Error] send_order_confirmation_email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send payment received / status update email to customer.
 *
 * @param array  $order    — order row from DB (should include updated status)
 * @param array  $event    — event config
 * @param array  $settings — all settings
 * @return bool
 */
function send_payment_received_email(
    array $order,
    array $event,
    array $settings
): bool {
    try {
        $mail = _make_mailer($settings);

        $mail->addAddress($order['email'], $order['contact_name'] ?? '');

        $orderId   = $order['order_id'] ?? '';
        $eventName = $event['name'] ?? '';
        $contact   = htmlspecialchars($order['contact_name'] ?? '');
        $company   = htmlspecialchars($order['company_name'] ?? '');
        $booth     = htmlspecialchars($order['booth_number'] ?? '');
        $total     = number_format((float)($order['total'] ?? 0), 2);
        $companyWA = $settings['company_whatsapp'] ?? '+254731001723';
        $waNum     = preg_replace('/[^0-9]/', '', $companyWA);

        $mail->Subject = 'Payment Confirmed — ' . $orderId . ' — ' . $eventName;

        $body = <<<HTML
      <p style="font-size:15px;">Hi <strong>$contact</strong>,</p>
      <p>Great news! We have received your payment and your order is confirmed for delivery.</p>

      <div class="info-box">
        <strong>Order Reference:</strong> <span class="badge">$orderId</span><br>
        <strong>Company:</strong> $company &nbsp;&nbsp;
        <strong>Stand:</strong> $booth<br>
        <strong>Total Paid:</strong> <strong style="color:#0A9696;">$$$total USD</strong>
      </div>

      <p>Your items will be set up at your stand before the event opens. Our team will be on-site
         throughout the event if you need any assistance.</p>

      <p style="font-size:13px;">For any questions, WhatsApp us:
        <a href="https://wa.me/$waNum" class="btn" style="margin-top:8px;">
          💬 WhatsApp $companyWA
        </a>
      </p>
      <p style="font-size:12px;color:#999;margin-top:20px;">
        Please quote your order reference <strong>$orderId</strong> in all correspondence.
      </p>
HTML;

        $mail->isHTML(true);
        $mail->Body    = _email_wrapper($body, $settings, $eventName);
        $mail->AltBody = "Payment Confirmed: $orderId\n\nThank you $contact — your payment of $$$total USD has been received.\n\nFor questions: WhatsApp $companyWA";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('[OmniShop Email Error] send_payment_received_email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a new-order notification to the admin/company email.
 *
 * @param array $order
 * @param array $items
 * @param array $event
 * @param array $settings
 * @return bool
 */
function send_admin_notification_email(
    array $order,
    array $items,
    array $event,
    array $settings
): bool {
    $companyEmail = $settings['company_email'] ?? '';
    if (!$companyEmail) return false;

    try {
        $mail = _make_mailer($settings);
        $mail->addAddress($companyEmail, $settings['company_name'] ?? 'OmniSpace');

        $orderId   = $order['order_id']     ?? '';
        $eventName = $event['name']          ?? '';
        $contact   = htmlspecialchars($order['contact_name']  ?? '');
        $company   = htmlspecialchars($order['company_name']  ?? '');
        $booth     = htmlspecialchars($order['booth_number']  ?? '');
        $email     = htmlspecialchars($order['email']          ?? '');
        $phone     = htmlspecialchars($order['phone']          ?? '');
        $total     = number_format((float)($order['total'] ?? 0), 2);
        $adminUrl  = (defined('SITE_URL') ? SITE_URL : '') . '/admin/order/' . urlencode($orderId);

        // Build items list
        $itemsList = '';
        foreach ($items as $item) {
            $name  = htmlspecialchars($item['product_name'] ?? '');
            $color = !empty($item['color_name']) ? ' (' . htmlspecialchars($item['color_name']) . ')' : '';
            $qty   = (int)($item['quantity'] ?? 1);
            $line  = (float)($item['total_price'] ?? 0);
            $itemsList .= "<li>$name$color — Qty: $qty — $" . number_format($line, 2) . "</li>";
        }

        $mail->Subject = '🛒 New Order: ' . $orderId . ' — ' . $company . ' (' . $eventName . ')';

        $body = <<<HTML
      <p><strong>New order received!</strong></p>
      <div class="info-box">
        <strong>Order:</strong> <span class="badge">$orderId</span><br>
        <strong>Event:</strong> $eventName<br>
        <strong>Company:</strong> $company &nbsp;|&nbsp; <strong>Stand:</strong> $booth<br>
        <strong>Contact:</strong> $contact<br>
        <strong>Email:</strong> $email &nbsp;|&nbsp; <strong>Phone:</strong> $phone<br>
        <strong>Total:</strong> <strong style="color:#0A9696;">$$$total USD</strong>
      </div>
      <ul style="font-size:13px;line-height:1.8;">$itemsList</ul>
      <p>
        <a href="$adminUrl" class="btn">View Order in Admin Panel →</a>
      </p>
HTML;

        $mail->isHTML(true);
        $mail->Body    = _email_wrapper($body, $settings, 'Admin Notification — ' . $eventName);
        $mail->AltBody = "New order $orderId from $company ($booth)\nTotal: $$$total\nView: $adminUrl";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('[OmniShop Email Error] send_admin_notification_email: ' . $e->getMessage());
        return false;
    }
}
