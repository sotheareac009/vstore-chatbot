<?php
/**
 * Marvo Premium Products Shortcode
 *
 * A simple, single-category product grid shortcode.
 * Usage: [marvo_premium_products category="marvo"]
 *
 * @package Shopys
 */

function marvo_premium_products_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'category' => '',
    ), $atts, 'marvo_premium_products' );

    if ( empty( $atts['category'] ) ) {
        return '<p style="color:red;">marvo_premium_products: please set a <strong>category</strong> slug.</p>';
    }

    // Query products in this category
    $query = new WP_Query( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,   // show all products in the category
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => array_map( 'trim', explode( ',', $atts['category'] ) ),
                'include_children' => true,
            ),
        ),
    ) );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '<p class="ppg-no-results">No products found in this category.</p>';
    }

    ob_start();
    ?>
    <div class="ppg-container marvo-ppg">

        <!-- Header: Breadcrumb left | Product Count right -->
        <div class="ppg-cat-header">
            <div class="marvo-header-left">
                <?php if ( function_exists('woocommerce_breadcrumb') ) : ?>
                <nav class="marvo-breadcrumb">
                    <?php woocommerce_breadcrumb(); ?>
                </nav>
                <?php endif; ?>
            </div>
            <div class="marvo-header-right">
                <span class="ppg-search-count">
                    <?php printf(
                        esc_html( _n( '%d product found', '%d products found', $query->found_posts, 'shopys' ) ),
                        $query->found_posts
                    ); ?>
                </span>
            </div>
        </div>

        <div class="ppg-grid ppg-cols-4">
        <?php while ( $query->have_posts() ) : $query->the_post();
            $product = wc_get_product( get_the_ID() );
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

                <!-- Badges -->
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

                <!-- Image -->
                <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-image-link">
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

                <!-- Info -->
                <div class="ppg-info">
                    <a href="<?php echo esc_url( get_permalink() ); ?>" class="ppg-title-link">
                        <h3 class="ppg-title"><?php echo esc_html( $product->get_name() ); ?></h3>
                    </a>

                    <!-- Price -->
                    <div class="ppg-price-row">
                        <?php if ( $is_on_sale ) : ?>
                            <span class="ppg-price-regular"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                            <span class="ppg-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                        <?php else : ?>
                            <span class="ppg-price-current"><?php echo $product->get_price_html(); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Spec Bullets -->
                    <?php if ( ! empty( $specs ) ) : ?>
                    <div class="ppg-specs">
                        <ul class="ppg-specs-list">
                            <?php foreach ( $specs as $spec ) : ?>
                                <li><?php echo esc_html( $spec ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- SKU -->
                    <?php if ( $sku ) : ?>
                    <div class="ppg-sku">
                        <span class="ppg-sku-separator"></span>
                        <?php esc_html_e( 'Code', 'shopys' ); ?> : <?php echo esc_html( $sku ); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Add to Cart -->
                    <!-- <div class="ppg-actions">
                        <?php woocommerce_template_loop_add_to_cart(); ?>
                    </div> -->
                </div>

            </div>
        <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'marvo_premium_products', 'marvo_premium_products_shortcode' );
