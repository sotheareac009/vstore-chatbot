<?php
/**
 * Products By Category Shortcode
 * Lists ALL products, grouped by their primary category with a section header per group.
 *
 * Usage: [products_by_category columns="4" orderby="date" order="DESC" cart="true" exclude_cat="uncategorized"]
 *
 * @package Shopys
 */

function products_by_category_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'columns'     => 4,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'cart'        => 'true',
        'exclude_cat' => 'uncategorized', // comma-separated slugs to skip
    ), $atts, 'products_by_category' );

    // ── 1. Fetch every published product ──────────────────────────────────────
    $all_products = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => sanitize_text_field( $atts['orderby'] ),
        'order'          => sanitize_text_field( $atts['order'] ),
    ) );

    if ( empty( $all_products ) ) {
        return '<div class="pbc-no-products">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <p>' . esc_html__( 'No products found.', 'shopys' ) . '</p>
        </div>';
    }

    // ── 2. Get all non-excluded categories (ordered by menu_order / name) ─────
    $excluded_slugs = array_filter( array_map( 'trim', explode( ',', $atts['exclude_cat'] ) ) );

    $all_cats = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
        'exclude'    => array_map( function( $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            return $term ? $term->term_id : 0;
        }, $excluded_slugs ),
    ) );

    if ( is_wp_error( $all_cats ) || empty( $all_cats ) ) {
        return '<p>' . esc_html__( 'No product categories found.', 'shopys' ) . '</p>';
    }

    // ── 3. Index products by their primary category ───────────────────────────
    // "Primary" = first term returned that is NOT excluded
    $cat_products = array(); // [ term_id => [ post_id, ... ] ]
    $cat_map      = array(); // [ term_id => WP_Term ]

    foreach ( $all_cats as $cat ) {
        $cat_map[ $cat->term_id ] = $cat;
    }

    foreach ( $all_products as $post ) {
        $terms = wp_get_post_terms( $post->ID, 'product_cat', array( 'orderby' => 'term_order' ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) continue;

        // Find first non-excluded term
        $primary = null;
        foreach ( $terms as $t ) {
            if ( ! in_array( $t->slug, $excluded_slugs, true ) ) {
                $primary = $t;
                break;
            }
        }
        if ( ! $primary ) continue;

        $cat_products[ $primary->term_id ][] = $post->ID;
        // Ensure this cat is in our map even if get_terms missed it
        if ( ! isset( $cat_map[ $primary->term_id ] ) ) {
            $cat_map[ $primary->term_id ] = $primary;
        }
    }

    // ── 4. Render ─────────────────────────────────────────────────────────────
    $cols = intval( $atts['columns'] );

    ob_start();
    ?>
    <div class="pbc-container">
        <?php foreach ( $all_cats as $cat ) :
            if ( empty( $cat_products[ $cat->term_id ] ) ) continue;
            $product_ids = $cat_products[ $cat->term_id ];
            $cat_url     = get_term_link( $cat );
        ?>

        <!-- ══ Category Section ══ -->
        <section class="pbc-section" id="pbc-cat-<?php echo esc_attr( $cat->slug ); ?>">

            <!-- Section Header -->
            <div class="pbc-section-header">
                <div class="pbc-section-header-left">
                    <span class="pbc-section-accent"></span>
                    <h2 class="pbc-section-title">
                        <?php echo esc_html( $cat->name ); ?>
                        <span class="pbc-section-count">(<?php echo count( $product_ids ); ?>)</span>
                    </h2>
                    <?php if ( $cat->description ) : ?>
                    <p class="pbc-section-desc"><?php echo esc_html( $cat->description ); ?></p>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url( $cat_url ); ?>" class="pbc-view-all-btn">
                    <?php esc_html_e( 'View All', 'shopys' ); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>

            <!-- Product Grid -->
            <div class="ppg-grid ppg-cols-<?php echo esc_attr( $cols ); ?>">
                <?php foreach ( $product_ids as $pid ) :
                    $product = wc_get_product( $pid );
                    if ( ! $product ) continue;

                    $thumb_id   = $product->get_image_id();
                    $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
                    $regular    = (float) $product->get_regular_price();
                    $sale       = (float) $product->get_sale_price();
                    $is_on_sale = $product->is_on_sale();
                    $pct        = ( $is_on_sale && $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
                    $sku        = $product->get_sku();
                    $short_desc = $product->get_short_description();
                    if ( empty( $short_desc ) ) {
                        $short_desc = $product->get_description();
                    }
                    $specs = array();
                    if ( $short_desc ) {
                        $clean = wp_strip_all_tags( $short_desc );
                        $parts = preg_split( '/[.\n]+/', $clean, -1, PREG_SPLIT_NO_EMPTY );
                        foreach ( $parts as $p ) {
                            $p = trim( $p );
                            if ( strlen( $p ) > 2 ) $specs[] = $p;
                        }
                    }
                ?>
                <div class="ppg-card">

                    <?php if ( $is_on_sale || ! $product->is_in_stock() ) : ?>
                    <div class="ppg-badges">
                        <?php if ( $is_on_sale ) : ?>
                            <span class="ppg-badge ppg-badge-sale">-<?php echo esc_html( $pct ); ?>%</span>
                        <?php endif; ?>
                        <?php if ( ! $product->is_in_stock() ) : ?>
                            <span class="ppg-badge ppg-badge-oos"><?php esc_html_e( 'Sold Out', 'shopys' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="ppg-image-link">
                        <div class="ppg-image-wrapper">
                            <?php if ( $thumb_url ) : ?>
                                <img class="ppg-image" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
                            <?php else : ?>
                                <div class="ppg-no-image">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="m21 15-5-5L5 21"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="ppg-info">
                        <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="ppg-title-link">
                            <h3 class="ppg-title"><?php echo esc_html( $product->get_name() ); ?></h3>
                        </a>

                        <div class="ppg-price-row">
                            <?php if ( $is_on_sale ) : ?>
                                <span class="ppg-price-regular"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                                <span class="ppg-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                            <?php else : ?>
                                <span class="ppg-price-current"><?php echo $product->get_price_html(); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $specs ) ) : ?>
                        <div class="ppg-specs">
                            <ul class="ppg-specs-list">
                                <?php foreach ( $specs as $spec ) : ?>
                                    <li><?php echo esc_html( $spec ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if ( $sku ) : ?>
                        <div class="ppg-sku">
                            <span class="ppg-sku-separator"></span>
                            <?php esc_html_e( 'Code', 'shopys' ); ?> : <?php echo esc_html( $sku ); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ( $atts['cart'] === 'true' ) : ?>
                        <div class="ppg-actions">
                            <?php
                            // Set up global $post so WC loop functions work
                            global $post;
                            $post = get_post( $pid );
                            setup_postdata( $post );
                            woocommerce_template_loop_add_to_cart();
                            wp_reset_postdata();
                            ?>
                        </div>
                        <?php else : ?>
                        <div class="ppg-actions">
                            <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="button ppg-view-btn">
                                <?php esc_html_e( 'View Product', 'shopys' ); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

        </section>

        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'products_by_category', 'products_by_category_shortcode' );
