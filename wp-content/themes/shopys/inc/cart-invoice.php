<?php
/**
 * Cart Invoice — Print Button + Printable Invoice Page
 *
 * Adds a "Print Invoice" button to the WooCommerce cart page.
 * Clicking it opens a premium styled printable invoice in a new window.
 *
 * @package Shopys
 */

// ── 1. Enqueue button CSS on cart page only ────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'shopys_cart_invoice_assets' );
function shopys_cart_invoice_assets() {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) return;
    wp_enqueue_style(
        'shopys-cart-invoice',
        get_stylesheet_directory_uri() . '/css/cart-invoice.css',
        array(),
        filemtime( get_stylesheet_directory() . '/css/cart-invoice.css' )
    );
}

// ── 2. Add Print Invoice button — works for BOTH Blocks cart and legacy cart ───

// Legacy shortcode cart fallback
add_action( 'woocommerce_proceed_to_checkout', 'shopys_cart_print_button', 5 );

function shopys_cart_print_button() {
    $invoice_url = add_query_arg( 'shopys_cart_invoice', '1', home_url( '/' ) );
    ?>
    <a href="<?php echo esc_url( $invoice_url ); ?>"
       target="_blank"
       rel="noopener"
       class="shopys-print-btn"
       id="shopys-print-invoice-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
        </svg>
        <?php esc_html_e( 'Print Invoice', 'shopys' ); ?>
    </a>
    <?php
}

// Blocks cart — inject via JS in wp_footer
add_action( 'wp_footer', 'shopys_cart_print_button_blocks' );
function shopys_cart_print_button_blocks() {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) return;
    $invoice_url = add_query_arg( 'shopys_cart_invoice', '1', home_url( '/' ) );
    $label       = esc_js( __( 'Print Invoice', 'shopys' ) );
    ?>
    <script>
    (function () {
        var invoiceUrl = <?php echo json_encode( esc_url( $invoice_url ) ); ?>;
        var label      = <?php echo json_encode( __( 'Print Invoice', 'shopys' ) ); ?>;

        function buildBtn() {
            var btn = document.createElement('a');
            btn.href      = invoiceUrl;
            btn.target    = '_blank';
            btn.rel       = 'noopener';
            btn.id        = 'shopys-print-invoice-btn';
            btn.className = 'shopys-print-btn';
            btn.innerHTML =
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<polyline points="6 9 6 2 18 2 18 9"/>' +
                '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>' +
                '<rect x="6" y="14" width="12" height="8"/>' +
                '</svg> ' + label;
            return btn;
        }

        function inject() {
            // Already injected?
            if (document.getElementById('shopys-print-invoice-btn')) return;

            // WooCommerce Blocks cart: look for the checkout button wrapper
            var targets = [
                '.wc-block-cart__submit-container',   // Blocks cart submit area
                '.wc-block-components-checkout-place-order-button', // alt
                '.wp-block-woocommerce-cart .wc-block-cart__submit', // older blocks
                '.woocommerce-cart .wc-proceed-to-checkout',          // legacy
            ];

            for (var i = 0; i < targets.length; i++) {
                var el = document.querySelector(targets[i]);
                if (el) {
                    el.insertAdjacentElement('beforebegin', buildBtn());
                    return;
                }
            }
        }

        // Try immediately, then watch for Blocks to render
        inject();

        var observer = new MutationObserver(function () {
            inject();
        });
        observer.observe(document.body, { childList: true, subtree: true });

        // Stop watching after 10s (Blocks should have rendered by then)
        setTimeout(function () { observer.disconnect(); }, 10000);
    })();
    </script>
    <?php
}



// ── 3. Intercept request and render the invoice ────────────────────────────────
add_action( 'template_redirect', 'shopys_render_cart_invoice' );
function shopys_render_cart_invoice() {
    if ( ! isset( $_GET['shopys_cart_invoice'] ) || $_GET['shopys_cart_invoice'] !== '1' ) {
        return;
    }
    if ( ! function_exists( 'WC' ) || WC()->cart->is_empty() ) {
        wp_die( __( 'Your cart is empty.', 'shopys' ), '', array( 'response' => 200 ) );
    }

    $cart        = WC()->cart;
    $items       = $cart->get_cart();
    $shop_name   = get_bloginfo( 'name' );
    $shop_url    = home_url( '/' );
    $logo_id     = get_theme_mod( 'custom_logo' );
    $logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
    $date        = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
    $invoice_no  = 'CART-' . strtoupper( substr( md5( session_id() . time() ), 0, 8 ) );

    // Shop address — pull from WooCommerce store settings, fallback to hardcoded
    $wc_address  = trim( get_option( 'woocommerce_store_address', '' ) );
    $wc_address2 = trim( get_option( 'woocommerce_store_address_2', '' ) );
    $wc_city     = trim( get_option( 'woocommerce_store_city', '' ) );
    $wc_postcode = trim( get_option( 'woocommerce_store_postcode', '' ) );
    if ( $wc_address ) {
        $shop_address_parts = array_filter( [ $wc_address, $wc_address2, $wc_city, $wc_postcode ] );
        $shop_address = implode( ', ', $shop_address_parts );
    } else {
        $shop_address = 'Street 271, Front of Psa Hengly';
    }

    // Customer
    $user        = wp_get_current_user();
    $customer    = $user->ID ? $user->display_name : __( 'Guest', 'shopys' );
    $customer_email = $user->ID ? $user->user_email : '';

    $item_count  = 0;
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo esc_attr( get_locale() ); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html( $shop_name ); ?> — <?php esc_html_e( 'Invoice', 'shopys' ); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                font-size: 13px;
                color: #1a1a1a;
                background: #eef0f3;
                line-height: 1.5;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .inv {
                max-width: 760px;
                margin: 32px auto;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 8px 48px rgba(0,0,0,0.12);
                overflow: hidden;
                display: flex;
                flex-direction: column;
                min-height: calc(100vh - 64px);
            }
            /* The table section grows to push footer down */
            .inv-table-grow {
                flex: 1;
            }
            /* Pills Row */
            .inv-pills {
                display: flex;
                justify-content: space-between;
                padding: 16px 36px;
                background: #fff;
                border-bottom: 1px solid #f0f0f0;
            }
            .inv-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 11px;
                font-weight: 600;
                color: #fff;
                background: #13e800;
                border-radius: 50px;
                padding: 5px 14px;
            }
            .inv-pill span { opacity: 0.75; font-weight: 400; }
            /* Hero */
            .inv-hero { padding: 28px 36px 0; }
            .inv-hero-title {
                font-size: 52px;
                font-weight: 800;
                letter-spacing: -2.5px;
                color: #111;
                line-height: 1;
                margin-bottom: 20px;
            }
            /* Customer + From */
            .inv-info-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 24px;
                padding: 18px 36px;
                background: #f8fbf8;
                border-top: 1px solid #e8f5e8;
                border-bottom: 1px solid #e8f5e8;
            }
            .inv-to-label, .inv-payment-label {
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #13e800;
                margin-bottom: 4px;
            }
            .inv-customer-name { font-size: 14px; font-weight: 700; color: #111; }
            .inv-customer-sub { font-size: 11px; color: #888; margin-top: 2px; }
            .inv-payment-block { text-align: right; }
            .inv-payment-value { font-size: 11.5px; color: #555; line-height: 1.6; }
            .inv-grand-amount {
                font-size: 28px; font-weight: 800; color: #111;
                margin-top: 4px; letter-spacing: -1px;
            }
            /* Table */
            .inv-table { width: 100%; border-collapse: collapse; }
            .inv-table thead tr { background: #13e800; }
            .inv-table thead th {
                font-size: 11px; font-weight: 700; text-transform: uppercase;
                letter-spacing: 0.8px; color: #fff; padding: 12px 20px; text-align: left;
            }
            .inv-table thead th:first-child { width: 44px; text-align: center; }
            .inv-table thead th:nth-child(3),
            .inv-table thead th:nth-child(4),
            .inv-table thead th:last-child { text-align: right; }
            .inv-table tbody tr { border-bottom: 1px solid #f2f2f2; }
            .inv-table tbody tr:nth-child(even) { background: #fafafa; }
            .inv-table tbody tr:last-child { border-bottom: none; }
            .inv-table tbody td {
                padding: 13px 20px; font-size: 12.5px;
                vertical-align: middle; color: #333;
            }
            .inv-table tbody td:first-child {
                text-align: center; font-size: 11px;
                font-weight: 700; color: #13e800;
            }
            .inv-table tbody td:nth-child(3),
            .inv-table tbody td:nth-child(4),
            .inv-table tbody td:last-child { text-align: right; }
            .inv-product-cell { display: flex; align-items: center; gap: 10px; }
            .inv-product-img {
                width: 36px; height: 36px; object-fit: cover;
                border-radius: 6px; border: 1px solid #eee; flex-shrink: 0;
            }
            .inv-product-no-img {
                width: 36px; height: 36px; border-radius: 6px;
                background: #f0f0f0; display: flex; align-items: center;
                justify-content: center; flex-shrink: 0;
            }
            .inv-product-name { font-weight: 600; color: #111; }
            .inv-product-sku { font-size: 10px; color: #bbb; margin-top: 1px; }
            .inv-subtotal-strong { font-weight: 700; color: #111; }
            /* Footer */
            .inv-footer {
                background: #1c2b1c;
                color: #fff;
                display: flex;
                margin-top: auto;
            }
            .inv-footer-left {
                flex: 1; padding: 28px 36px;
                border-right: 1px solid rgba(255,255,255,0.07);
            }
            .inv-footer-right { width: 260px; padding: 28px 36px; }
            .inv-footer-section-label {
                font-size: 10px; font-weight: 700; text-transform: uppercase;
                letter-spacing: 1px; color: #13e800; margin-bottom: 8px;
            }
            .inv-terms-text {
                font-size: 11px; color: rgba(255,255,255,0.5);
                line-height: 1.7; margin-bottom: 20px;
            }
            .inv-contact-item {
                font-size: 11px; color: rgba(255,255,255,0.55);
                margin-bottom: 4px; display: flex; align-items: center; gap: 6px;
            }
            .inv-totals-row {
                display: flex; justify-content: space-between; align-items: center;
                padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12px;
            }
            .inv-totals-row:last-of-type { border-bottom: none; }
            .inv-totals-label { color: rgba(255,255,255,0.5); font-weight: 500; }
            .inv-totals-value { color: #fff; font-weight: 600; }
            .inv-total-final {
                display: flex; justify-content: space-between; align-items: center;
                margin-top: 12px; padding-top: 12px;
                border-top: 1px solid rgba(19,232,0,0.3);
            }
            .inv-total-final-label {
                font-size: 12px; font-weight: 700; color: #13e800;
                text-transform: uppercase; letter-spacing: 0.5px;
            }
            .inv-total-final-value { font-size: 22px; font-weight: 800; color: #fff; }
            /* Sig row */
            .inv-sig-row {
                background: #152415; padding: 14px 36px;
                display: flex; justify-content: space-between; align-items: center;
                border-top: 1px solid rgba(255,255,255,0.04);
            }
            .inv-generated { font-size: 10px; color: rgba(255,255,255,0.28); }
            .inv-note-pill {
                font-size: 10px; color: rgba(19,232,0,0.65);
                background: rgba(19,232,0,0.07);
                border: 1px solid rgba(19,232,0,0.18);
                border-radius: 50px; padding: 3px 12px;
            }
            /* Print button */
            .inv-print-wrap { padding: 20px 36px; background: #fff; }
            .inv-print-btn {
                display: flex; align-items: center; justify-content: center; gap: 8px;
                width: 100%; padding: 13px; background: #13e800; color: #0a2200;
                font-family: inherit; font-size: 13px; font-weight: 700;
                letter-spacing: 0.3px; border: none; border-radius: 8px;
                cursor: pointer; transition: background 0.2s;
            }
            .inv-print-btn:hover { background: #0fcc00; }
            @media print {
                @page {
                    margin: 0; /* removes browser URL header + page-number footer */
                }
                body { background: #fff; padding: 0; }
                .inv {
                    box-shadow: none;
                    border-radius: 0;
                    margin: 0;
                    max-width: 100%;
                    min-height: 100vh;
                }
                .inv-print-wrap { display: none !important; }
                .inv-footer, .inv-sig-row, .inv-table thead tr {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
    <div class="inv">

        <!-- Pills -->
        <div class="inv-pills">
            <div class="inv-pill">
                <?php esc_html_e( 'Invoice Date', 'shopys' ); ?> —
                <span style="font-weight: 700; color: #fff; "><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></span>
            </div>
            <div class="inv-pill">
                <?php esc_html_e( 'Invoice ID', 'shopys' ); ?> —
                <span style="font-weight: 700; color: #fff; "><?php echo esc_html( $invoice_no ); ?></span>
            </div>

        </div>

        <!-- Hero -->
        <div class="inv-hero">
            <div class="inv-hero-title">INVOICE</div>
        </div>

        <!-- Customer + From -->
        <div class="inv-info-row">
            <div>
                <div class="inv-to-label">To:</div>
                <!-- <div class="inv-customer-name"><?php echo esc_html( $customer ); ?></div>
                <?php if ( $customer_email ) : ?>
                    <div class="inv-customer-sub"><?php echo esc_html( $customer_email ); ?></div>
                <?php endif; ?>
                <div class="inv-customer-sub"><?php echo esc_html( $shop_address ); ?></div> -->
            </div>
            <div class="inv-payment-block">
                <div class="inv-payment-label">From:</div>
                <div class="inv-payment-value">
                    <strong><?php echo esc_html( $shop_name ); ?></strong><br>
                    <?php echo esc_url( $shop_url ); ?>
                </div>
                <!-- <div class="inv-grand-amount"><?php echo $cart->get_total(); ?></div> -->
            </div>
        </div>

        <!-- Table -->
        <div class="inv-table-grow">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th><?php esc_html_e( 'Item Name', 'shopys' ); ?></th>
                    <th><?php esc_html_e( 'Qty', 'shopys' ); ?></th>
                    <th><?php esc_html_e( 'Unit Price', 'shopys' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'shopys' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) :
                    $item_count++;
                    $product    = $item['data'];
                    $thumb_id   = $product->get_image_id();
                    $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
                    $sku        = $product->get_sku();
                    $qty        = $item['quantity'];
                    $price      = wc_get_price_including_tax( $product );
                    $line_total = $price * $qty;
                ?>
                <tr>
                    <td><?php echo $item_count; ?></td>
                    <td>
                        <div class="inv-product-cell">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" class="inv-product-img" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
                            <?php else : ?>
                                <div class="inv-product-no-img">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="inv-product-name"><?php echo esc_html( $product->get_name() ); ?></div>
                                <?php if ( $sku ) : ?><div class="inv-product-sku">SKU: <?php echo esc_html( $sku ); ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html( $qty ); ?></td>
                    <td><?php echo wc_price( $price ); ?></td>
                    <td class="inv-subtotal-strong"><?php echo wc_price( $line_total ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div><!-- /.inv-table-grow -->

        <!-- Footer -->
        <div class="inv-footer">
            <div class="inv-footer-left">
                <div class="inv-footer-section-label"><?php esc_html_e( 'Terms and Condition', 'shopys' ); ?></div>
                <div class="inv-terms-text">
                    <?php esc_html_e( 'All invoices must be paid within 30 days from the date of the invoice unless otherwise agreed upon in writing. Late payments may incur additional charges.', 'shopys' ); ?>
                </div>
                <div class="inv-footer-section-label"><?php esc_html_e( 'Contact Us:', 'shopys' ); ?></div>
                <?php if ( $customer_email ) : ?>
                <div class="inv-contact-item">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <!-- <?php echo esc_html( $customer_email ); ?> -->
                    vtech520@gmail.com
                </div>
                <?php endif; ?>
                <div class="inv-contact-item">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    -Sales : +855 92 77 55 49 | +855 98 86 86 89
                </div>
                <div class="inv-contact-item">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo esc_html( $shop_address ); ?>
                </div>
            </div>
            <div class="inv-footer-right">
                <div class="inv-footer-section-label" style="margin-bottom:12px;"><?php esc_html_e( 'Summary', 'shopys' ); ?></div>
                <div class="inv-totals-row">
                    <span class="inv-totals-label"><?php esc_html_e( 'Sub Total', 'shopys' ); ?></span>
                    <span class="inv-totals-value"><?php echo $cart->get_cart_subtotal(); ?></span>
                </div>
                <div class="inv-totals-row" id="booking-summary-row" style="display: none;">
                    <span class="inv-totals-label"><?php esc_html_e( 'Booking', 'shopys' ); ?></span>
                    <span class="inv-totals-value" id="display-booking"></span>
                </div>
                <?php if ( $cart->get_cart_shipping_total() ) : ?>
                <div class="inv-totals-row">
                    <span class="inv-totals-label"><?php esc_html_e( 'Shipping', 'shopys' ); ?></span>
                    <!-- <span class="inv-totals-value"><?php echo $cart->get_cart_shipping_total(); ?></span> -->
                    <span class="inv-totals-value">2$</span>
                </div>
                <?php endif; ?>
                <?php if ( wc_tax_enabled() && $cart->get_taxes_total() ) : ?>
                <div class="inv-totals-row">
                    <span class="inv-totals-label"><?php printf( esc_html__( 'Tax (%s%%)', 'shopys' ), '' ); ?></span>
                    <span class="inv-totals-value"><?php echo wc_price( $cart->get_taxes_total() ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $cart->get_discount_total() ) : ?>
                <div class="inv-totals-row">
                    <span class="inv-totals-label"><?php esc_html_e( 'Discount', 'shopys' ); ?></span>
                    <span class="inv-totals-value" style="color:#ff6b6b;">-<?php echo wc_price( $cart->get_discount_total() ); ?></span>
                </div>
                <?php endif; ?>
                <div class="inv-total-final">
                    <span class="inv-total-final-label"><?php esc_html_e( 'Total', 'shopys' ); ?></span>
                    <span class="inv-total-final-value"><?php echo $cart->get_total(); ?></span>
                </div>
            </div>
        </div>

        <!-- Sig Row -->
        <div class="inv-sig-row">
            <div class="inv-generated"><?php echo esc_html( $shop_name ); ?> &mdash; <?php echo esc_html( $date ); ?></div>
            <div class="inv-note-pill"><?php esc_html_e( 'Cart Summary — Not a confirmed order', 'shopys' ); ?></div>
        </div>

        <!-- Print Button -->
        <div class="inv-print-wrap">
            <div style="margin-bottom: 12px;">
                <input type="text" id="booking-input" placeholder="<?php esc_attr_e( 'Customer Booking...', 'shopys' ); ?>" style="width: 100%; padding: 12px 14px; border: 1px solid #ccc; border-radius: 8px; font-family: inherit; font-size: 13px; outline: none;" oninput="
                    var row = document.getElementById('booking-summary-row');
                    var span = document.getElementById('display-booking');
                    if (this.value.trim() !== '') {
                        row.style.display = 'flex';
                        span.innerText = this.value;
                    } else {
                        row.style.display = 'none';
                        span.innerText = '';
                    }
                ">
            </div>
            <button class="inv-print-btn" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <?php esc_html_e( 'Print / Save as PDF', 'shopys' ); ?>
            </button>
        </div>

    </div>
    <script>
        // Removed the auto-print so you have time to paste the booking ID.
        // window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 700); });
        
        // Auto-focus the booking input so you can paste immediately when the window opens.
        window.addEventListener('load', function () {
            document.getElementById('booking-input').focus();
        });
    </script>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}
