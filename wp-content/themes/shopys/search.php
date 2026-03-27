<?php
/**
 * Custom Search Results Template
 * Displays product search results in the premium product grid style.
 *
 * @package Shopys
 */

get_header();

$search_query = get_search_query();
$post_type    = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
$is_product   = ( $post_type === 'product' ) && class_exists( 'WooCommerce' );
?>
<div id="content" class="page-content">
    <div class="content-wrap">
        <div class="container">
            <div class="main-area">
                <div id="primary" class="primary-content-area">
                    <div class="primary-content-wrap">

<?php if ( $is_product ) : ?>

    <div class="ppg-container">
        <div class="ppg-search-header">
            <h1 class="ppg-search-title">
                <?php
                printf(
                    esc_html__( 'Search results for "%s"', 'shopys' ),
                    '<span>' . esc_html( $search_query ) . '</span>'
                );
                ?>
            </h1>
            <p class="ppg-search-count">
                <?php
                printf(
                    esc_html( _n( '%d product found', '%d products found', $wp_query->found_posts, 'shopys' ) ),
                    $wp_query->found_posts
                );
                ?>
            </p>
        </div>

        <?php if ( have_posts() ) : ?>
            <div class="ppg-grid ppg-cols-4">
                <?php while ( have_posts() ) : the_post();
                    $product = wc_get_product( get_the_ID() );
                    if ( ! $product ) continue;

                    $thumb_id = $product->get_image_id();
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
                    $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
                    $cat_data = implode( ' ', ( ! is_wp_error( $categories ) ? $categories : array() ) );
                ?>
                <div class="ppg-card" data-categories="<?php echo esc_attr( $cat_data ); ?>">
                    <?php // Badges
                    if ( $product->is_on_sale() || ! $product->is_in_stock() ) : ?>
                        <div class="ppg-badges">
                            <?php if ( $product->is_on_sale() ) :
                                $regular = (float) $product->get_regular_price();
                                $sale    = (float) $product->get_sale_price();
                                $pct     = ( $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
                            ?>
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
                                <div class="ppg-no-image"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>
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
                            <?php if ( $product->is_on_sale() ) : ?>
                                <span class="ppg-price-regular"><?php echo wc_price( $product->get_regular_price() ); ?></span>
                                <span class="ppg-price-sale"><?php echo wc_price( $product->get_sale_price() ); ?></span>
                            <?php else : ?>
                                <span class="ppg-price-current"><?php echo $product->get_price_html(); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Description Bullets -->
                        <?php
                        $short_desc = $product->get_short_description();
                        if ( empty( $short_desc ) ) {
                            $short_desc = $product->get_description();
                        }
                        if ( $short_desc ) :
                            $desc_clean = wp_strip_all_tags( $short_desc );
                            $specs = preg_split( '/[.\n]+/', $desc_clean, -1, PREG_SPLIT_NO_EMPTY );
                        ?>
                        <div class="ppg-specs">
                            <?php if ( ! empty( $specs ) ) : ?>
                            <ul class="ppg-specs-list">
                                <?php foreach ( $specs as $spec ) :
                                    $spec = trim( $spec );
                                    if ( ! empty( $spec ) && strlen( $spec ) > 2 ) :
                                ?>
                                    <li><?php echo esc_html( $spec ); ?></li>
                                <?php endif; endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- SKU -->
                        <?php if ( $product->get_sku() ) : ?>
                        <div class="ppg-sku">
                            <span class="ppg-sku-separator"></span>
                            <?php esc_html_e( 'Code', 'shopys' ); ?> : <?php echo esc_html( $product->get_sku() ); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Add to Cart -->
                        <div class="ppg-actions">
                            <?php woocommerce_template_loop_add_to_cart(); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php
            $pagination = paginate_links( array(
                'total'   => $wp_query->max_num_pages,
                'current' => max( 1, get_query_var( 'paged' ) ),
                'prev_text' => '&larr; Prev',
                'next_text' => 'Next &rarr;',
            ));
            if ( $pagination ) :
            ?>
            <nav class="ppg-pagination">
                <?php echo $pagination; ?>
            </nav>
            <?php endif; ?>

        <?php else : ?>
            <div class="ppg-no-products">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <p><?php esc_html_e( 'No products found. Try a different search term.', 'shopys' ); ?></p>
            </div>
        <?php endif; ?>
    </div>

<?php else :
    // Non-product search — keep default behavior
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            get_template_part( 'template-parts/content', 'search' );
        endwhile;
    else :
        get_template_part( 'template-parts/content', 'none' );
    endif;
endif; ?>

                    </div><!-- end primary-content-wrap -->
                </div><!-- end primary-content-area -->
            </div><!-- end main-area -->
        </div>
    </div><!-- end content-wrap -->
</div><!-- end content page-content -->
<?php get_footer(); ?>
