<?php
/**
 * Featured Product Grid Shortcode
 * Usage: [featured_products limit="12" columns="4" filter="true" cart="true" pagination_type="normal" category="" filter_tabs=""]
 *
 * Shows only WooCommerce "Featured" products.
 *
 * @package Shopys
 */

function featured_product_grid_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit'            => 12,
        'columns'          => 4,
        'category'         => '',
        'filter_tabs'      => '',
        'orderby'          => 'date',
        'order'            => 'DESC',
        'filter'           => 'false',
        'cart'             => 'true',
        'pagination_type'  => 'normal',
        'show_description' => 'true',
        'listing_type'                  => 'grid',
        'show_product_listing_header'   => 'true',
    ), $atts, 'featured_products' );

    $atts['listing_type'] = ( trim( strtolower( $atts['listing_type'] ) ) === 'table' ) ? 'table' : 'grid';

    $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['limit'] ),
        'paged'          => $paged,
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'order'          => sanitize_text_field( $atts['order'] ),
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ),
        ),
    );

    // Category filter
    if ( ! empty( $atts['category'] ) ) {
        $args['tax_query']['relation'] = 'AND';
        $args['tax_query'][] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => array_map( 'trim', explode( ',', $atts['category'] ) ),
            'include_children' => true,
        );
    }

    // Get categories for filter tab buttons
    $has_filter_tabs = ! empty( $atts['filter_tabs'] );
    $cat_args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    );

    if ( $has_filter_tabs ) {
        $cat_args['slug'] = array_map( 'trim', explode( ',', $atts['filter_tabs'] ) );
        wp_cache_delete( 'get_terms', 'terms' );
        delete_transient( 'wc_product_loop' );
        if ( function_exists( 'wc_update_product_cat_counts' ) ) {
            wc_update_product_cat_counts();
        }
    } elseif ( ! empty( $atts['category'] ) ) {
        $cat_args['slug'] = array_map( 'trim', explode( ',', $atts['category'] ) );
    }

    $product_categories = get_terms( $cat_args );

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <div class="ppg-container"
         data-shortcode="featured_products"
         data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
         data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
         data-category="<?php echo esc_attr( $atts['category'] ); ?>"
         data-filter-tabs="<?php echo esc_attr( $atts['filter_tabs'] ); ?>"
         data-orderby="<?php echo esc_attr( $atts['orderby'] ); ?>"
         data-order="<?php echo esc_attr( $atts['order'] ); ?>"
         data-filter="<?php echo esc_attr( $atts['filter'] ); ?>"
         data-cart="<?php echo esc_attr( $atts['cart'] ); ?>"
         data-show-description="<?php echo esc_attr( $atts['show_description'] ); ?>"
         data-pagination-type="<?php echo esc_attr( $atts['pagination_type'] ); ?>"
         data-listing-type="<?php echo esc_attr( $atts['listing_type'] ); ?>">

        <?php if ( $atts['filter'] === 'true' && ! is_wp_error( $product_categories ) && ! empty( $product_categories ) ) : ?>
        <div class="ppg-filter-bar">
            <button class="ppg-filter-btn active" data-category="all">
                <span class="ppg-filter-icon">⭐</span> All
            </button>
            <?php foreach ( $product_categories as $cat ) : ?>
            <button class="ppg-filter-btn" data-category="<?php echo esc_attr( $cat->slug ); ?>">
                <?php echo esc_html( $cat->name ); ?>
                <span class="ppg-filter-count"><?php echo esc_html( $cat->count ); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $query->have_posts() ) : ?>

        <?php if ( $atts['pagination_type'] === 'infinite' ) : ?>
        <div class="ppg-infinite-status"
             data-page="1"
             data-max="<?php echo esc_attr( $query->max_num_pages ); ?>"
             data-category="<?php echo esc_attr( $atts['category'] ); ?>"
             data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
             data-orderby="<?php echo esc_attr( $atts['orderby'] ); ?>"
             data-order="<?php echo esc_attr( $atts['order'] ); ?>"></div>
        <?php endif; ?>


        <?php if ( $atts['listing_type'] === 'table' ) : ?>

        <!-- ── TABLE LISTING ── -->
        <div class="ppg-table-wrap">
            <table class="ppg-list-table">
                <?php if ( trim( strtolower( $atts['show_product_listing_header'] ) ) !== 'false' ) : ?>
                <thead>
                    <tr>
                        <th class="ppg-lt-col-num">#</th>
                        <th class="ppg-lt-col-product"><?php esc_html_e( 'Product', 'shopys' ); ?></th>
                        <th class="ppg-lt-col-price"><?php esc_html_e( 'Price', 'shopys' ); ?></th>
                        <?php if ( $atts['cart'] === 'true' ) : ?>
                        <th class="ppg-lt-col-action"><?php esc_html_e( 'Action', 'shopys' ); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <?php endif; ?>
                <tbody>
                <?php
                $row_num = 0;
                while ( $query->have_posts() ) : $query->the_post();
                    $product  = wc_get_product( get_the_ID() );
                    if ( ! $product ) continue;
                    $row_num++;
                    $thumb_id   = $product->get_image_id();
                    $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
                    $large_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : wc_placeholder_img_src();
                    $sku        = $product->get_sku();
                    $cats       = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
                    $cat_data   = implode( ' ', ( ! is_wp_error( $cats ) ? $cats : array() ) );
                    $short_desc = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
                    $price_html = $product->get_price_html();
                ?>
                    <tr class="ppg-lt-row" data-categories="<?php echo esc_attr( $cat_data ); ?>"
                        data-qv-image="<?php echo esc_url( $large_url ); ?>"
                        data-qv-name="<?php echo esc_attr( $product->get_name() ); ?>"
                        data-qv-price="<?php echo esc_attr( $price_html ); ?>"
                        data-qv-desc="<?php echo esc_attr( wp_trim_words( $short_desc, 35, '…' ) ); ?>"
                        data-qv-url="<?php echo esc_url( get_permalink() ); ?>">
                        <td class="ppg-lt-col-num"><?php echo $row_num; ?></td>
                        <td class="ppg-lt-col-product">
                            <div class="ppg-lt-product">
                                <?php if ( $thumb_url ) : ?>
                                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-lt-thumb-link">
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" class="ppg-lt-thumb" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
                                    </a>
                                <?php endif; ?>
                                <div class="ppg-lt-meta">
                                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-lt-name"><?php echo esc_html( $product->get_name() ); ?></a>
                                    <?php if ( $sku ) : ?>
                                        <span class="ppg-lt-sku">SKU: <?php echo esc_html( $sku ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( ! $product->is_in_stock() ) : ?>
                                        <span class="ppg-lt-oos"><?php esc_html_e( 'Out of stock', 'shopys' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="ppg-lt-col-price">
                            <?php if ( $product->is_on_sale() ) : ?>
                                <span class="ppg-lt-price-old"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                                <span class="ppg-lt-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                            <?php else : ?>
                                <span class="ppg-lt-price"><?php echo $price_html; ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ( $atts['cart'] === 'true' ) : ?>
                        <td class="ppg-lt-col-action">
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        </td>
                        <?php endif; ?>
                        <td class="ppg-lt-col-qv">
                            <button class="ppg-qv-btn" aria-label="<?php esc_attr_e( 'Quick View', 'shopys' ); ?>">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <?php esc_html_e( 'View', 'shopys' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Quick View Modal ── -->
        <div class="ppg-qv-overlay" id="ppg-qv-modal" role="dialog" aria-modal="true" aria-label="Quick View" hidden>
            <div class="ppg-qv-modal">
                <button class="ppg-qv-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                <div class="ppg-qv-body">
                    <div class="ppg-qv-image-wrap">
                        <img class="ppg-qv-image" src="" alt="">
                    </div>
                    <div class="ppg-qv-info">
                        <h3 class="ppg-qv-name"></h3>
                        <div class="ppg-qv-price"></div>
                        <p class="ppg-qv-desc"></p>
                        <a class="ppg-qv-link" href="#"><?php esc_html_e( 'View Full Details →', 'shopys' ); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <?php else : ?>

        <!-- ── GRID LISTING ── -->
        <div class="ppg-grid ppg-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
        <?php while ( $query->have_posts() ) : $query->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product ) continue;

            $thumb_id   = $product->get_image_id();
            $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
            $cats       = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
            $cat_data   = implode( ' ', ( ! is_wp_error( $cats ) ? $cats : array() ) );
            $regular    = (float) $product->get_regular_price();
            $sale       = (float) $product->get_sale_price();
            $pct        = ( $product->is_on_sale() && $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
            $sku        = $product->get_sku();
            $short_desc = '';
            $specs      = array();
            if ( $atts['show_description'] !== 'false' ) {
                $short_desc = $product->get_short_description();
                if ( empty( $short_desc ) ) $short_desc = $product->get_description();
                if ( $short_desc ) {
                    $desc_clean = wp_strip_all_tags( $short_desc );
                    $raw_specs  = preg_split( '/[.\n]+/', $desc_clean, -1, PREG_SPLIT_NO_EMPTY );
                    foreach ( $raw_specs as $s ) {
                        $s = trim( $s );
                        if ( ! empty( $s ) && strlen( $s ) > 2 ) $specs[] = $s;
                    }
                }
            }
        ?>
            <div class="ppg-card" data-categories="<?php echo esc_attr( $cat_data ); ?>">
                <?php if ( $product->is_on_sale() || ! $product->is_in_stock() ) : ?>
                <div class="ppg-badges">
                    <?php if ( $product->is_on_sale() ) : ?>
                        <span class="ppg-badge ppg-badge-sale">-<?php echo esc_html( $pct ); ?>%</span>
                    <?php endif; ?>
                    <?php if ( ! $product->is_in_stock() ) : ?>
                        <span class="ppg-badge ppg-badge-oos"><?php esc_html_e( 'Sold Out', 'shopys' ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-image-link">
                    <div class="ppg-image-wrapper">
                        <?php if ( $thumb_url ) : ?>
                            <img class="ppg-image" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
                        <?php else : ?>
                            <div class="ppg-no-image">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="ppg-info">
                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-title-link">
                        <h3 class="ppg-title"><?php echo esc_html( $product->get_name() ); ?></h3>
                    </a>
                    <div class="ppg-price-row">
                        <?php if ( $product->is_on_sale() ) : ?>
                            <span class="ppg-price-regular"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                            <span class="ppg-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                        <?php else : ?>
                            <span class="ppg-price-current"><?php echo $product->get_price_html(); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $specs ) ) : ?>
                    <div class="ppg-specs"><ul class="ppg-specs-list">
                        <?php foreach ( $specs as $spec ) : ?>
                        <li><?php echo esc_html( $spec ); ?></li>
                        <?php endforeach; ?>
                    </ul></div>
                    <?php endif; ?>
                    <?php if ( $sku ) : ?>
                    <div class="ppg-sku"><span class="ppg-sku-separator"></span><?php esc_html_e( 'Code', 'shopys' ); ?> : <?php echo esc_html( $sku ); ?></div>
                    <?php endif; ?>
                    <?php if ( $atts['cart'] === 'true' ) : ?>
                    <div class="ppg-actions"><?php woocommerce_template_loop_add_to_cart(); ?></div>
                    <?php else : ?>
                    <div class="ppg-actions"><a href="<?php echo esc_url( get_permalink() ); ?>" class="button ppg-view-btn"><?php esc_html_e( 'View Product', 'shopys' ); ?></a></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
        </div>

        <?php endif; ?>

        <?php if ( $query->max_num_pages > 1 ) : ?>
        <div class="ppg-pagination" data-type="<?php echo esc_attr( $atts['pagination_type'] ); ?>">
            <?php if ( $atts['pagination_type'] === 'infinite' ) : ?>
                <div class="ppg-infinite-loader">
                    <div class="aps-spinner ppg-spinner"></div>
                    <span class="ppg-infinite-text">Loading more...</span>
                </div>
            <?php endif; ?>
            <div class="ppg-pagination-links <?php echo $atts['pagination_type'] === 'infinite' ? 'ppg-hidden' : ''; ?>">
                <?php echo paginate_links( array(
                    'total'     => $query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => '&larr; Prev',
                    'next_text' => 'Next &rarr;',
                ) ); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else : ?>
        <div class="ppg-no-products">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <p><?php esc_html_e( 'No featured products found.', 'shopys' ); ?></p>
        </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'featured_products', 'featured_product_grid_shortcode' );

// ── AJAX: paginate featured products without page reload ─────────────────────
add_action( 'wp_ajax_fpg_ajax_paginate',        'fpg_ajax_paginate_handler' );
add_action( 'wp_ajax_nopriv_fpg_ajax_paginate', 'fpg_ajax_paginate_handler' );

function fpg_ajax_paginate_handler() {
    check_ajax_referer( 'ppg_ajax_nonce', 'nonce' );

    $paged = max( 1, intval( $_POST['page'] ?? 1 ) );

    $atts = array(
        'limit'            => intval(   $_POST['limit']            ?? 12 ),
        'columns'          => intval(   $_POST['columns']          ?? 4 ),
        'category'         => sanitize_text_field( $_POST['category']         ?? '' ),
        'filter_tabs'      => sanitize_text_field( $_POST['filter_tabs']      ?? '' ),
        'orderby'          => sanitize_text_field( $_POST['orderby']          ?? 'date' ),
        'order'            => sanitize_text_field( $_POST['order']            ?? 'DESC' ),
        'filter'           => sanitize_text_field( $_POST['filter']           ?? 'false' ),
        'cart'             => sanitize_text_field( $_POST['cart']             ?? 'true' ),
        'show_description' => sanitize_text_field( $_POST['show_description'] ?? 'true' ),
        'pagination_type'  => sanitize_text_field( $_POST['pagination_type']  ?? 'normal' ),
        'listing_type'     => sanitize_text_field( $_POST['listing_type']     ?? 'grid' ),
    );

    $atts['listing_type'] = ( trim( strtolower( $atts['listing_type'] ) ) === 'table' ) ? 'table' : 'grid';

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $atts['limit'],
        'paged'          => $paged,
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ),
        ),
    );

    if ( ! empty( $atts['category'] ) ) {
        $args['tax_query']['relation'] = 'AND';
        $args['tax_query'][] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => array_map( 'trim', explode( ',', $atts['category'] ) ),
            'include_children' => true,
        );
    }

    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) {
        wp_send_json_error( array( 'message' => 'No products found' ) );
    }

    ob_start();

    if ( $atts['listing_type'] === 'table' ) {
        ?>
        <div class="ppg-table-wrap">
            <table class="ppg-list-table">
                <tbody>
                <?php
                $row_num = 0;
                while ( $query->have_posts() ) : $query->the_post();
                    $product = wc_get_product( get_the_ID() );
                    if ( ! $product ) continue;
                    $row_num++;
                    $thumb_id   = $product->get_image_id();
                    $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
                    $large_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : wc_placeholder_img_src();
                    $sku        = $product->get_sku();
                    $cats       = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
                    $cat_data   = implode( ' ', ( ! is_wp_error( $cats ) ? $cats : array() ) );
                    $short_desc = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
                    $price_html = $product->get_price_html();
                ?>
                    <tr class="ppg-lt-row" data-categories="<?php echo esc_attr( $cat_data ); ?>"
                        data-qv-image="<?php echo esc_url( $large_url ); ?>"
                        data-qv-name="<?php echo esc_attr( $product->get_name() ); ?>"
                        data-qv-price="<?php echo esc_attr( $price_html ); ?>"
                        data-qv-desc="<?php echo esc_attr( wp_trim_words( $short_desc, 35, '…' ) ); ?>"
                        data-qv-url="<?php echo esc_url( get_permalink() ); ?>">
                        <td class="ppg-lt-col-num"><?php echo $row_num; ?></td>
                        <td class="ppg-lt-col-product">
                            <div class="ppg-lt-product">
                                <?php if ( $thumb_url ) : ?>
                                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-lt-thumb-link">
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" class="ppg-lt-thumb" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
                                    </a>
                                <?php endif; ?>
                                <div class="ppg-lt-meta">
                                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-lt-name"><?php echo esc_html( $product->get_name() ); ?></a>
                                    <?php if ( $sku ) : ?>
                                        <span class="ppg-lt-sku">SKU: <?php echo esc_html( $sku ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( ! $product->is_in_stock() ) : ?>
                                        <span class="ppg-lt-oos"><?php esc_html_e( 'Out of stock', 'shopys' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="ppg-lt-col-price">
                            <?php if ( $product->is_on_sale() ) : ?>
                                <span class="ppg-lt-price-old"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                                <span class="ppg-lt-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                            <?php else : ?>
                                <span class="ppg-lt-price"><?php echo $price_html; ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ( $atts['cart'] === 'true' ) : ?>
                        <td class="ppg-lt-col-action"><?php woocommerce_template_loop_add_to_cart(); ?></td>
                        <?php endif; ?>
                        <td class="ppg-lt-col-qv">
                            <button class="ppg-qv-btn" aria-label="<?php esc_attr_e( 'Quick View', 'shopys' ); ?>">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <?php esc_html_e( 'View', 'shopys' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        ?>
        <div class="ppg-grid ppg-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
        <?php while ( $query->have_posts() ) : $query->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product ) continue;

            $thumb_id   = $product->get_image_id();
            $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
            $cats       = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
            $cat_data   = implode( ' ', ( ! is_wp_error( $cats ) ? $cats : array() ) );
            $regular    = (float) $product->get_regular_price();
            $sale       = (float) $product->get_sale_price();
            $pct        = ( $product->is_on_sale() && $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
            $sku        = $product->get_sku();
            $short_desc = '';
            $specs      = array();
            if ( $atts['show_description'] !== 'false' ) {
                $short_desc = $product->get_short_description();
                if ( empty( $short_desc ) ) $short_desc = $product->get_description();
                if ( $short_desc ) {
                    $desc_clean = wp_strip_all_tags( $short_desc );
                    $raw_specs  = preg_split( '/[.\n]+/', $desc_clean, -1, PREG_SPLIT_NO_EMPTY );
                    foreach ( $raw_specs as $s ) {
                        $s = trim( $s );
                        if ( ! empty( $s ) && strlen( $s ) > 2 ) $specs[] = $s;
                    }
                }
            }
        ?>
            <div class="ppg-card" data-categories="<?php echo esc_attr( $cat_data ); ?>">
                <?php if ( $product->is_on_sale() || ! $product->is_in_stock() ) : ?>
                <div class="ppg-badges">
                    <?php if ( $product->is_on_sale() ) : ?>
                        <span class="ppg-badge ppg-badge-sale">-<?php echo esc_html( $pct ); ?>%</span>
                    <?php endif; ?>
                    <?php if ( ! $product->is_in_stock() ) : ?>
                        <span class="ppg-badge ppg-badge-oos"><?php esc_html_e( 'Sold Out', 'shopys' ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-image-link">
                    <div class="ppg-image-wrapper">
                        <?php if ( $thumb_url ) : ?>
                            <img class="ppg-image" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
                        <?php else : ?>
                            <div class="ppg-no-image"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="ppg-info">
                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-title-link">
                        <h3 class="ppg-title"><?php echo esc_html( $product->get_name() ); ?></h3>
                    </a>
                    <div class="ppg-price-row">
                        <?php if ( $product->is_on_sale() ) : ?>
                            <span class="ppg-price-regular"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                            <span class="ppg-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                        <?php else : ?>
                            <span class="ppg-price-current"><?php echo $product->get_price_html(); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $specs ) ) : ?>
                    <div class="ppg-specs"><ul class="ppg-specs-list">
                        <?php foreach ( $specs as $spec ) : ?><li><?php echo esc_html( $spec ); ?></li><?php endforeach; ?>
                    </ul></div>
                    <?php endif; ?>
                    <?php if ( $sku ) : ?>
                    <div class="ppg-sku"><span class="ppg-sku-separator"></span><?php esc_html_e( 'Code', 'shopys' ); ?> : <?php echo esc_html( $sku ); ?></div>
                    <?php endif; ?>
                    <?php if ( $atts['cart'] === 'true' ) : ?>
                    <div class="ppg-actions"><?php woocommerce_template_loop_add_to_cart(); ?></div>
                    <?php else : ?>
                    <div class="ppg-actions"><a href="<?php echo esc_url( get_permalink() ); ?>" class="button ppg-view-btn"><?php esc_html_e( 'View Product', 'shopys' ); ?></a></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
        <?php
    }

    if ( $query->max_num_pages > 1 ) : ?>
    <div class="ppg-pagination" data-type="normal">
        <div class="ppg-pagination-links">
            <?php echo paginate_links( array(
                'total'     => $query->max_num_pages,
                'current'   => $paged,
                'prev_text' => '&larr; Prev',
                'next_text' => 'Next &rarr;',
            ) ); ?>
        </div>
    </div>
    <?php endif;

    $html = ob_get_clean();
    wp_reset_postdata();
    wp_send_json_success( array(
        'html'     => $html,
        'page'     => $paged,
        'maxPages' => $query->max_num_pages,
    ) );
}
