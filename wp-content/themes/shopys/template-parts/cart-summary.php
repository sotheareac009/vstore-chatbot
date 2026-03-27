<?php
/**
 * Cart Summary Shortcode — [cart_summary]
 *
 * Layout:
 *   TOP  → Full-width items table
 *   BOTTOM-LEFT  → Action buttons (Checkout, Print Invoice, View Cart)
 *   BOTTOM-RIGHT → Totals (Subtotal + Total, no shipping)
 *
 * Usage: [cart_summary title="" show_checkout="true" show_print="true"]
 *
 * @package Shopys
 */

add_shortcode( 'cart_summary', 'shopys_cart_summary_shortcode' );

function shopys_cart_summary_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'show_checkout' => 'true',
        'show_print'    => 'true',
        'title'         => '',
    ), $atts, 'cart_summary' );

    if ( ! function_exists( 'WC' ) ) return '';

    WC()->cart->calculate_totals();
    $cart  = WC()->cart;
    $items = $cart->get_cart();

    ob_start();
    ?>
    <div class="shopys-cs"
         data-show-checkout="<?php echo esc_attr( $atts['show_checkout'] ); ?>"
         data-show-print="<?php echo esc_attr( $atts['show_print'] ); ?>"
         data-title="<?php echo esc_attr( $atts['title'] ); ?>">

        <?php if ( $atts['title'] ) : ?>
            <h3 class="shopys-cs__heading"><?php echo esc_html( $atts['title'] ); ?></h3>
        <?php endif; ?>

        <?php if ( $cart->is_empty() ) : ?>
        <!-- Empty cart -->
        <div class="shopys-cs__empty">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#13e800" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <p><?php esc_html_e( 'Your cart is empty.', 'shopys' ); ?></p>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="shopys-cs__shop-btn">
                <?php esc_html_e( 'Continue Shopping', 'shopys' ); ?>
            </a>
        </div>

        <?php else : ?>

        <!-- ── Products Table (full width) ── -->
        <div class="shopys-cs__table-wrap">
            <!-- Header -->
            <div class="shopys-cs__row shopys-cs__row--head">
                <div class="shopys-cs__col-product"><?php esc_html_e( 'Product', 'shopys' ); ?></div>
                <div class="shopys-cs__col-price"><?php esc_html_e( 'Price', 'shopys' ); ?></div>
                <div class="shopys-cs__col-qty"><?php esc_html_e( 'Qty', 'shopys' ); ?></div>
                <div class="shopys-cs__col-total"><?php esc_html_e( 'Subtotal', 'shopys' ); ?></div>
                <div class="shopys-cs__col-remove"></div>
            </div>

            <!-- Items -->
            <?php foreach ( $items as $cart_item_key => $item ) :
                $product    = $item['data'];
                $qty        = $item['quantity'];
                $thumb_id   = $product->get_image_id();
                $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : wc_placeholder_img_src();
                $price      = (float) wc_get_price_including_tax( $product );
                $line_total = $price * $qty;
                $sku        = $product->get_sku();
                $permalink  = get_permalink( $product->get_id() );
                $remove_url = wc_get_cart_url( array(
                    'remove_item' => $cart_item_key,
                    '_wpnonce'    => wp_create_nonce( 'woocommerce-cart' ),
                ) );
            ?>
            <div class="shopys-cs__row shopys-cs__row--item">
                <div class="shopys-cs__col-product">
                    <a href="<?php echo esc_url( $permalink ); ?>" class="shopys-cs__thumb-link">
                        <img class="shopys-cs__thumb" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy"/>
                    </a>
                    <div class="shopys-cs__meta">
                        <a href="<?php echo esc_url( $permalink ); ?>" class="shopys-cs__name"><?php echo esc_html( $product->get_name() ); ?></a>
                        <?php if ( $sku ) : ?><span class="shopys-cs__sku">SKU: <?php echo esc_html( $sku ); ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="shopys-cs__col-price"><?php echo wc_price( $price ); ?></div>
                <div class="shopys-cs__col-qty">
                    <span class="shopys-cs__qty-badge"><?php echo esc_html( $qty ); ?></span>
                </div>
                <div class="shopys-cs__col-total"><?php echo wc_price( $line_total ); ?></div>
                <div class="shopys-cs__col-remove">
                    <button class="shopys-cs__remove-btn"
                            data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
                            data-nonce="<?php echo wp_create_nonce( 'shopys_cart_nonce' ); ?>"
                            title="<?php esc_attr_e( 'Remove item', 'shopys' ); ?>"
                            aria-label="<?php esc_attr_e( 'Remove item', 'shopys' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div><!-- /.shopys-cs__table-wrap -->

        <!-- ── Bottom Row: Buttons (left) + Totals (right) ── -->
        <div class="shopys-cs__bottom">

            <!-- Left: Action Buttons -->
            <div class="shopys-cs__actions">
                <?php if ( $atts['show_checkout'] === 'true' ) : ?>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="shopys-cs__checkout-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    <?php esc_html_e( 'Proceed to Checkout', 'shopys' ); ?>
                </a>
                <?php endif; ?>

                <?php if ( $atts['show_print'] === 'true' ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'shopys_cart_invoice', '1', home_url( '/' ) ) ); ?>"
                   target="_blank" rel="noopener" class="shopys-cs__print-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    <?php esc_html_e( 'Print Invoice', 'shopys' ); ?>
                </a>
                <?php endif; ?>

                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="shopys-cs__cart-link">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <?php esc_html_e( 'View Full Cart', 'shopys' ); ?>
                </a>
            </div>

            <!-- Right: Totals (no shipping) -->
            <div class="shopys-cs__totals">
                <div class="shopys-cs__totals-row">
                    <span><?php esc_html_e( 'Subtotal', 'shopys' ); ?></span>
                    <span><?php echo $cart->get_cart_subtotal(); ?></span>
                </div>
                <?php if ( $cart->get_discount_total() ) : ?>
                <div class="shopys-cs__totals-row shopys-cs__totals-row--discount">
                    <span><?php esc_html_e( 'Discount', 'shopys' ); ?></span>
                    <span>-<?php echo wc_price( $cart->get_discount_total() ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( wc_tax_enabled() && $cart->get_taxes_total() ) : ?>
                <div class="shopys-cs__totals-row">
                    <span><?php esc_html_e( 'Tax', 'shopys' ); ?></span>
                    <span><?php echo wc_price( $cart->get_taxes_total() ); ?></span>
                </div>
                <?php endif; ?>
                <div class="shopys-cs__totals-total">
                    <span><?php esc_html_e( 'Total', 'shopys' ); ?></span>
                    <span><?php echo $cart->get_total(); ?></span>
                </div>
            </div>

        </div><!-- /.shopys-cs__bottom -->

        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ── AJAX: Remove item from cart ──────────────────────────────────
add_action( 'wp_ajax_shopys_remove_cart_item',        'shopys_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_shopys_remove_cart_item', 'shopys_ajax_remove_cart_item' );

function shopys_ajax_remove_cart_item() {
    check_ajax_referer( 'shopys_cart_nonce', 'nonce' );

    $key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
    if ( ! $key || ! function_exists( 'WC' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request' ) );
    }

    $atts = array(
        'show_checkout' => sanitize_text_field( wp_unslash( $_POST['show_checkout'] ?? 'true' ) ),
        'show_print'    => sanitize_text_field( wp_unslash( $_POST['show_print']    ?? 'true' ) ),
        'title'         => sanitize_text_field( wp_unslash( $_POST['title']         ?? '' ) ),
    );

    if ( WC()->cart->remove_cart_item( $key ) ) {
        WC()->cart->calculate_totals();
        wp_send_json_success( array(
            'html'  => shopys_cart_summary_shortcode( $atts ),
            'count' => WC()->cart->get_cart_contents_count(),
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Could not remove item' ) );
    }
}

// ── AJAX: Refresh cart summary (called after add-to-cart) ────────
add_action( 'wp_ajax_shopys_refresh_cart_summary',        'shopys_ajax_refresh_cart_summary' );
add_action( 'wp_ajax_nopriv_shopys_refresh_cart_summary', 'shopys_ajax_refresh_cart_summary' );

function shopys_ajax_refresh_cart_summary() {
    check_ajax_referer( 'shopys_cart_nonce', 'nonce' );
    if ( ! function_exists( 'WC' ) ) wp_send_json_error();

    WC()->cart->calculate_totals();

    $atts = array(
        'show_checkout' => sanitize_text_field( wp_unslash( $_POST['show_checkout'] ?? 'true' ) ),
        'show_print'    => sanitize_text_field( wp_unslash( $_POST['show_print']    ?? 'true' ) ),
        'title'         => sanitize_text_field( wp_unslash( $_POST['title']         ?? '' ) ),
    );

    wp_send_json_success( array(
        'html'  => shopys_cart_summary_shortcode( $atts ),
        'count' => WC()->cart->get_cart_contents_count(),
    ) );
}

// ── Enqueue CSS + JS ───────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'shopys_cart_summary_assets' );
function shopys_cart_summary_assets() {
    wp_enqueue_style(
        'shopys-cart-summary',
        get_stylesheet_directory_uri() . '/css/cart-summary.css',
        array(),
        filemtime( get_stylesheet_directory() . '/css/cart-summary.css' )
    );
    wp_enqueue_script(
        'shopys-cart-summary',
        get_stylesheet_directory_uri() . '/js/cart-summary.js',
        array(),
        filemtime( get_stylesheet_directory() . '/js/cart-summary.js' ),
        true
    );
    wp_localize_script( 'shopys-cart-summary', 'shopysCsParams', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'shopys_cart_nonce' ),
    ) );
}
