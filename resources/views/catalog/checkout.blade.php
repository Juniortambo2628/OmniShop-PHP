@extends('catalog.layout')

@section('title', 'Your Order — ' . $event['name'])
@section('event_name', $event['name'])

@section('content')
<div class="checkout-page">
    <h1 style="font-size:22px;color:#0A9696;margin-bottom:6px;">Review &amp; Submit Your Order</h1>
    <p style="color:#6E6E6E;margin-bottom:24px;">{{ $event['name'] }} · {{ $event['venue'] }}</p>

    <div id="empty-cart-msg" style="display:none;text-align:center;padding:40px;">
        <p style="font-size:18px;color:#6E6E6E;">Your order is empty.</p>
        <a href="{{ route('catalog', $event['slug']) }}" class="btn btn-primary mt-2">← Back to Catalogue</a>
    </div>

    <div id="checkout-content" style="display:none;">
        <div class="checkout-grid">

            <!-- Order summary -->
            <div>
                <div class="card mb-2">
                    <div class="card-header">Your Order Summary</div>
                    <div class="card-body">
                        <table class="order-summary-table" id="summary-table">
                            <thead>
                                <tr style="font-size:11px;color:#6E6E6E;text-transform:uppercase;letter-spacing:0.3px;">
                                    <td>Item</td>
                                    <td style="text-align:right;">Qty</td>
                                    <td style="text-align:right;">Price</td>
                                    <td style="text-align:right;">Total</td>
                                    <td></td>
                                </tr>
                            </thead>
                            <tbody id="summary-body"></tbody>
                            <tfoot class="totals">
                                <tr>
                                    <td colspan="3" style="text-align:right;color:#6E6E6E;">Subtotal</td>
                                    <td style="text-align:right;" id="subtotal-cell">$0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align:right;color:#6E6E6E;">VAT ({{ config('app.vat_rate', 16) }}%)</td>
                                    <td style="text-align:right;" id="vat-cell">$0.00</td>
                                    <td></td>
                                </tr>
                                <tr class="grand-total">
                                    <td colspan="3" style="text-align:right;">TOTAL (USD)</td>
                                    <td style="text-align:right;" id="total-cell">$0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <a href="{{ route('catalog', $event['slug']) }}" class="btn btn-outline btn-sm">← Add More Items</a>
            </div>

            <!-- Contact form -->
            <div>
                <div class="card">
                    <div class="card-header">Your Details</div>
                    <div class="card-body">
                        <div id="form-error" class="alert alert-danger" style="display:none;"></div>
                        <form id="checkout-form">
                            @csrf
                            <div class="form-group">
                                <label>Contact Name *</label>
                                <input type="text" name="contact_name" class="form-control" required
                                       placeholder="Your full name">
                            </div>
                            <div class="form-group">
                                <label>Company / Organisation *</label>
                                <input type="text" name="company_name" class="form-control" required
                                       placeholder="Your company name">
                            </div>
                            <div class="form-group">
                                <label>Stand / Booth Number *</label>
                                <input type="text" name="booth_number" class="form-control" required
                                       placeholder="e.g. A12 or Stand 42">
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" required
                                       placeholder="your@email.com">
                            </div>
                            <div class="form-group">
                                <label>Phone / WhatsApp</label>
                                <input type="tel" name="phone" class="form-control"
                                       placeholder="+254 700 000 000">
                            </div>
                            <div class="form-group">
                                <label>Notes / Special Requests</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="Any special requirements or notes for your order"></textarea>
                            </div>

                            <div style="background:#D6F0EF;border-radius:6px;padding:12px;font-size:12px;color:#0A9696;margin-bottom:16px;">
                                <strong>After submitting:</strong> You will receive a PDF invoice by email.
                                Payment instructions will be included. For urgent queries:
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('app.company_whatsapp', '+254731001723')) }}">WhatsApp us</a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
                                Submit Order &amp; Get Invoice →
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Load cart from session storage
var cart = {};
var saved = sessionStorage.getItem('omnishop_cart');
var savedEvent = sessionStorage.getItem('omnishop_event');

if (saved && savedEvent === EVENT_SLUG) {
    try { cart = JSON.parse(saved); } catch(e) { cart = {}; }
}

function fmt(n) { return '$' + parseFloat(n).toFixed(2); }

function renderSummary() {
    var items = Object.values(cart);
    if (items.length === 0) {
        document.getElementById('empty-cart-msg').style.display = 'block';
        document.getElementById('checkout-content').style.display = 'none';
        return;
    }
    document.getElementById('empty-cart-msg').style.display = 'none';
    document.getElementById('checkout-content').style.display = 'block';

    var tbody = document.getElementById('summary-body');
    tbody.innerHTML = '';
    var subtotal = 0;
    items.forEach(function(item) {
        var lineTotal = item.price * item.quantity;
        subtotal += lineTotal;
        var row = document.createElement('tr');
        row.innerHTML =
            '<td class="item-name">' + item.product_name +
              (item.selectedColor ? '<br><small style="color:#6E6E6E">' + item.selectedColor + '</small>' : '') +
            '</td>' +
            '<td style="text-align:right;">' + item.quantity + '</td>' +
            '<td style="text-align:right;">' + item.price_display + '</td>' +
            '<td style="text-align:right;">' + fmt(lineTotal) + '</td>' +
            '<td><button onclick="removeItem(\'' + item.product_id + '\')" style="background:none;border:none;color:#c00;cursor:pointer;font-size:14px;" title="Remove">✕</button></td>';
        tbody.appendChild(row);
    });
    var vat   = subtotal * (VAT_RATE / 100);
    var total = subtotal + vat;
    document.getElementById('subtotal-cell').textContent = fmt(subtotal);
    document.getElementById('vat-cell').textContent      = fmt(vat);
    document.getElementById('total-cell').textContent    = fmt(total);
}

function removeItem(productId) {
    delete cart[productId];
    sessionStorage.setItem('omnishop_cart', JSON.stringify(cart));
    renderSummary();
}

renderSummary();

// Form submission
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('submit-btn');
    btn.textContent = 'Submitting...';
    btn.disabled = true;

    var items = Object.values(cart).map(function(i) { return {
        product_code: i.product_code,
        product_name: i.product_name,
        color_name:   i.selectedColor || '',
        category:     i.category,
        dimensions:   i.dimensions,
        quantity:     i.quantity,
        unit_price:   i.price,
    }; });

    var subtotal = items.reduce(function(s, i) { return s + i.unit_price * i.quantity; }, 0);
    var vat      = subtotal * (VAT_RATE / 100);
    var total    = subtotal + vat;

    var payload = {
        event_slug:   EVENT_SLUG,
        contact_name: this.contact_name.value,
        company_name: this.company_name.value,
        booth_number: this.booth_number.value,
        email:        this.email.value,
        phone:        this.phone.value,
        notes:        this.notes.value,
        subtotal:     Math.round(subtotal * 100) / 100,
        vat:          Math.round(vat * 100) / 100,
        total:        Math.round(total * 100) / 100,
        items:        items,
    };

    fetch(SITE_URL + '/api/submit-order', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            sessionStorage.removeItem('omnishop_cart');
            sessionStorage.removeItem('omnishop_event');
            window.location.href = SITE_URL + '/' + EVENT_SLUG + '/confirmation/' + data.order_id;
        } else {
            document.getElementById('form-error').style.display = 'block';
            document.getElementById('form-error').textContent = data.error || 'An error occurred. Please try again.';
            btn.textContent = 'Submit Order & Get Invoice →';
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        document.getElementById('form-error').style.display = 'block';
        document.getElementById('form-error').textContent = 'Network error. Please check your connection and try again.';
        btn.textContent = 'Submit Order & Get Invoice →';
        btn.disabled = false;
    });
});
</script>
@endpush
