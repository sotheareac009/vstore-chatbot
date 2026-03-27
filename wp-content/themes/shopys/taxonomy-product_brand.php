<?php
/**
 * Template for WooCommerce Product Brand Archives (product_brand taxonomy)
 * Mirrors the premium category listing layout.
 *
 * @package Shopys
 */

get_header();

$queried_object   = get_queried_object();
$current_slug     = isset( $queried_object->slug ) ? $queried_object->slug : '';
$current_name     = isset( $queried_object->name ) ? $queried_object->name : '';
$current_desc     = isset( $queried_object->description ) ? $queried_object->description : '';
$current_id       = isset( $queried_object->term_id ) ? $queried_object->term_id : 0;
$taxonomy         = isset( $queried_object->taxonomy ) ? $queried_object->taxonomy : 'product_brand';
$paged            = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$per_page         = 24;

// Pagination type: 'normal' or 'infinite'
$pagination_type  = apply_filters( 'shopys_brand_pagination_type', 'infinite' );

// Query products in this brand
$query = new WP_Query( array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'tax_query'      => array(
        array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $current_slug,
        ),
    ),
) );

// Child terms (sub-brands) for filter tabs
$child_terms = get_terms( array(
    'taxonomy'   => $taxonomy,
    'parent'     => $current_id,
    'hide_empty' => true,
) );
?>

<div id="content" class="page-content">
    <div class="content-wrap">
        <div class="container">
            <div class="main-area">
                <div id="primary" class="primary-content-area">
                    <div class="primary-content-wrap">

                        <div class="ppg-container">

                            <!-- Brand Header -->
                            <div class="ppg-cat-header">
                                <h1 class="ppg-cat-title"><?php echo esc_html( $current_name ); ?></h1>
                                <?php if ( $current_desc ) : ?>
                                    <p class="ppg-cat-desc"><?php echo esc_html( $current_desc ); ?></p>
                                <?php endif; ?>
                                <p class="ppg-search-count">
                                    <?php printf(
                                        esc_html( _n( '%d product found', '%d products found', $query->found_posts, 'shopys' ) ),
                                        $query->found_posts
                                    ); ?>
                                </p>
                            </div>

                            <!-- Sub-brand Filter Tabs (if any) -->
                            <?php if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) : ?>
                            <div class="ppg-filter-bar">
                                <button class="ppg-filter-btn active" data-category="all">
                                    <span class="ppg-filter-icon">🏷️</span> All
                                </button>
                                <?php foreach ( $child_terms as $term ) : ?>
                                <button class="ppg-filter-btn" data-category="<?php echo esc_attr( $term->slug ); ?>">
                                    <?php echo esc_html( $term->name ); ?>
                                    <span class="ppg-filter-count"><?php echo esc_html( $term->count ); ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Product Grid -->
                            <?php if ( $query->have_posts() ) : ?>
                            <div class="ppg-grid ppg-cols-4">
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
                                    $short_desc = $product->get_short_description();
                                    if ( empty( $short_desc ) ) {
                                        $short_desc = $product->get_description();
                                    }
                                ?>
                                <div class="ppg-card" data-categories="<?php echo esc_attr( $cat_data ); ?>">

                                    <!-- Badges -->
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
                                        <?php if ( $short_desc ) :
                                            $desc_clean = wp_strip_all_tags( $short_desc );
                                            $specs = preg_split( '/[.\n]+/', $desc_clean, -1, PREG_SPLIT_NO_EMPTY );
                                        ?>
                                        <div class="ppg-specs">
                                            <?php if ( ! empty( $specs ) ) : ?>
                                            <ul class="ppg-specs-list">
                                                <?php foreach ( $specs as $spec ) :
                                                    $spec = trim( $spec );
                                                    if ( ! empty( $spec ) && strlen( $spec ) > 2 ) : ?>
                                                    <li><?php echo esc_html( $spec ); ?></li>
                                                <?php endif; endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
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
                                        <div class="ppg-actions">
                                            <?php woocommerce_template_loop_add_to_cart(); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ( $query->max_num_pages > 1 ) : ?>
                            <div class="ppg-pagination" data-type="<?php echo esc_attr( $pagination_type ); ?>">
                                <?php if ( $pagination_type === 'infinite' ) : ?>
                                    <div class="ppg-infinite-loader">
                                        <div class="aps-spinner ppg-spinner"></div>
                                        <span class="ppg-infinite-text">Loading more...</span>
                                    </div>
                                <?php endif; ?>
                                <div class="ppg-pagination-links <?php echo $pagination_type === 'infinite' ? 'ppg-hidden' : ''; ?>">
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
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                <p><?php esc_html_e( 'No products found for this brand.', 'shopys' ); ?></p>
                            </div>
                            <?php endif;
                            wp_reset_postdata(); ?>

                        </div><!-- .ppg-container -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
