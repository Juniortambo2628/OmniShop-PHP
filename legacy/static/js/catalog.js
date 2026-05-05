/**
 * OmniShop Catalog JavaScript
 * Cart management, colour switcher, sidebar scroll, order submission
 */

// ── Cart state ────────────────────────────────────────────────────────────────
var cart = {};   // { productId: { ...productData, quantity, selectedColor, selectedColorId } }

// ── Colour selection ─────────────────────────────────────────────────────────
function selectColor(btn, productId) {
    var container = document.getElementById('colors-' + productId);
    if (!container) return;
    container.querySelectorAll('.color-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');

    var colorId   = btn.getAttribute('data-color-id');
    var colorName = btn.getAttribute('data-color-name');

    // Update image if a colour-specific image exists
    var code = document.querySelector('[data-product-id="' + productId + '"]').getAttribute('data-product-code');
    var imgEl = document.getElementById('prod-img-' + productId);
    if (imgEl && IMAGES[code]) {
        var src = IMAGES[code][colorId] || IMAGES[code]['default'] || null;
        if (src) imgEl.src = SITE_URL + '/static/images/products/' + src;
    }

    // Update cart if this product is already in cart
    if (cart[productId]) {
        cart[productId].selectedColor   = colorName;
        cart[productId].selectedColorId = colorId;
        updateCartUI();
    }
}

function getSelectedColor(productId) {
    var container = document.getElementById('colors-' + productId);
    if (!container) {
        // Single colour — read from product card
        var card = document.querySelector('[data-product-id="' + productId + '"]');
        var colors = JSON.parse(card.getAttribute('data-colors') || '[]');
        return colors.length > 0 ? { id: colors[0].id, name: colors[0].name } : { id: '', name: '' };
    }
    var active = container.querySelector('.color-btn.active');
    if (!active) return { id: '', name: '' };
    return { id: active.getAttribute('data-color-id'), name: active.getAttribute('data-color-name') };
}

// ── Quantity controls ────────────────────────────────────────────────────────
function changeQty(productId, delta) {
    var input = document.getElementById('qty-' + productId);
    if (!input) return;
    var val = Math.max(1, (parseInt(input.value) || 1) + delta);
    input.value = val;
    if (cart[productId]) {
        cart[productId].quantity = val;
        updateCartUI();
    }
}

// ── Add to order ──────────────────────────────────────────────────────────────
function addToOrder(productId) {
    var card  = document.querySelector('[data-product-id="' + productId + '"]');
    if (!card) return;

    var qty   = parseInt((document.getElementById('qty-' + productId) || {}).value || 1);
    var color = getSelectedColor(productId);

    var item = {
        product_id:       productId,
        product_code:     card.getAttribute('data-product-code'),
        product_name:     card.getAttribute('data-product-name'),
        price:            parseFloat(card.getAttribute('data-price')),
        price_display:    card.getAttribute('data-price-display'),
        category:         card.getAttribute('data-category'),
        category_name:    card.getAttribute('data-category-name'),
        dimensions:       card.getAttribute('data-dimensions'),
        selectedColor:    color.name,
        selectedColorId:  color.id,
        quantity:         qty,
    };

    cart[productId] = item;
    document.getElementById('btn-' + productId).textContent = '✓ In Order';
    document.getElementById('btn-' + productId).classList.add('in-order');
    updateCartUI();
}

function removeFromOrder(productId) {
    delete cart[productId];
    var btn = document.getElementById('btn-' + productId);
    if (btn) { btn.textContent = '+ Add to Order'; btn.classList.remove('in-order'); }
    updateCartUI();
}

// ── Cart UI ───────────────────────────────────────────────────────────────────
function updateCartUI() {
    var items   = Object.values(cart);
    var count   = items.reduce(function(s, i) { return s + i.quantity; }, 0);
    var subtotal= items.reduce(function(s, i) { return s + i.price * i.quantity; }, 0);
    var vatAmt  = subtotal * (VAT_RATE / 100);
    var total   = subtotal + vatAmt;

    var cartBar = document.getElementById('cart-bar');
    if (count > 0) {
        cartBar.classList.remove('hidden');
        document.getElementById('cart-count-label').textContent = count + ' item' + (count !== 1 ? 's' : '');
        document.getElementById('cart-total-label').textContent = 'Total: $' + total.toFixed(2) + ' (incl. VAT)';
        document.getElementById('nav-checkout-btn').style.display = 'flex';
        document.getElementById('nav-cart-count').textContent = count;
    } else {
        cartBar.classList.add('hidden');
        document.getElementById('nav-checkout-btn').style.display = 'none';
    }

    // Save to session storage for checkout page
    sessionStorage.setItem('omnishop_cart', JSON.stringify(cart));
    sessionStorage.setItem('omnishop_event', EVENT_SLUG);
}

function goToCheckout() {
    if (Object.keys(cart).length === 0) {
        alert('Your order is empty. Please add some items first.');
        return;
    }
    window.location.href = SITE_URL + '/' + EVENT_SLUG + '/checkout';
}

function clearAuth() {
    document.cookie = 'catalog_auth_' + EVENT_SLUG + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
}

// ── Sidebar active category tracking ─────────────────────────────────────────
function initSidebar() {
    var sidebar = document.getElementById('cat-sidebar');
    var main    = document.getElementById('catalog-main');
    if (!sidebar || !main) return;

    var links = sidebar.querySelectorAll('.cat-link');
    var sections = main.querySelectorAll('.catalog-section');

    // Click to scroll
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            var cat = this.getAttribute('data-cat');
            var section = document.getElementById('section-' + cat);
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Scroll spy
    main.addEventListener('scroll', function() {
        var scrollTop = main.scrollTop + 80;
        var current = '';
        sections.forEach(function(s) {
            if (s.offsetTop <= scrollTop) current = s.id.replace('section-', '');
        });
        links.forEach(function(l) {
            l.classList.toggle('active', l.getAttribute('data-cat') === current);
        });
    });

    // Set first as active
    if (links.length) links[0].classList.add('active');
}

// ── Restore cart from session storage ────────────────────────────────────────
function restoreCart() {
    var saved = sessionStorage.getItem('omnishop_cart');
    var savedEvent = sessionStorage.getItem('omnishop_event');
    if (saved && savedEvent === EVENT_SLUG) {
        try {
            cart = JSON.parse(saved);
            Object.keys(cart).forEach(function(productId) {
                var btn = document.getElementById('btn-' + productId);
                if (btn) { btn.textContent = '✓ In Order'; btn.classList.add('in-order'); }
            });
            updateCartUI();
        } catch(e) { cart = {}; }
    }
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    restoreCart();
});
