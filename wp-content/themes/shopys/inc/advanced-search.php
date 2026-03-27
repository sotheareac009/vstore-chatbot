<?php
/**
 * Advanced WooCommerce Product Search
 * AJAX-powered live search with product image, price, category, and SKU
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the search bar HTML
 */
function shopys_advanced_search_bar() {
    if ( ! class_exists( 'WooCommerce' ) ) return;
    ?>
    <div class="aps-wrapper" id="aps-wrapper">
        <form class="aps-form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" autocomplete="off">
            <div class="aps-input-group">
                <span class="aps-search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input
                    type="text"
                    class="aps-input"
                    id="aps-search-input"
                    name="s"
                    placeholder="Search products by name, SKU, or category..."
                    value=""
                />
                <input type="hidden" name="post_type" value="product" />
                <button type="submit" class="aps-submit-btn">Search</button>
            </div>
        </form>
        <!-- Live Results Dropdown -->
        <div class="aps-results" id="aps-results" style="display:none;">
            <div class="aps-results-inner" id="aps-results-inner"></div>
        </div>
        <!-- Overlay -->
        <div class="aps-overlay" id="aps-overlay" style="display:none;"></div>
    </div>
    <?php
}

/**
 * AJAX handler for live product search
 */
function shopys_ajax_product_search() {
    $query = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';

    if ( strlen( $query ) < 2 ) {
        wp_send_json( array( 'results' => array(), 'total' => 0 ) );
    }

    // Search by title and content
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $query,
        'posts_per_page' => 8,
    );

    $search_query = new WP_Query( $args );
    $results = array();
    $found_ids = array();

    if ( $search_query->have_posts() ) {
        while ( $search_query->have_posts() ) {
            $search_query->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product ) continue;
            $found_ids[] = get_the_ID();
            $results[] = shopys_format_search_result( $product );
        }
    }

    // Also search by SKU if less than 8 results
    if ( count( $results ) < 8 ) {
        $sku_args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 8 - count( $results ),
            'post__not_in'   => $found_ids,
            'meta_query'     => array(
                array(
                    'key'     => '_sku',
                    'value'   => $query,
                    'compare' => 'LIKE',
                ),
            ),
        );
        $sku_query = new WP_Query( $sku_args );
        if ( $sku_query->have_posts() ) {
            while ( $sku_query->have_posts() ) {
                $sku_query->the_post();
                $product = wc_get_product( get_the_ID() );
                if ( ! $product ) continue;
                $results[] = shopys_format_search_result( $product );
            }
        }
    }

    wp_reset_postdata();

    $total = $search_query->found_posts;
    wp_send_json( array(
        'results'    => $results,
        'total'      => $total,
        'query'      => $query,
        'search_url' => home_url( '/?s=' . urlencode( $query ) . '&post_type=product' ),
    ));
}
add_action( 'wp_ajax_shopys_product_search', 'shopys_ajax_product_search' );
add_action( 'wp_ajax_nopriv_shopys_product_search', 'shopys_ajax_product_search' );

/**
 * Format a single product for the search result
 */
function shopys_format_search_result( $product ) {
    $thumb = '';
    $thumb_id = $product->get_image_id();
    if ( $thumb_id ) {
        $thumb_src = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
        if ( $thumb_src ) {
            $thumb = $thumb_src[0];
        }
    }

    $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
    $cat_name = ! is_wp_error( $categories ) && ! empty( $categories ) ? $categories[0] : '';

    return array(
        'id'            => $product->get_id(),
        'title'         => $product->get_name(),
        'url'           => get_permalink( $product->get_id() ),
        'image'         => $thumb,
        'price_html'    => $product->get_price_html(),
        'regular_price' => $product->get_regular_price(),
        'sale_price'    => $product->get_sale_price(),
        'sku'           => $product->get_sku(),
        'category'      => $cat_name,
        'stock_status'  => $product->get_stock_status(),
    );
}

/**
 * Inject the search bar into the header via hook
 */
function shopys_inject_header_search() {
    shopys_advanced_search_bar();
}
add_action( 'open_shop_after_header', 'shopys_inject_header_search', 5 );
