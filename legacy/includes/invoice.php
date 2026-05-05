<?php
/**
 * OmniShop — FPDF PDF Invoice Generator
 *
 * Requires FPDF library at: LIB_PATH . '/fpdf/fpdf.php'
 * Download from: http://www.fpdf.org/  (fpdf182.zip)
 *
 * Usage:
 *   $pdf = generate_invoice($order, $items, $event, $settings);
 *   // $pdf is a string (raw PDF bytes)
 *   // To output inline: header('Content-Type: application/pdf'); echo $pdf;
 *   // To save: file_put_contents('/path/to/file.pdf', $pdf);
 */

if (!defined('BASE_PATH')) { exit; }

require_once LIB_PATH . '/fpdf/fpdf.php';

// ── Brand colours as RGB ──────────────────────────────────────────────────────
define('TEAL_R',    10);  define('TEAL_G',  150); define('TEAL_B',  150);  // #0A9696
define('LTEAL_R',  25);  define('LTEAL_G', 175); define('LTEAL_B', 172);  // #19AFAC
define('PTEAL_R', 214);  define('PTEAL_G', 240); define('PTEAL_B', 239);  // #D6F0EF
define('GREY_R',  110);  define('GREY_G',  110); define('GREY_B',  110);  // #6E6E6E
define('DARK_R',   51);  define('DARK_G',   51); define('DARK_B',   51);  // #333333

/**
 * OmniSpacePDF — extends FPDF with custom header/footer
 */
class OmniSpacePDF extends FPDF
{
    public $logoPath    = '';
    public $eventName   = '';
    public $orderRef    = '';
    public $companyName = 'OmniSpace 3D Events Ltd';
    public $companyAddr = '';
    public $companyPhone = '';
    public $companyWA   = '';
    public $companyEmail = '';
    public $companyWeb  = '';
    public $pageCount   = 0;

    function Header()
    {
        // ── Teal header band ─────────────────────────────────────────────────
        $this->SetFillColor(TEAL_R, TEAL_G, TEAL_B);
        $this->Rect(0, 0, 210, 28, 'F');

        // Logo (left side of band)
        $logo = $this->logoPath;
        if ($logo && file_exists($logo)) {
            $this->Image($logo, 8, 4, 40, 0, '', '');  // max width 40mm, auto height
        }

        // Company name in band (right-aligned)
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(120, 6);
        $this->Cell(82, 7, utf8_decode($this->companyName), 0, 1, 'R');
        $this->SetFont('Arial', '', 8);
        $this->SetXY(120, 13);
        $this->Cell(82, 5, utf8_decode($this->companyWeb), 0, 1, 'R');
        $this->SetXY(120, 18);
        $this->Cell(82, 5, utf8_decode($this->companyPhone), 0, 1, 'R');

        // ── Invoice title bar ─────────────────────────────────────────────────
        $this->SetFillColor(PTEAL_R, PTEAL_G, PTEAL_B);
        $this->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(0, 28);
        $this->Cell(105, 8, '  INVOICE', 0, 0, 'L', true);
        $this->Cell(105, 8, 'Ref: ' . $this->orderRef . '  ', 0, 1, 'R', true);

        $this->Ln(3);
        $this->SetTextColor(DARK_R, DARK_G, DARK_B);
    }

    function Footer()
    {
        $this->SetY(-22);
        // Thin teal line
        $this->SetDrawColor(TEAL_R, TEAL_G, TEAL_B);
        $this->SetLineWidth(0.4);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(1);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(GREY_R, GREY_G, GREY_B);
        $this->Cell(0, 4,
            utf8_decode($this->companyName) . '  |  ' .
            'WhatsApp: ' . $this->companyWA . '  |  ' .
            $this->companyEmail,
            0, 1, 'C');
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 4,
            'Page ' . $this->PageNo() . '  |  ' .
            'This is a computer-generated document.',
            0, 0, 'C');
    }
}

/**
 * Generate the PDF invoice and return it as a string.
 *
 * @param array $order    — order row from DB (order_id, contact_name, company_name, booth_number,
 *                          email, phone, subtotal, vat, total, created_at, notes, event_slug)
 * @param array $items    — array of order_items rows (product_name, color_name, quantity,
 *                          unit_price, total_price)
 * @param array $event    — event config array from get_event()
 * @param array $settings — settings from get_all_settings()
 * @return string         — raw PDF bytes
 */
function generate_invoice(array $order, array $items, array $event, array $settings): string
{
    $pdf = new OmniSpacePDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 28);
    $pdf->SetMargins(10, 40, 10);

    // ── Populate header/footer properties ────────────────────────────────────
    // Look for logo: prefer white_logo_color_background, fallback to logo_white_background
    $logoDir = BASE_PATH . '/static/images/';
    foreach (['white_logo_color_background.jpg', 'logo_white_background.jpg'] as $f) {
        if (file_exists($logoDir . $f)) {
            $pdf->logoPath = $logoDir . $f;
            break;
        }
    }

    $pdf->companyName  = $settings['company_name']      ?? 'OmniSpace 3D Events Ltd';
    $pdf->companyAddr  = $settings['company_address']   ?? 'Nairobi, Kenya';
    $pdf->companyPhone = $settings['company_phone']     ?? '+254 731 001 723';
    $pdf->companyWA    = $settings['company_whatsapp']  ?? '+254731001723';
    $pdf->companyEmail = $settings['company_email']     ?? 'info@omnispace3d.com';
    $pdf->companyWeb   = $settings['company_website']   ?? 'www.omnispace3d.com';
    $pdf->eventName    = $event['name']                 ?? '';
    $pdf->orderRef     = $order['order_id']             ?? '';

    $pdf->AddPage();

    $w = 190; // usable width

    // ── BILL TO / EVENT block ─────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->Cell(95, 5, 'BILL TO', 0, 0, 'L');
    $pdf->Cell(95, 5, 'EVENT DETAILS', 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);

    // Left: customer info
    $pdf->SetX(10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(95, 6, utf8_decode($order['company_name'] ?? ''), 0, 0, 'L');

    // Right: event info
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(95, 6, utf8_decode($event['name'] ?? ''), 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(10);
    $pdf->Cell(95, 5, utf8_decode('Contact: ' . ($order['contact_name'] ?? '')), 0, 0, 'L');
    $pdf->Cell(95, 5, utf8_decode('Venue: ' . ($event['venue'] ?? '')), 0, 1, 'L');

    $pdf->SetX(10);
    $pdf->Cell(95, 5, utf8_decode('Email: ' . ($order['email'] ?? '')), 0, 0, 'L');
    $pdf->Cell(95, 5, utf8_decode('Dates: ' . ($event['dates'] ?? '')), 0, 1, 'L');

    $pdf->SetX(10);
    $pdf->Cell(95, 5, utf8_decode('Phone: ' . ($order['phone'] ?? '')), 0, 0, 'L');
    $pdf->Cell(95, 5, utf8_decode('Stand / Booth: ' . ($order['booth_number'] ?? '')), 0, 1, 'L');

    // ── Invoice meta line ─────────────────────────────────────────────────────
    $pdf->Ln(2);
    $pdf->SetFillColor(PTEAL_R, PTEAL_G, PTEAL_B);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetX(10);
    $pdf->Cell(47, 6, 'Invoice No.', 1, 0, 'L', true);
    $pdf->Cell(48, 6, 'Invoice Date', 1, 0, 'L', true);
    $pdf->Cell(48, 6, 'Due Date', 1, 0, 'L', true);
    $pdf->Cell(47, 6, 'Currency', 1, 1, 'L', true);

    $createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
    $invoiceDate = date('d M Y', strtotime($createdAt));
    // Due date: 7 days from creation
    $dueDate = date('d M Y', strtotime($createdAt . ' +7 days'));

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);
    $pdf->SetX(10);
    $pdf->Cell(47, 7, utf8_decode($order['order_id'] ?? ''), 1, 0, 'L');
    $pdf->Cell(48, 7, $invoiceDate, 1, 0, 'L');
    $pdf->Cell(48, 7, $dueDate, 1, 0, 'L');
    $pdf->Cell(47, 7, 'USD (US Dollars)', 1, 1, 'L');

    $pdf->Ln(4);

    // ── ITEMS TABLE HEADER ───────────────────────────────────────────────────
    $pdf->SetFillColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetX(10);
    $pdf->Cell(10,  7, '#',           1, 0, 'C', true);
    $pdf->Cell(85,  7, 'ITEM',        1, 0, 'L', true);
    $pdf->Cell(20,  7, 'UNIT PRICE',  1, 0, 'R', true);
    $pdf->Cell(15,  7, 'QTY',         1, 0, 'C', true);
    $pdf->Cell(30,  7, 'UNIT PRICE',  1, 0, 'R', true);
    $pdf->Cell(30,  7, 'LINE TOTAL',  1, 1, 'R', true);

    // ── ITEMS ROWS ────────────────────────────────────────────────────────────
    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);
    $pdf->SetFont('Arial', '', 9);
    $rowNum  = 0;
    $subtotal = 0;

    foreach ($items as $item) {
        $rowNum++;
        // Alternate row fill
        if ($rowNum % 2 === 0) {
            $pdf->SetFillColor(PTEAL_R, PTEAL_G, PTEAL_B);
            $fill = true;
        } else {
            $pdf->SetFillColor(255, 255, 255);
            $fill = false;
        }

        $itemName  = $item['product_name'] ?? '';
        if (!empty($item['color_name'])) {
            $itemName .= ' — ' . $item['color_name'];
        }
        $unitPrice = (float)($item['unit_price']   ?? 0);
        $qty       = (int)($item['quantity']         ?? 1);
        $lineTotal = (float)($item['total_price']   ?? ($unitPrice * $qty));
        $subtotal += $lineTotal;

        // Measure text height for item name (may wrap)
        $pdf->SetFont('Arial', 'B', 9);
        $lineH = 6;

        $pdf->SetX(10);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(10,  $lineH, $rowNum,                        1, 0, 'C', $fill);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(85,  $lineH, utf8_decode($itemName),         1, 0, 'L', $fill);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(20,  $lineH, '$' . number_format($unitPrice, 2), 1, 0, 'R', $fill);
        $pdf->Cell(15,  $lineH, $qty,                           1, 0, 'C', $fill);
        $pdf->Cell(30,  $lineH, '$' . number_format($unitPrice, 2), 1, 0, 'R', $fill);
        $pdf->Cell(30,  $lineH, '$' . number_format($lineTotal, 2), 1, 1, 'R', $fill);
    }

    // ── TOTALS ────────────────────────────────────────────────────────────────
    $vat   = (float)($order['vat']   ?? ($subtotal * 0.16));
    $total = (float)($order['total'] ?? ($subtotal + $vat));
    $vatRate = defined('VAT_RATE') ? (int)VAT_RATE : 16;

    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);
    $labelX = 135;
    $valW   = 55;

    // Subtotal
    $pdf->SetX($labelX);
    $pdf->Cell(90, 6, 'Subtotal (before VAT)', 0, 0, 'R');
    $pdf->Cell($valW, 6, '$' . number_format($subtotal, 2), 0, 1, 'R');

    // VAT
    $pdf->SetX($labelX);
    $pdf->Cell(90, 6, 'VAT (' . $vatRate . '%)', 0, 0, 'R');
    $pdf->Cell($valW, 6, '$' . number_format($vat, 2), 0, 1, 'R');

    // Separator
    $pdf->SetDrawColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($labelX, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(1);

    // Grand Total
    $pdf->SetFillColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetX($labelX);
    $pdf->Cell(90, 9, 'TOTAL DUE (USD)', 0, 0, 'R', true);
    $pdf->Cell($valW, 9, '$' . number_format($total, 2), 0, 1, 'R', true);

    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);
    $pdf->Ln(4);

    // ── PAYMENT INSTRUCTIONS ──────────────────────────────────────────────────
    $paymentInstructions = $settings['payment_instructions'] ??
        "Payment is due within 7 days of invoice date.\n" .
        "Bank transfer details will be provided upon request.\n" .
        "Please quote your Order Reference when making payment.\n" .
        "For enquiries: WhatsApp " . ($settings['company_whatsapp'] ?? '+254731001723');

    $pdf->SetFillColor(PTEAL_R, PTEAL_G, PTEAL_B);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetX(10);
    $pdf->Cell($w, 6, 'PAYMENT INSTRUCTIONS', 0, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(DARK_R, DARK_G, DARK_B);
    $pdf->SetX(10);
    $pdf->MultiCell($w, 5, utf8_decode($paymentInstructions), 0, 'L');

    // ── NOTES (if any) ────────────────────────────────────────────────────────
    if (!empty($order['notes'])) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
        $pdf->SetX(10);
        $pdf->Cell($w, 6, 'ORDER NOTES', 0, 1, 'L', true);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(GREY_R, GREY_G, GREY_B);
        $pdf->SetX(10);
        $pdf->MultiCell($w, 5, utf8_decode($order['notes']), 0, 'L');
    }

    // ── TERMS & CONDITIONS ────────────────────────────────────────────────────
    $tAndC = $settings['invoice_tc'] ??
        "1. All prices are in USD and subject to applicable taxes.\n" .
        "2. Orders are confirmed upon receipt of payment.\n" .
        "3. Cancellation must be requested in writing at least 72 hours before the event.\n" .
        "4. OmniSpace 3D Events Ltd reserves the right to substitute products of equal or greater value.\n" .
        "5. Delivery and set-up are included unless otherwise stated.";

    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetFillColor(PTEAL_R, PTEAL_G, PTEAL_B);
    $pdf->SetX(10);
    $pdf->Cell($w, 6, 'TERMS & CONDITIONS', 0, 1, 'L', true);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(GREY_R, GREY_G, GREY_B);
    $pdf->SetX(10);
    $pdf->MultiCell($w, 4.5, utf8_decode($tAndC), 0, 'L');

    // ── THANK YOU FOOTER BOX ─────────────────────────────────────────────────
    $pdf->Ln(3);
    $pdf->SetFillColor(TEAL_R, TEAL_G, TEAL_B);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetX(10);
    $pdf->Cell($w, 8,
        'Thank you for your order! Questions? WhatsApp: ' . ($settings['company_whatsapp'] ?? ''),
        0, 1, 'C', true);

    // ── Output as string ─────────────────────────────────────────────────────
    return $pdf->Output('S');
}

/**
 * Save the invoice PDF to disk and return the path.
 *
 * @param string $orderId
 * @param string $pdfBytes raw PDF bytes
 * @return string file path
 */
function save_invoice_pdf(string $orderId, string $pdfBytes): string
{
    $dir = BASE_PATH . '/storage/invoices/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = 'invoice-' . preg_replace('/[^A-Z0-9\-]/', '', strtoupper($orderId)) . '.pdf';
    $path = $dir . $filename;
    file_put_contents($path, $pdfBytes);
    return $path;
}
