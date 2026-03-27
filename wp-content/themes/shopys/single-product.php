<?php
/**
 * Premium Single Product Template
 * @package Shopys
 */
get_header();

while ( have_posts() ) :
    the_post();
    $product      = wc_get_product( get_the_ID() );
    if ( ! $product ) { get_footer(); exit; }

    $name         = $product->get_name();
    $price_html   = $product->get_price_html();
    $reg_price    = $product->get_regular_price();
    $sale_price   = $product->get_sale_price();
    $is_on_sale   = $product->is_on_sale();
    $in_stock     = $product->is_in_stock();
    $stock_qty    = $product->get_stock_quantity();
    $sku          = $product->get_sku();
    $short_desc   = $product->get_short_description();
    $description  = $product->get_description();
    $categories   = wc_get_product_category_list( $product->get_id(), ', ' );
    $brands       = wp_get_post_terms( $product->get_id(), 'product_brand' );
    $brand_html   = '';
    if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
        $brand_links = array();
        foreach ( $brands as $brand ) {
            $brand_links[] = '<a href="' . esc_url( get_term_link( $brand ) ) . '">' . esc_html( $brand->name ) . '</a>';
        }
        $brand_html = implode( ', ', $brand_links );
    }
    $gallery_ids  = $product->get_gallery_image_ids();
    $main_img_id  = $product->get_image_id();
    $main_img     = $main_img_id ? wp_get_attachment_image_url( $main_img_id, 'large' ) : wc_placeholder_img_src('large');
    $pct          = ( $is_on_sale && (float)$reg_price > 0 ) ? round( ( ( (float)$reg_price - (float)$sale_price ) / (float)$reg_price ) * 100 ) : 0;

    // Short desc bullets
    $bullets = array();
    if ( $short_desc ) {
        $clean = wp_strip_all_tags( $short_desc );
        $parts = preg_split( '/[.\n]+/', $clean, -1, PREG_SPLIT_NO_EMPTY );
        foreach ( $parts as $p ) {
            $p = trim($p);
            if ( strlen($p) > 2 ) $bullets[] = $p;
        }
    }
?>

<div id="content" class="page-content spd-page">
  <div class="content-wrap">
    <div class="container">

      <!-- Breadcrumb -->
      <nav class="spd-breadcrumb">
        <?php woocommerce_breadcrumb(); ?>
      </nav>

      <!-- Main Product Section -->
      <div class="spd-wrapper">

        <!-- LEFT: Image Gallery -->
        <div class="spd-gallery">
          <!-- Main Image -->
          <div class="spd-main-image-wrap">
            <?php if ( $is_on_sale ) : ?>
              <div class="spd-badge-sale">-<?php echo esc_html($pct); ?>% OFF</div>
            <?php endif; ?>
            <?php if ( ! $in_stock ) : ?>
              <div class="spd-badge-oos">Sold Out</div>
            <?php endif; ?>
            <img id="spd-main-img" class="spd-main-img" src="<?php echo esc_url($main_img); ?>" alt="<?php echo esc_attr($name); ?>" />
          </div>

          <!-- Thumbnails -->
          <?php if ( ! empty( $gallery_ids ) ) : ?>
          <div class="spd-thumbnails">
            <div class="spd-thumb <?php echo $main_img_id ? '' : 'spd-thumb-active'; ?>" onclick="spd_switch(this, '<?php echo esc_url($main_img); ?>')">
              <img src="<?php echo esc_url( wp_get_attachment_image_url($main_img_id, 'thumbnail') ); ?>" />
            </div>
            <?php foreach ( $gallery_ids as $gid ) : ?>
            <div class="spd-thumb" onclick="spd_switch(this, '<?php echo esc_url( wp_get_attachment_image_url($gid, 'large') ); ?>')">
              <img src="<?php echo esc_url( wp_get_attachment_image_url($gid, 'thumbnail') ); ?>" />
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- RIGHT: Product Info -->
        <div class="spd-info">

          <!-- Categories -->
          <?php if ( $categories ) : ?>
          <div class="spd-cats"><?php echo wp_kses_post($categories); ?></div>
          <?php endif; ?>

          <!-- Title -->
          <h1 class="spd-title"><?php echo esc_html($name); ?></h1>

          <!-- Rating -->
          <?php if ( $product->get_rating_count() > 0 ) : ?>
          <div class="spd-rating">
            <?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); ?>
            <span class="spd-rating-count">(<?php echo esc_html($product->get_rating_count()); ?> reviews)</span>
          </div>
          <?php endif; ?>

          <!-- Price -->
          <div class="spd-price-block">
            <?php if ( $is_on_sale ) : ?>
              <span class="spd-price-regular"><?php echo wc_price($reg_price); ?></span>
              <span class="spd-price-sale"><?php echo wc_price($sale_price); ?></span>
              <span class="spd-price-pct">Save <?php echo esc_html($pct); ?>%</span>
            <?php else : ?>
              <span class="spd-price-current"><?php echo $price_html; ?></span>
            <?php endif; ?>
          </div>

          <!-- Short Description Bullets -->
          <?php if ( ! empty($bullets) ) : ?>
          <ul class="spd-bullets">
            <?php foreach ( $bullets as $b ) : ?>
            <li><?php echo esc_html($b); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <!-- Divider -->
          <div class="spd-divider"></div>

          <!-- Stock Status -->
          <div class="spd-stock <?php echo $in_stock ? 'in-stock' : 'out-stock'; ?>">
            <?php if ( $in_stock ) : ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
              <?php echo $stock_qty ? esc_html($stock_qty) . ' in stock' : 'In Stock'; ?>
            <?php else : ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
              Out of Stock
            <?php endif; ?>
          </div>

          <!-- Add to Cart Form -->
          <div class="spd-cart-section">
            <?php woocommerce_template_single_add_to_cart(); ?>
          </div>

          <!-- Meta: SKU / Categories -->
          <div class="spd-meta">
            <?php if ( $sku ) : ?>
            <div class="spd-meta-row">
              <span class="spd-meta-label">SKU</span>
              <span class="spd-meta-value"><?php echo esc_html($sku); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $categories ) : ?>
            <div class="spd-meta-row">
              <span class="spd-meta-label">Category</span>
              <span class="spd-meta-value"><?php echo wp_kses_post($categories); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $brand_html ) : ?>
            <div class="spd-meta-row">
              <span class="spd-meta-label">Brand</span>
              <span class="spd-meta-value"><?php echo wp_kses_post($brand_html); ?></span>
            </div>
            <?php endif; ?>
          </div>

        </div><!-- .spd-info -->
      </div><!-- .spd-wrapper -->

      <!-- Description Tabs -->
      <?php if ( $description || $short_desc ) : ?>
      <div class="spd-tabs-section">
        <div class="spd-tabs-nav">
          <button class="spd-tab-btn active" data-tab="description">Description</button>
          <button class="spd-tab-btn" data-tab="reviews">Reviews (<?php echo esc_html($product->get_review_count()); ?>)</button>
        </div>

        <div class="spd-tab-content active" id="spd-tab-description">
          <?php echo wp_kses_post( $description ?: $short_desc ); ?>
        </div>

        <div class="spd-tab-content" id="spd-tab-reviews">
          <?php
          if ( comments_open() ) {
              comments_template();
          } else {
              echo '<p class="spd-no-reviews">Reviews are closed for this product.</p>';
          }
          ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Related Products -->
      <?php
      $related_ids = wc_get_related_products( $product->get_id(), 4 );
      if ( ! empty($related_ids) ) :
          $related_query = new WP_Query( array(
              'post_type' => 'product',
              'post__in'  => $related_ids,
              'orderby'   => 'rand',
              'posts_per_page' => 4,
          ));
      ?>
      <div class="spd-related">
        <h2 class="spd-related-title">Related Products</h2>
        <div class="ppg-grid ppg-cols-4">
          <?php while ( $related_query->have_posts() ) : $related_query->the_post();
              $rp = wc_get_product(get_the_ID());
              if (!$rp) continue;
              $rthumb = $rp->get_image_id() ? wp_get_attachment_image_url($rp->get_image_id(), 'medium') : '';
              $rcat   = implode(' ', wp_get_post_terms($rp->get_id(), 'product_cat', ['fields'=>'slugs']));
              $rreg   = (float)$rp->get_regular_price();
              $rsal   = (float)$rp->get_sale_price();
              $rpct   = ($rp->is_on_sale() && $rreg > 0) ? round((($rreg-$rsal)/$rreg)*100) : 0;
          ?>
          <div class="ppg-card" data-categories="<?php echo esc_attr($rcat); ?>">
            <?php if ($rp->is_on_sale()) : ?>
            <div class="ppg-badges"><span class="ppg-badge ppg-badge-sale">-<?php echo $rpct; ?>%</span></div>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" class="ppg-image-link">
              <div class="ppg-image-wrapper">
                <?php if ($rthumb) : ?>
                  <img class="ppg-image" src="<?php echo esc_url($rthumb); ?>" alt="<?php echo esc_attr($rp->get_name()); ?>" loading="lazy" />
                <?php else : ?>
                  <div class="ppg-no-image"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>
                <?php endif; ?>
              </div>
            </a>
            <div class="ppg-info">
              <a href="<?php echo esc_url(get_permalink()); ?>" class="ppg-title-link">
                <h3 class="ppg-title"><?php echo esc_html($rp->get_name()); ?></h3>
              </a>
              <div class="ppg-price-row">
                <?php if ($rp->is_on_sale()) : ?>
                  <span class="ppg-price-regular"><?php echo wc_price($rreg); ?></span>
                  <span class="ppg-price-sale"><?php echo wc_price($rsal); ?></span>
                <?php else : ?>
                  <span class="ppg-price-current"><?php echo $rp->get_price_html(); ?></span>
                <?php endif; ?>
              </div>
              <div class="ppg-actions"><a href="<?php echo esc_url(get_permalink()); ?>" class="button">View Product</a></div>
            </div>
          </div>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- .container -->
  </div><!-- .content-wrap -->
</div><!-- .spd-page -->

<script>
function spd_switch(el, src) {
    document.getElementById('spd-main-img').src = src;
    document.querySelectorAll('.spd-thumb').forEach(function(t){ t.classList.remove('spd-thumb-active'); });
    el.classList.add('spd-thumb-active');
}

// Tab switching
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.spd-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tab = this.getAttribute('data-tab');
            document.querySelectorAll('.spd-tab-btn').forEach(function(b){ b.classList.remove('active'); });
            document.querySelectorAll('.spd-tab-content').forEach(function(c){ c.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('spd-tab-' + tab).classList.add('active');
        });
    });
});
</script>

<?php endwhile; ?>
<?php get_footer(); ?>
