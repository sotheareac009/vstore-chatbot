<?php
/**
 * AI Shopping Assistant – Anthropic Claude Integration
 * Natural conversation + product cards when relevant
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ═══════════════════════════════════════════════════════════════════
   1. ADMIN SETTINGS PAGE
   ═══════════════════════════════════════════════════════════════════ */

add_action( 'admin_menu', 'shopys_ai_admin_menu' );
function shopys_ai_admin_menu() {
    add_options_page(
        __( 'AI Chatbot Settings', 'shopys' ),
        __( 'AI Chatbot', 'shopys' ),
        'manage_options',
        'shopys-ai-chatbot',
        'shopys_ai_settings_page'
    );
}

add_action( 'admin_init', 'shopys_ai_register_settings' );
function shopys_ai_register_settings() {
    register_setting( 'shopys_ai_settings', 'shopys_ai_enabled',     'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_api_key',     'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_bot_name',    'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_welcome_msg', 'sanitize_textarea_field' );

    // Feature toggles
    register_setting( 'shopys_ai_settings', 'shopys_ai_product_only',      'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_image_search',      'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_pdf_reading',       'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_link_comparison',   'sanitize_text_field' );
    register_setting( 'shopys_ai_settings', 'shopys_ai_attachments',       'sanitize_text_field' );
}

function shopys_ai_settings_page() {
    $enabled     = get_option( 'shopys_ai_enabled', '1' );
    $api_key     = get_option( 'shopys_ai_api_key', '' );
    $bot_name    = get_option( 'shopys_ai_bot_name', 'Shopping Assistant' );
    $welcome_msg = get_option( 'shopys_ai_welcome_msg', "Hi! I'm your shopping assistant.\nAsk me anything — I can recommend products based on your needs!" );

    // Feature toggles (default ON)
    $product_only    = get_option( 'shopys_ai_product_only', '1' );
    $image_search    = get_option( 'shopys_ai_image_search', '1' );
    $pdf_reading     = get_option( 'shopys_ai_pdf_reading', '1' );
    $link_comparison = get_option( 'shopys_ai_link_comparison', '1' );
    $attachments_on  = get_option( 'shopys_ai_attachments', '1' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'AI Chatbot Settings', 'shopys' ); ?></h1>
        <p style="font-size:14px;color:#555;">
            Powered by <strong>Anthropic Claude</strong> — get your API key at
            <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">Anthropic Console</a>.
        </p>
        <?php if ( empty( $api_key ) ) : ?>
        <div class="notice notice-warning"><p>⚠️ No API key set. Please enter your Claude API key below and save.</p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'shopys_ai_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="shopys_ai_enabled"><?php esc_html_e( 'Enable Chatbot', 'shopys' ); ?></label></th>
                    <td>
                        <select name="shopys_ai_enabled" id="shopys_ai_enabled">
                            <option value="1" <?php selected( $enabled, '1' ); ?>><?php esc_html_e( 'Enabled', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $enabled, '0' ); ?>><?php esc_html_e( 'Disabled', 'shopys' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="shopys_ai_api_key"><?php esc_html_e( 'Claude API Key', 'shopys' ); ?></label></th>
                    <td>
                        <input type="password" name="shopys_ai_api_key" id="shopys_ai_api_key"
                               value="<?php echo esc_attr( $api_key ); ?>"
                               class="regular-text" autocomplete="off" />
                        <p class="description">
                            Your Anthropic Claude API key from
                            <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>.
                            Starts with <code>sk-ant-</code>.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="shopys_ai_bot_name"><?php esc_html_e( 'Bot Name', 'shopys' ); ?></label></th>
                    <td>
                        <input type="text" name="shopys_ai_bot_name" id="shopys_ai_bot_name"
                               value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="shopys_ai_welcome_msg"><?php esc_html_e( 'Welcome Message', 'shopys' ); ?></label></th>
                    <td>
                        <textarea name="shopys_ai_welcome_msg" id="shopys_ai_welcome_msg"
                                  rows="3" class="large-text"><?php echo esc_textarea( $welcome_msg ); ?></textarea>
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:30px;"><?php esc_html_e( 'Feature Toggles', 'shopys' ); ?></h2>
            <p style="font-size:13px;color:#666;">Enable or disable specific chatbot capabilities.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Product-Only Mode', 'shopys' ); ?></th>
                    <td>
                        <select name="shopys_ai_product_only" id="shopys_ai_product_only">
                            <option value="1" <?php selected( $product_only, '1' ); ?>><?php esc_html_e( 'On', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $product_only, '0' ); ?>><?php esc_html_e( 'Off', 'shopys' ); ?></option>
                        </select>
                        <p class="description">Restrict the chatbot to only answer product/store-related questions. Off-topic questions will be politely declined.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Image Product Search', 'shopys' ); ?></th>
                    <td>
                        <select name="shopys_ai_image_search" id="shopys_ai_image_search">
                            <option value="1" <?php selected( $image_search, '1' ); ?>><?php esc_html_e( 'On', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $image_search, '0' ); ?>><?php esc_html_e( 'Off', 'shopys' ); ?></option>
                        </select>
                        <p class="description">Allow users to upload product images to find matching items in the catalog.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'PDF Reading', 'shopys' ); ?></th>
                    <td>
                        <select name="shopys_ai_pdf_reading" id="shopys_ai_pdf_reading">
                            <option value="1" <?php selected( $pdf_reading, '1' ); ?>><?php esc_html_e( 'On', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $pdf_reading, '0' ); ?>><?php esc_html_e( 'Off', 'shopys' ); ?></option>
                        </select>
                        <p class="description">Allow users to upload PDF files for reading and summarization.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Outside Link Comparison', 'shopys' ); ?></th>
                    <td>
                        <select name="shopys_ai_link_comparison" id="shopys_ai_link_comparison">
                            <option value="1" <?php selected( $link_comparison, '1' ); ?>><?php esc_html_e( 'On', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $link_comparison, '0' ); ?>><?php esc_html_e( 'Off', 'shopys' ); ?></option>
                        </select>
                        <p class="description">Allow users to paste product links from other websites to compare with your catalog.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'File Attachments', 'shopys' ); ?></th>
                    <td>
                        <select name="shopys_ai_attachments" id="shopys_ai_attachments">
                            <option value="1" <?php selected( $attachments_on, '1' ); ?>><?php esc_html_e( 'On', 'shopys' ); ?></option>
                            <option value="0" <?php selected( $attachments_on, '0' ); ?>><?php esc_html_e( 'Off', 'shopys' ); ?></option>
                        </select>
                        <p class="description">Allow users to attach images and files to chat messages.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* ═══════════════════════════════════════════════════════════════════
   2. PRODUCT CATALOG BUILDER  (cached 10 min)
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_get_catalog() {
    $cached = get_transient( 'shopys_ai_catalog' );
    if ( false !== $cached ) return $cached;

    if ( ! class_exists( 'WooCommerce' ) ) return array();

    $currency = get_woocommerce_currency_symbol();

    $products = wc_get_products( array(
        'status'  => 'publish',
        'limit'   => 200,
        'orderby' => 'date',
        'order'   => 'DESC',
    ) );

    $catalog = array();
    foreach ( $products as $product ) {
        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

        $attrs = array();
        foreach ( $product->get_attributes() as $attr ) {
            $label = wc_attribute_label( $attr->get_name() );
            if ( $attr->is_taxonomy() ) {
                $terms = wp_get_post_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    $attrs[ $label ] = implode( ', ', $terms );
                }
            } else {
                $attrs[ $label ] = implode( ', ', $attr->get_options() );
            }
        }

        $desc = wp_strip_all_tags( $product->get_short_description() );
        if ( empty( $desc ) ) {
            $desc = wp_strip_all_tags( $product->get_description() );
        }
        $desc = mb_substr( $desc, 0, 250 );

        $catalog[] = array(
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            'price'       => $currency . $product->get_price(),
            'regular'     => $product->get_regular_price() ? $currency . $product->get_regular_price() : '',
            'sale'        => $product->get_sale_price() ? $currency . $product->get_sale_price() : '',
            'categories'  => ! is_wp_error( $categories ) ? $categories : array(),
            'description' => $desc,
            'sku'         => $product->get_sku(),
            'stock'       => $product->get_stock_status(),
            'attributes'  => $attrs,
        );
    }

    set_transient( 'shopys_ai_catalog', $catalog, 10 * MINUTE_IN_SECONDS );
    return $catalog;
}

/* ═══════════════════════════════════════════════════════════════════
   2.5 WEBSITE MAP BUILDER (all pages, posts, categories)
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_get_website_map() {
    $cached = get_transient( 'shopys_ai_website_map' );
    if ( false !== $cached ) return $cached;

    $map = array(
        'pages'      => array(),
        'posts'      => array(),
        'categories' => array(),
        'archives'   => array(),
        'menus'      => array(),
        'product_categories' => array(),
    );

    // Get all pages
    $pages = get_pages( array( 'number' => 100 ) );
    foreach ( $pages as $page ) {
        $map['pages'][] = array(
            'id'    => $page->ID,
            'title' => $page->post_title,
            'url'   => get_page_link( $page->ID ),
            'slug'  => $page->post_name,
        );
    }

    // Get all posts
    $posts = get_posts( array(
        'numberposts' => 100,
        'post_type'   => 'post',
        'orderby'     => 'date',
        'order'       => 'DESC',
    ) );
    foreach ( $posts as $post ) {
        $post_categories = get_the_category( $post->ID );
        $cat_names = array_map( function( $cat ) { return $cat->name; }, $post_categories );
        
        $map['posts'][] = array(
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'url'        => get_permalink( $post->ID ),
            'slug'       => $post->post_name,
            'categories' => $cat_names,
            'excerpt'    => wp_strip_all_tags( $post->post_excerpt ),
        );
    }

    // Get all post categories
    $categories = get_categories( array( 'hide_empty' => false, 'number' => 100 ) );
    foreach ( $categories as $cat ) {
        $map['categories'][] = array(
            'id'        => $cat->term_id,
            'name'      => $cat->name,
            'url'       => get_category_link( $cat->term_id ),
            'slug'      => $cat->slug,
            'count'     => $cat->count,
            'post_count' => $cat->count,
        );
    }

    // Get all product categories (WooCommerce)
    if ( class_exists( 'WooCommerce' ) ) {
        $product_cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 100,
        ) );
        if ( ! is_wp_error( $product_cats ) ) {
            foreach ( $product_cats as $pcat ) {
                $map['product_categories'][] = array(
                    'id'        => $pcat->term_id,
                    'name'      => $pcat->name,
                    'url'       => get_term_link( $pcat->term_id, 'product_cat' ),
                    'slug'      => $pcat->slug,
                    'count'     => $pcat->count,
                    'description' => wp_strip_all_tags( $pcat->description ),
                );
            }
        }
    }

    // Get all registered menus and their items
    $menus = wp_get_nav_menus();
    foreach ( $menus as $menu ) {
        $menu_items = wp_get_nav_menu_items( $menu->term_id );
        if ( $menu_items ) {
            $items_list = array();
            foreach ( $menu_items as $item ) {
                if ( $item->menu_item_parent == 0 ) { // Only get top-level items
                    $items_list[] = array(
                        'id'    => $item->ID,
                        'title' => $item->title,
                        'url'   => $item->url,
                        'label' => $item->attr_title ?: $item->title,
                    );
                }
            }
            if ( ! empty( $items_list ) ) {
                $map['menus'][] = array(
                    'name'  => $menu->name,
                    'items' => $items_list,
                );
            }
        }
    }

    // Add common archive pages
    $map['archives'][] = array(
        'title' => 'Blog Home',
        'url'   => get_home_url() . '/blog/',
    );

    if ( class_exists( 'WooCommerce' ) ) {
        $map['archives'][] = array(
            'title' => 'Shop',
            'url'   => wc_get_page_permalink( 'shop' ),
        );
    }

    set_transient( 'shopys_ai_website_map', $map, 10 * MINUTE_IN_SECONDS );
    return $map;
}

/* ═══════════════════════════════════════════════════════════════════
   2.6 URL TO PAGE IDENTIFIER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_identify_page_from_url( $url ) {
    $website_map = shopys_ai_get_website_map();
    $url_normalized = strtolower( rtrim( $url, '/' ) );
    
    // Check if it's homepage
    $home_url_normalized = strtolower( rtrim( home_url(), '/' ) );
    if ( $url_normalized === $home_url_normalized ) {
        return array(
            'type'  => 'homepage',
            'title' => 'Home',
            'url'   => $url,
        );
    }

    // Check pages
    foreach ( $website_map['pages'] as $page ) {
        $page_url_normalized = strtolower( rtrim( $page['url'], '/' ) );
        if ( $url_normalized === $page_url_normalized || strpos( $url_normalized, $page_url_normalized ) === 0 ) {
            return array(
                'type'  => 'page',
                'title' => $page['title'],
                'url'   => $page['url'],
                'slug'  => $page['slug'],
            );
        }
    }

    // Check posts
    foreach ( $website_map['posts'] as $post ) {
        $post_url_normalized = strtolower( rtrim( $post['url'], '/' ) );
        if ( $url_normalized === $post_url_normalized || strpos( $url_normalized, $post_url_normalized ) === 0 ) {
            return array(
                'type'       => 'post',
                'title'      => $post['title'],
                'url'        => $post['url'],
                'slug'       => $post['slug'],
                'categories' => $post['categories'],
            );
        }
    }

    // Check categories
    foreach ( $website_map['categories'] as $cat ) {
        $cat_url_normalized = strtolower( rtrim( $cat['url'], '/' ) );
        if ( $url_normalized === $cat_url_normalized || strpos( $url_normalized, $cat_url_normalized ) === 0 ) {
            return array(
                'type'  => 'category',
                'title' => $cat['name'],
                'url'   => $cat['url'],
                'slug'  => $cat['slug'],
                'count' => $cat['post_count'],
            );
        }
    }

    // Check product categories
    foreach ( $website_map['product_categories'] as $pcat ) {
        $pcat_url_normalized = strtolower( rtrim( $pcat['url'], '/' ) );
        if ( $url_normalized === $pcat_url_normalized || strpos( $url_normalized, $pcat_url_normalized ) === 0 ) {
            return array(
                'type'  => 'product_category',
                'title' => $pcat['name'],
                'url'   => $pcat['url'],
                'slug'  => $pcat['slug'],
                'count' => $pcat['count'],
            );
        }
    }

    // Check archives
    foreach ( $website_map['archives'] as $archive ) {
        $archive_url_normalized = strtolower( rtrim( $archive['url'], '/' ) );
        if ( $url_normalized === $archive_url_normalized || strpos( $url_normalized, $archive_url_normalized ) === 0 ) {
            return array(
                'type'  => 'archive',
                'title' => $archive['title'],
                'url'   => $archive['url'],
            );
        }
    }

    // If no match, return unknown
    return array(
        'type'  => 'unknown',
        'title' => 'Unknown Page',
        'url'   => $url,
    );
}

/* ═══════════════════════════════════════════════════════════════════
   3. WEB BROWSING / REAL-TIME DATA FETCHER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_fetch_url( $url ) {
    // Validate URL
    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return new WP_Error( 'invalid_url', 'Invalid URL format' );
    }

    // Fetch URL with cURL - Allow all domains
    $response = wp_remote_get( $url, array(
        'timeout'    => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'sslverify'  => false, // Allow self-signed certificates for localhost
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );
    $code = wp_remote_retrieve_response_code( $response );

    if ( $code !== 200 ) {
        return new WP_Error( 'fetch_failed', 'Failed to fetch webpage (HTTP ' . $code . ')' );
    }

    if ( empty( $body ) ) {
        return new WP_Error( 'empty_response', 'Webpage returned empty content' );
    }

    // Extract text content (strip HTML tags)
    $content = wp_strip_all_tags( $body );
    $content = preg_replace( '/\s+/', ' ', $content );
    $content = mb_substr( $content, 0, 3000 ); // Increased limit to 3000 chars

    // Extract additional structural information
    $page_info = shopys_ai_extract_page_structure( $body, $url );

    return array(
        'url'              => $url,
        'content'          => trim( $content ),
        'fetched'          => current_time( 'mysql' ),
        'page_title'       => $page_info['title'],
        'images'           => $page_info['images'],
        'promotions'       => $page_info['promotions'],
        'layout_structure' => $page_info['structure'],
        'headings'         => $page_info['headings'],
    );
}

/* ═══════════════════════════════════════════════════════════════════
   3.2 PAGE STRUCTURE & LAYOUT ANALYZER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_extract_page_structure( $html, $url ) {
    $info = array(
        'title'     => '',
        'images'    => array(),
        'promotions' => array(),
        'structure' => array(),
        'headings'  => array(),
    );

    // Extract page title
    if ( preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $html, $matches ) ) {
        $info['title'] = trim( $matches[1] );
    }

    // Extract images (first 5)
    if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i', $html, $matches ) ) {
        foreach ( array_slice( array_combine( $matches[1], $matches[2] ), 0, 5 ) as $src => $alt ) {
            // Make URLs absolute
            $abs_src = shopys_ai_make_url_absolute( $src, $url );
            $info['images'][] = array(
                'src' => $abs_src,
                'alt' => $alt ?: 'Product image',
            );
        }
    }

    // Extract promotions/sales keywords
    $promo_keywords = array( 'sale', 'discount', 'offer', 'promotion', 'deal', 'limited time', 'free shipping', 'save', 'off', '% off', 'coupon', 'special' );
    foreach ( $promo_keywords as $keyword ) {
        if ( stripos( $html, $keyword ) !== false ) {
            // Extract surrounding context
            if ( preg_match( '/([^.]{0,100}' . preg_quote( $keyword, '/' ) . '[^.]{0,100})/i', $html, $matches ) ) {
                $info['promotions'][] = wp_strip_all_tags( trim( $matches[0] ) );
            }
        }
    }
    $info['promotions'] = array_slice( array_unique( $info['promotions'] ), 0, 5 );

    // Extract headings (h1, h2, h3) for structure
    if ( preg_match_all( '/<h[1-3][^>]*>([^<]+)<\/h[1-3]>/i', $html, $matches ) ) {
        $info['headings'] = array_slice( array_map( function( $h ) {
            return wp_strip_all_tags( trim( $h ) );
        }, $matches[1] ), 0, 10 );
    }

    // Analyze page structure/layout
    $info['structure'] = array(
        'has_navigation' => preg_match( '/<nav[^>]*>|<menu[^>]*>/i', $html ) ? 'Yes' : 'No',
        'has_header'     => preg_match( '/<header[^>]*>|<div[^>]*class=["\']header/i', $html ) ? 'Yes' : 'No',
        'has_footer'     => preg_match( '/<footer[^>]*>|<div[^>]*class=["\']footer/i', $html ) ? 'Yes' : 'No',
        'has_sidebar'    => preg_match( '/<aside[^>]*>|<div[^>]*class=["\']sidebar/i', $html ) ? 'Yes' : 'No',
        'image_count'    => substr_count( $html, '<img' ),
        'link_count'     => substr_count( $html, '<a ' ),
    );

    return $info;
}

/* ═══════════════════════════════════════════════════════════════════
   3.3 URL HELPER FUNCTION
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_make_url_absolute( $url, $base_url ) {
    if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return $url;
    }
    
    $base_parts = parse_url( $base_url );
    $base_dir = rtrim( dirname( $base_parts['path'] ), '/' ) . '/';
    
    if ( substr( $url, 0, 2 ) === '//' ) {
        return $base_parts['scheme'] . ':' . $url;
    } elseif ( substr( $url, 0, 1 ) === '/' ) {
        return $base_parts['scheme'] . '://' . $base_parts['host'] . $url;
    } else {
        return $base_parts['scheme'] . '://' . $base_parts['host'] . $base_dir . $url;
    }
}

/* ═══════════════════════════════════════════════════════════════════
   3.4 MENU COUNTER FUNCTION
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_count_menu_items() {
    $website_map = shopys_ai_get_website_map();
    $menu_summary = array();

    if ( ! empty( $website_map['menus'] ) ) {
        foreach ( $website_map['menus'] as $menu ) {
            $menu_summary[] = array(
                'name'  => $menu['name'],
                'count' => count( $menu['items'] ),
                'items' => array_map( function( $item ) { return $item['title']; }, $menu['items'] ),
            );
        }
    }

    return $menu_summary;
}

/* ═══════════════════════════════════════════════════════════════════
   3.5 PRODUCT COMPARISON HELPER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_extract_product_info( $url, $content ) {
    // Try to extract product-like information from fetched content
    $product_info = array(
        'url'        => $url,
        'title'      => '',
        'price'      => '',
        'features'   => array(),
        'rating'     => '',
        'availability' => '',
    );

    // Extract title (usually from page title or first heading)
    if ( preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $content, $matches ) ) {
        $product_info['title'] = trim( strip_tags( $matches[1] ) );
    }

    // Look for price patterns (common formats: $99, $99.99, etc.)
    if ( preg_match( '/(\$|€|£)[\s]?([\d,]+\.?\d{0,2})/i', $content, $matches ) ) {
        $product_info['price'] = $matches[0];
    }

    // Look for rating patterns (out of 5 or percentage)
    if ( preg_match( '/(\d+\.?\d*)\s*(?:out of|\/)\s*5/i', $content, $matches ) ) {
        $product_info['rating'] = $matches[1] . '/5';
    } elseif ( preg_match( '/rating:?\s*(\d+\.?\d*)%/i', $content, $matches ) ) {
        $product_info['rating'] = $matches[1] . '%';
    }

    // Look for availability info
    if ( preg_match( '/(in stock|available|out of stock|unavailable)/i', $content, $matches ) ) {
        $product_info['availability'] = $matches[1];
    }

    // Extract features/specifications (look for common patterns)
    if ( preg_match_all( '/(?:feature|spec|specification|benefit):?\s*([^.!\n]+[.!])/i', $content, $matches ) ) {
        $product_info['features'] = array_slice( array_map( 'trim', $matches[1] ), 0, 5 );
    }

    return $product_info;
}

/* ═══════════════════════════════════════════════════════════════════
   4. CLAUDE API CALLER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_call_claude( $api_key, $system_prompt, $messages, $model = 'claude-opus-4-6' ) {
    // Check if message contains images or PDFs
    $has_media = false;
    $has_pdf = false;
    foreach ( $messages as $msg ) {
        if ( is_array( $msg['content'] ) ) {
            foreach ( $msg['content'] as $part ) {
                if ( isset( $part['type'] ) && $part['type'] === 'image' ) {
                    $has_media = true;
                }
                if ( isset( $part['type'] ) && $part['type'] === 'document' ) {
                    $has_media = true;
                    $has_pdf = true;
                }
            }
        }
    }

    $body = array(
        'model'      => $model,
        'max_tokens' => $has_media ? 4096 : 1024,
        'system'     => $system_prompt,
        'messages'   => $messages,
    );

    $headers = array(
        'Content-Type'      => 'application/json',
        'x-api-key'         => $api_key,
        'anthropic-version' => '2023-06-01',
    );

    // PDF support requires beta header
    if ( $has_pdf ) {
        $headers['anthropic-beta'] = 'pdfs-2024-09-25';
    }

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'headers' => $headers,
        'body'    => wp_json_encode( $body ),
        'timeout' => $has_media ? 60 : 30,
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code !== 200 ) {
        $err_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'API error ' . $code;
        return new WP_Error( 'claude_error', $err_msg );
    }

    if ( isset( $data['content'][0]['text'] ) ) {
        return $data['content'][0]['text'];
    }

    return new WP_Error( 'claude_empty', 'Empty response from Claude.' );
}

/* ═══════════════════════════════════════════════════════════════════
   4. AJAX CHAT HANDLER
   ═══════════════════════════════════════════════════════════════════ */

add_action( 'wp_ajax_shopys_ai_chat',        'shopys_ai_chat_handler' );
add_action( 'wp_ajax_nopriv_shopys_ai_chat', 'shopys_ai_chat_handler' );

function shopys_ai_chat_handler() {
    check_ajax_referer( 'shopys_ai_nonce', 'nonce' );

    $message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
    $history = isset( $_POST['history'] ) ? json_decode( wp_unslash( $_POST['history'] ), true ) : array();

    $allowed_models = array(
        'claude-haiku-4-5-20251001',
        'claude-sonnet-4-6',
        'claude-opus-4-6',
    );
    $model = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'claude-opus-4-6';
    if ( ! in_array( $model, $allowed_models, true ) ) {
        $model = 'claude-opus-4-6';
    }

    // Parse attachments (base64 images/files)
    $raw_attachments = isset( $_POST['attachments'] ) ? wp_unslash( $_POST['attachments'] ) : '';
    $file_attachments = ! empty( $raw_attachments ) ? json_decode( $raw_attachments, true ) : array();

    if ( empty( $message ) && empty( $file_attachments ) ) {
        wp_send_json_error( array( 'message' => 'Please type a message or attach a file.' ) );
    }

    // Get API key from WP options only (no hardcoded fallback)
    $api_key = get_option( 'shopys_ai_api_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( array( 'message' => 'AI chatbot is not configured. Please ask the admin to add the API key in Settings > AI Chatbot.' ) );
    }

    // ── Product-Only Pre-Filter (saves API credits) ──────────────────
    // Catches obviously off-topic messages BEFORE calling Claude
    $is_product_only_mode = get_option( 'shopys_ai_product_only', '1' ) === '1';
    if ( $is_product_only_mode && ! empty( $message ) ) {
        $msg_lower = strtolower( trim( $message ) );

        // Strip command tags so they don't interfere with detection
        $msg_clean = preg_replace( '/\[(FIND_PRODUCT|READ_TEXT|SUMMARIZE)\]\s*/i', '', $msg_lower );
        $msg_clean = trim( $msg_clean );

        // Skip filter if message has attachments (image search / PDF reading)
        $has_files = ! empty( $file_attachments );

        // Skip filter if message contains a URL (could be product link comparison)
        $has_url = (bool) preg_match( '/(https?:\/\/|www\.)/i', $msg_clean );

        if ( ! $has_files && ! $has_url && strlen( $msg_clean ) > 2 ) {

            // ── ALLOW LIST: product/store/computer-shop related keywords ──
            $allow_keywords = array(
                // Products & shopping
                'product', 'price', 'cost', 'buy', 'purchase', 'order', 'cart', 'checkout',
                'shipping', 'delivery', 'return', 'refund', 'warranty', 'stock', 'available',
                'recommend', 'suggestion', 'compare', 'comparison', 'review', 'rating',
                'catalog', 'category', 'brand', 'model', 'spec', 'feature', 'deal', 'sale',
                'discount', 'coupon', 'offer', 'promo', 'cheap', 'expensive', 'budget',
                'store', 'shop', 'payment', 'pay',
                // Computer / tech / electronics
                'computer', 'pc', 'laptop', 'desktop', 'notebook', 'macbook', 'imac',
                'monitor', 'screen', 'display', 'keyboard', 'mouse', 'headset', 'headphone',
                'speaker', 'microphone', 'webcam', 'camera', 'printer', 'scanner',
                'cpu', 'processor', 'gpu', 'graphics card', 'video card', 'ram', 'memory',
                'ssd', 'hdd', 'hard drive', 'storage', 'motherboard', 'mainboard',
                'power supply', 'psu', 'case', 'cooling', 'fan', 'rgb',
                'intel', 'amd', 'nvidia', 'geforce', 'radeon', 'ryzen', 'core i',
                'windows', 'macos', 'linux', 'operating system',
                'wifi', 'bluetooth', 'ethernet', 'router', 'modem', 'network', 'cable',
                'usb', 'hdmi', 'thunderbolt', 'port', 'adapter', 'hub', 'dock',
                'tablet', 'ipad', 'phone', 'smartphone', 'iphone', 'samsung', 'android',
                'charger', 'battery', 'power bank',
                'gaming', 'gamer', 'fps', 'resolution', '4k', '1080p', '1440p',
                'software', 'program', 'app', 'antivirus', 'office', 'microsoft',
                'server', 'nas', 'backup', 'cloud',
                'component', 'peripheral', 'accessory', 'gadget', 'device', 'electronic',
                'tech', 'hardware', 'build', 'upgrade', 'setup', 'install',
                'gb', 'tb', 'mhz', 'ghz', 'watt', 'inch',
                // Technology brands & products
                'apple', 'lenovo', 'dell', 'hp', 'asus', 'acer', 'msi', 'razer',
                'logitech', 'corsair', 'steelseries', 'hyperx', 'kingston', 'crucial',
                'western digital', 'seagate', 'sandisk', 'toshiba', 'sony', 'lg',
                'benq', 'viewsonic', 'philips', 'gigabyte', 'asrock', 'evga', 'zotac',
                'nzxt', 'cooler master', 'thermaltake', 'be quiet', 'seasonic',
                'tp-link', 'netgear', 'linksys', 'ubiquiti', 'synology', 'qnap',
                'epson', 'canon', 'brother', 'xerox', 'huawei', 'xiaomi', 'oppo',
                'google pixel', 'oneplus', 'realme', 'vivo', 'nothing phone',
                'surface', 'thinkpad', 'zenbook', 'vivobook', 'inspiron', 'pavilion',
                'predator', 'legion', 'rog', 'tuf', 'nitro', 'omen', 'alienware',
                // Computer accessories & peripherals
                'mousepad', 'mouse pad', 'wrist rest', 'monitor arm', 'monitor stand',
                'laptop stand', 'laptop bag', 'laptop sleeve', 'laptop cooler',
                'docking station', 'kvm switch', 'usb hub', 'card reader', 'sd card',
                'flash drive', 'thumb drive', 'external drive', 'portable drive',
                'surge protector', 'ups', 'uninterruptible', 'power strip',
                'webcam cover', 'privacy screen', 'screen protector', 'cleaning kit',
                'thermal paste', 'cable management', 'cable sleeve', 'cable clip',
                'desk mat', 'desk pad', 'ergonomic', 'standing desk', 'sit stand',
                'monitor light', 'desk lamp', 'led strip', 'light bar',
                'stream deck', 'capture card', 'game controller', 'joystick', 'gamepad',
                'racing wheel', 'flight stick', 'vr headset', 'vr', 'oculus', 'meta quest',
                'stylus', 'pen tablet', 'drawing tablet', 'wacom', 'xp-pen', 'huion',
                // Audio & video tech
                'earbuds', 'earphone', 'airpods', 'soundbar', 'subwoofer', 'amplifier',
                'dac', 'audio interface', 'mixer', 'studio monitor', 'condenser mic',
                'noise cancelling', 'anc', 'wireless', 'bluetooth speaker',
                'projector', 'streaming', 'hdmi cable', 'displayport', 'dp cable',
                // Networking & connectivity
                'mesh wifi', 'wifi extender', 'range extender', 'access point',
                'switch', 'network switch', 'patch cable', 'cat5', 'cat6', 'cat7',
                'fiber optic', 'powerline', 'vpn', 'firewall', 'poe',
                'sim card', 'esim', '5g', '4g', 'lte', 'hotspot', 'dongle',
                // Smart home & IoT
                'smart home', 'smart plug', 'smart light', 'smart lock', 'smart speaker',
                'alexa', 'echo', 'google home', 'home assistant', 'iot',
                'security camera', 'doorbell camera', 'ring', 'nest', 'arlo',
                'smart watch', 'smartwatch', 'fitness tracker', 'garmin', 'fitbit',
                'apple watch', 'galaxy watch', 'wearable',
                // Printer & office tech
                'toner', 'ink cartridge', 'inkjet', 'laser printer', 'label printer',
                '3d printer', '3d printing', 'filament', 'pla', 'abs',
                'laminator', 'shredder', 'binding machine', 'paper',
                // Tech specs & general terms
                'benchmark', 'overclock', 'bios', 'uefi', 'firmware', 'driver',
                'latency', 'bandwidth', 'throughput', 'refresh rate', 'response time',
                'oled', 'ips', 'va', 'tn', 'hdr', 'freesync', 'g-sync',
                'nvme', 'sata', 'pcie', 'ddr4', 'ddr5', 'm.2', 'dimm', 'sodimm',
                'lpddr', 'emmc', 'ufs', 'raid',
                'ampere', 'rdna', 'arc', 'tensor', 'npu', 'tpu', 'ai chip',
                'rtx', 'gtx', 'rx ', 'vram', 'cuda', 'ray tracing',
                'wh', 'mah', 'volt', 'amp', 'lumen', 'nit', 'cd/m2',
                'mini led', 'micro led', 'qled', 'nano ips', 'quantum dot',
                'type-c', 'usb-c', 'usb-a', 'lightning', 'magsafe', 'qi', 'wireless charging',
                'ip67', 'ip68', 'waterproof', 'dustproof', 'rugged', 'mil-std',

                // ── Amazon / Best Buy / Newegg categories ──
                // Laptops & notebooks (sub-categories)
                'chromebook', 'ultrabook', '2-in-1', 'convertible', 'detachable',
                'workstation', 'mobile workstation', 'thin and light', 'business laptop',
                'gaming laptop', 'student laptop', 'budget laptop', 'refurbished',
                'certified refurbished', 'renewed', 'open box',
                'elitebook', 'probook', 'latitude', 'precision', 'xps',
                'spectre', 'envy', 'swift', 'spin', 'chromebook plus',
                'gram', 'yoga', 'flex', 'ideapad', 'ideacentre',
                'galaxy book', 'pixelbook', 'matebook',
                // Desktops (sub-categories)
                'all-in-one', 'aio', 'mini pc', 'small form factor', 'sff',
                'tower', 'mid tower', 'full tower', 'micro atx', 'mini itx', 'atx',
                'gaming desktop', 'prebuilt', 'pre-built', 'custom build', 'barebones',
                'nuc', 'mac mini', 'mac studio', 'mac pro',
                'optiplex', 'prodesk', 'elitedesk', 'thinkcentre',
                'trident', 'infinite', 'aegis', 'aurora',
                // Monitors (sub-categories from Best Buy / Amazon)
                'ultrawide', 'super ultrawide', 'curved monitor', 'flat monitor',
                'portable monitor', 'touchscreen monitor', 'usb-c monitor',
                'gaming monitor', 'professional monitor', 'color accurate',
                'monitor 24', 'monitor 27', 'monitor 32', 'monitor 34', 'monitor 49',
                '240hz', '144hz', '165hz', '360hz', '120hz', '60hz', '75hz', '100hz',
                '5k', '8k', '1080p monitor', '1440p monitor', '4k monitor',
                'wqhd', 'uwqhd', 'fhd', 'qhd', 'uhd',
                'dell ultrasharp', 'lg ultragear', 'lg ultrafine', 'samsung odyssey',
                'asus proart', 'benq zowie', 'benq pd', 'mobiuz',
                'aoc', 'pixio', 'innocn', 'sceptre', 'viotek', 'koorui', 'dough',
                // Keyboards (sub-categories from Amazon / Best Buy / Newegg)
                'mechanical keyboard', 'membrane keyboard', 'wireless keyboard',
                'bluetooth keyboard', 'gaming keyboard', 'ergonomic keyboard',
                'split keyboard', 'compact keyboard', '60%', '65%', '75%', 'tkl', 'tenkeyless',
                'full size keyboard', 'numpad', 'keypad', 'macro pad',
                'hot swap', 'hot-swappable', 'cherry mx', 'gateron', 'kailh',
                'linear switch', 'tactile switch', 'clicky switch',
                'keycap', 'keycaps', 'pbt', 'abs keycap', 'doubleshot',
                'ducky', 'keychron', 'akko', 'royal kludge', 'redragon',
                'anne pro', 'wooting', 'nuphy', 'iqunix', 'varmilo', 'leopold',
                'tofu', 'gmmk', 'kbd', 'custom keyboard',
                // Mice (sub-categories)
                'gaming mouse', 'wireless mouse', 'ergonomic mouse', 'vertical mouse',
                'trackball', 'trackpad', 'touchpad', 'bluetooth mouse',
                'lightweight mouse', 'mmo mouse', 'fps mouse',
                'dpi', 'polling rate', 'sensor', 'optical', 'laser',
                'glorious', 'finalmouse', 'pulsar', 'lamzu', 'vaxee', 'zowie',
                'deathadder', 'viper', 'basilisk', 'orochi', 'superlight',
                'g pro', 'g502', 'g305', 'mx master', 'mx anywhere',
                // Storage & memory (deeper from Newegg / PCPartPicker)
                'external ssd', 'portable ssd', 'internal ssd', 'internal hdd',
                'nas drive', 'surveillance drive', 'enterprise drive',
                'samsung evo', 'samsung pro', 'wd black', 'wd blue', 'wd red',
                'firecuda', 'barracuda', 'ironwolf', 'skyhawk',
                'sabrent', 'silicon power', 'teamgroup', 'adata', 'transcend',
                'mushkin', 'patriot', 'pny', 'lexar', 'verbatim',
                'memory card', 'micro sd', 'microsd', 'sdxc', 'sdhc', 'cfast', 'cfexpress',
                'usb stick', 'pen drive',
                'ddr3', 'ddr4 ram', 'ddr5 ram', 'ecc', 'registered', 'unbuffered',
                'ram speed', 'ram latency', 'cl16', 'cl18', 'cl30', 'cl36', 'xmp', 'expo',
                'g.skill', 'trident z', 'ripjaws', 'vengeance', 'fury', 'dominator',
                'corsair vengeance', 'kingston fury',
                // GPUs (deeper from Newegg / Amazon / PCPartPicker)
                'rtx 5090', 'rtx 5080', 'rtx 5070', 'rtx 5060',
                'rtx 4090', 'rtx 4080', 'rtx 4070', 'rtx 4060',
                'rtx 3090', 'rtx 3080', 'rtx 3070', 'rtx 3060',
                'rx 9070', 'rx 7900', 'rx 7800', 'rx 7700', 'rx 7600',
                'rx 6800', 'rx 6700', 'rx 6600',
                'arc b580', 'arc a770', 'arc a750',
                'founders edition', 'reference card', 'aftermarket',
                'triple fan', 'dual fan', 'blower', 'aio cooled',
                'sapphire', 'xfx', 'powercolor', 'asus strix', 'tuf gaming',
                'msi suprim', 'msi ventus', 'gaming x trio', 'gaming oc',
                'windforce', 'eagle', 'aorus',
                'palit', 'gainward', 'inno3d', 'pny gpu', 'galax',
                // CPUs (deeper from Newegg / PCPartPicker)
                'core i3', 'core i5', 'core i7', 'core i9', 'core ultra',
                'ryzen 3', 'ryzen 5', 'ryzen 7', 'ryzen 9', 'threadripper',
                'xeon', 'epyc',
                'arrow lake', 'raptor lake', 'alder lake', 'meteor lake', 'lunar lake',
                'zen 5', 'zen 4', 'zen 3', 'granite ridge', 'phoenix', 'hawk point',
                'socket', 'am5', 'am4', 'lga 1851', 'lga 1700', 'lga 1200',
                'multi-core', 'single-core', 'thread', 'core count', 'boost clock', 'base clock',
                'tdp', 'wattage', 'integrated graphics', 'igpu', 'apu',
                // Motherboards (deeper from Newegg / PCPartPicker)
                'z890', 'z790', 'z690', 'b860', 'b760', 'b650', 'b550', 'b450',
                'x870', 'x670', 'x570', 'a620', 'a520',
                'wifi motherboard', 'itx motherboard', 'matx motherboard', 'eatx',
                'vrm', 'heatsink', 'chipset', 'bios flashback',
                'msi mag', 'msi mpg', 'msi meg', 'asus prime', 'asus rog strix',
                'gigabyte aorus', 'asrock phantom', 'asrock steel legend',
                // PSU (deeper from Newegg / PCPartPicker)
                'modular', 'semi-modular', 'non-modular', 'fully modular',
                '80 plus', '80+ gold', '80+ platinum', '80+ titanium', '80+ bronze',
                'sfx', 'sfx-l', 'atx 3.0', 'atx psu', '12vhpwr', '12v-2x6',
                '500w', '550w', '650w', '750w', '850w', '1000w', '1200w', '1600w',
                'rm850', 'rm1000', 'hx1000', 'focus', 'leadex', 'revolt',
                'prime ultra', 'straight power', 'dark power', 'ion',
                // Cases (deeper from Newegg / PCPartPicker)
                'pc case', 'computer case', 'tower case',
                'tempered glass', 'mesh front', 'airflow case', 'silent case',
                'compact case', 'cube case', 'open frame',
                'fractal design', 'meshify', 'north', 'torrent', 'pop',
                'lian li', 'lancool', 'o11 dynamic', 'dan case',
                'phanteks', 'evolv', 'eclipse',
                'h7', 'h510', 'h710',
                '4000d', '5000d', '5000t', '7000d',
                'haf', 'masterbox', 'mastercase', 'nr200',
                'define', 'meshroom', 'antec',
                // Cooling (deeper from Newegg / PCPartPicker)
                'air cooler', 'tower cooler', 'aio cooler', 'liquid cooler',
                'water cooling', 'custom loop', 'radiator', 'reservoir', 'pump',
                'cpu cooler', 'gpu cooler', 'case fan',
                '120mm', '140mm', '240mm', '280mm', '360mm', '420mm',
                'static pressure', 'airflow fan', 'pwm', 'argb fan', 'daisy chain',
                'noctua', 'nh-d15', 'nh-u12s', 'chromax',
                'arctic', 'liquid freezer', 'freezer 34',
                'deepcool', 'ak620', 'ls720', 'assassin',
                'ek', 'ekwb', 'alphacool', 'corsair h150', 'kraken',
                'id-cooling', 'thermalright', 'peerless assassin', 'frost spirit',

                // ── B&H Photo / Adorama categories ──
                // Photography & videography
                'dslr', 'mirrorless', 'point and shoot', 'action camera',
                'gopro', 'dji', 'insta360', 'fujifilm', 'nikon', 'panasonic',
                'olympus', 'sigma', 'tamron', 'tokina', 'samyang', 'rokinon',
                'camera lens', 'zoom lens', 'prime lens', 'wide angle', 'telephoto',
                'fisheye', 'macro lens', 'portrait lens',
                'full frame', 'aps-c', 'micro four thirds', 'crop sensor',
                'megapixel', 'image stabilization', 'autofocus', 'viewfinder',
                'tripod', 'monopod', 'gimbal', 'stabilizer', 'slider',
                'camera bag', 'camera strap', 'lens filter', 'nd filter', 'uv filter',
                'polarizer', 'lens cap', 'lens hood',
                'memory card for camera', 'battery grip',
                // Video production
                'camcorder', 'cinema camera', 'blackmagic', 'bmpcc',
                'video light', 'ring light', 'softbox', 'led panel',
                'green screen', 'chroma key', 'backdrop',
                'video switcher', 'video mixer', 'hdmi splitter',
                'video encoder', 'video recorder', 'field monitor',
                'lavalier', 'lav mic', 'shotgun mic', 'boom mic',
                'rode', 'shure', 'sennheiser', 'audio-technica', 'blue yeti',
                'elgato', 'blackmagic design', 'atomos',
                // Drones
                'drone', 'quadcopter', 'fpv drone', 'racing drone',
                'dji mini', 'dji air', 'dji mavic', 'dji avata', 'dji inspire',
                'autel', 'parrot', 'skydio', 'betafpv',
                'drone battery', 'propeller', 'drone bag', 'drone controller',

                // ── TechRadar / Tom's Hardware / CNET / The Verge categories ──
                // Emerging tech & AI hardware
                'ai pc', 'copilot pc', 'snapdragon x', 'snapdragon x elite', 'snapdragon x plus',
                'apple m1', 'apple m2', 'apple m3', 'apple m4', 'm1 pro', 'm1 max', 'm1 ultra',
                'm2 pro', 'm2 max', 'm2 ultra', 'm3 pro', 'm3 max', 'm4 pro', 'm4 max',
                'neural engine', 'machine learning', 'on-device ai',
                'arm processor', 'arm chip', 'risc-v', 'qualcomm', 'mediatek',
                // Displays & TV tech (Best Buy / Amazon)
                'smart tv', 'led tv', 'oled tv', 'qled tv', 'neo qled',
                'mini led tv', 'micro led tv', '8k tv', '4k tv',
                'tv', 'television', 'roku', 'fire tv', 'chromecast', 'apple tv',
                'streaming device', 'streaming stick', 'set top box',
                'lg c4', 'lg g4', 'samsung s95', 'sony bravia', 'hisense', 'tcl',
                'vizio', 'toshiba tv', 'insignia',
                'universal remote', 'tv mount', 'wall mount', 'tv stand',
                'hdmi arc', 'earc', 'hdmi 2.1', 'hdmi 2.0', 'dolby atmos', 'dolby vision',
                'dtsx', 'dts',
                // Home audio (Best Buy / Amazon)
                'home theater', 'surround sound', '5.1', '7.1', '2.1', 'atmos speaker',
                'receiver', 'av receiver', 'stereo receiver', 'preamp', 'preamplifier',
                'bookshelf speaker', 'floor standing', 'tower speaker', 'center channel',
                'powered speaker', 'passive speaker', 'active speaker',
                'turntable', 'vinyl', 'record player', 'phono',
                'bose', 'sonos', 'jbl', 'harman kardon', 'marshall', 'bang olufsen',
                'klipsch', 'polk', 'yamaha', 'denon', 'marantz', 'onkyo',
                'kef', 'elac', 'svs', 'audioengine', 'edifier', 'creative',
                // E-readers & tablets (Amazon / Best Buy)
                'kindle', 'e-reader', 'e-ink', 'kobo', 'remarkable',
                'ipad pro', 'ipad air', 'ipad mini', 'galaxy tab', 'fire tablet',
                'android tablet', 'windows tablet', 'drawing display',
                'apple pencil', 'samsung s pen', 'tablet keyboard', 'tablet case',
                // Wearables & health tech (Best Buy / Amazon)
                'fitness band', 'heart rate monitor', 'blood pressure monitor',
                'pulse oximeter', 'smart ring', 'oura ring', 'whoop',
                'gps watch', 'running watch', 'cycling computer',
                'galaxy buds', 'pixel buds', 'beats', 'jabra', 'jaybird',
                'bone conduction', 'open ear', 'true wireless', 'tws',
                // Car tech & accessories (Best Buy / Amazon)
                'dash cam', 'dashcam', 'car camera', 'backup camera', 'gps navigator',
                'car charger', 'car mount', 'phone mount', 'car adapter',
                'obd2', 'obd scanner', 'car diagnostic', 'tire inflator',
                'car stereo', 'car speaker', 'head unit', 'car amplifier',
                'radar detector', 'cb radio', 'walkie talkie', 'two-way radio',
                // Power & energy (Amazon / Best Buy)
                'solar panel', 'solar charger', 'portable power station',
                'generator', 'inverter', 'ecoflow', 'jackery', 'bluetti', 'anker solix',
                'wall charger', 'fast charger', 'gan charger', 'multi-port charger',
                'wireless charger', 'charging pad', 'charging stand', 'charging cable',
                'usb-c cable', 'lightning cable', 'micro usb cable', 'braided cable',
                'anker', 'baseus', 'ugreen', 'belkin', 'satechi', 'twelve south',
                'spigen', 'otterbox', 'phone case', 'tablet case',
                // Gaming (Best Buy / Amazon / Newegg)
                'gaming chair', 'gaming desk', 'gaming headset', 'gaming monitor',
                'gaming keyboard', 'gaming mouse', 'gaming mousepad',
                'playstation', 'ps5', 'ps4', 'xbox', 'xbox series x', 'xbox series s',
                'nintendo', 'switch', 'steam deck', 'rog ally', 'legion go',
                'handheld gaming', 'portable gaming', 'handheld pc',
                'console', 'controller', 'dualsense', 'pro controller',
                'gaming headphones', 'rgb lighting', 'led gaming',
                'graphics setting', 'frame rate', 'high refresh',
                'esports', 'competitive gaming', 'tournament',
                'elgato stream', 'obs', 'stream setup',
                // Cables & connectors (Amazon / Newegg)
                'optical cable', 'toslink', 'rca cable', 'xlr cable', 'trs cable',
                'coaxial', 'vga cable', 'dvi cable', 'mini displayport',
                'extension cable', 'power cable', 'iec cable', 'nema',
                'sata cable', 'molex', 'psu cable', 'sleeved cable', 'cable mod',
                'kvm cable', 'serial cable', 'parallel cable',
                // Server & enterprise (Newegg / Amazon)
                'rack server', 'tower server', 'blade server', 'rack mount',
                'server rack', 'server case', 'server psu', 'redundant power',
                'server ram', 'ecc memory', 'server cpu', 'server motherboard',
                'raid controller', 'hba', 'network card', 'nic',
                'sfp', 'sfp+', 'qsfp', '10gbe', '2.5gbe', '10 gigabit',
                'managed switch', 'unmanaged switch', 'poe switch',
                'rack shelf', 'rack rail', 'cable tray', 'patch panel',
                // Software & OS (Best Buy / Amazon)
                'windows 11', 'windows 10', 'windows license', 'product key',
                'office 365', 'microsoft 365', 'adobe', 'photoshop', 'lightroom',
                'premiere pro', 'after effects', 'creative cloud', 'final cut',
                'logic pro', 'davinci resolve',
                'vpn software', 'password manager', 'parental control',
                'norton', 'mcafee', 'kaspersky', 'bitdefender', 'malwarebytes',
                'os license', 'ubuntu', 'fedora', 'debian', 'arch linux', 'mint',
                // 3D printing & maker (Amazon / Newegg)
                'resin printer', 'fdm printer', 'sla printer', 'msla',
                'creality', 'ender 3', 'ender 5', 'prusa', 'bambu lab', 'anycubic',
                'elegoo', 'flashforge', 'voron', 'klipper',
                'nozzle', 'hotend', 'extruder', 'build plate', 'print bed',
                'petg', 'tpu', 'nylon filament', 'resin', 'wash and cure',
                'cnc', 'laser engraver', 'laser cutter', 'soldering iron',
                'soldering station', 'multimeter', 'oscilloscope',
                'raspberry pi', 'arduino', 'esp32', 'microcontroller',
                'breadboard', 'led kit', 'sensor kit', 'robotics',
                // AliExpress / global brands
                'baseus', 'orico', 'vention', 'unitek', 'jsaux',
                'kemove', 'womier', 'epomaker', 'feker', 'yunzii',
                'topping', 'fiio', 'moondrop', 'kz', 'cca', 'tin hifi',
                'tripowin', 'truthear', '7hz', 'simgot', 'tangzu',
                'beelink', 'minisforum', 'geekom', 'acemagic', 'trigkey',
                'chuwi', 'teclast', 'alldocube', 'doogee', 'blackview', 'ulefone',
                'umidigi', 'poco', 'redmi', 'zte', 'honor', 'tecno', 'infinix',
                'amazfit', 'ticwatch', 'mobvoi', 'haylou', 'qcy',
                'dreame', 'roborock', 'ecovacs', 'robot vacuum', 'vacuum cleaner',
                'air purifier', 'humidifier', 'dehumidifier', 'space heater',
                'portable ac', 'portable fan', 'tower fan', 'desk fan',

                // ── PCPartPicker / custom build terms ──
                'part list', 'compatibility', 'bottleneck', 'build guide',
                'budget build', 'mid range', 'high end', 'enthusiast', 'entry level',
                'price drop', 'price history', 'price alert', 'price match',
                'in stock', 'out of stock', 'backorder', 'pre-order', 'preorder',
                'new arrival', 'best seller', 'top rated', 'editor choice',
                'unboxing', 'teardown', 'disassembly', 'repair', 'replacement part',
                'diagnostic', 'troubleshoot', 'blue screen', 'bsod', 'crash', 'freeze',
                'slow computer', 'speed up', 'optimize', 'clean install', 'fresh install',
                'dual boot', 'partition', 'format', 'clone', 'disk image', 'recovery',
                'data recovery', 'file transfer', 'migration',

                // Store-specific
                'menu', 'page', 'website', 'site', 'navigation', 'contact', 'about',
                'support', 'help', 'faq', 'policy', 'terms',
                // Greetings (allow polite openers)
                'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening',
                'thanks', 'thank you', 'bye', 'goodbye',
            );

            // Check if message contains any allowed keyword
            $is_on_topic = false;
            foreach ( $allow_keywords as $kw ) {
                if ( strpos( $msg_clean, $kw ) !== false ) {
                    $is_on_topic = true;
                    break;
                }
            }

            // ── BLOCK LIST: obviously off-topic patterns ──
            $block_patterns = array(
                // Coding / programming
                '/\b(write|create|make|build|code|program|script|debug|fix)\s+(me\s+)?(a\s+)?(code|function|class|script|program|app|html|css|javascript|python|php|java|sql|api)\b/i',
                '/\b(coding|programming|developer|compiler|syntax|algorithm|variable|loop|array|database|query)\b/i',
                '/\b(react|angular|vue|node\.?js|django|flask|laravel|express|typescript)\b/i',
                // Math / science
                '/\b(solve|calculate|equation|formula|integral|derivative|calculus|algebra|geometry|trigonometry|theorem)\b/i',
                '/\b(physics|chemistry|biology|molecule|atom|cell|dna|evolution|gravity|quantum)\b/i',
                '/\bwhat\s+is\s+\d+\s*[\+\-\*\/x×÷]\s*\d+/i',
                // History / geography / politics
                '/\b(history|historical|ancient|medieval|world war|civil war|revolution|empire|dynasty|century)\b/i',
                '/\b(geography|continent|capital of|president|prime minister|government|election|politics|political|democracy)\b/i',
                '/\b(country|countries|population|language|religion|culture)\b/i',
                // Entertainment / media
                '/\b(movie|film|song|music|lyrics|singer|actor|actress|celebrity|netflix|spotify|youtube|tiktok|instagram)\b/i',
                '/\b(recipe|cook|cooking|baking|ingredient|food|restaurant|diet|nutrition|calories)\b/i',
                '/\b(joke|funny|riddle|story|poem|poetry|novel|fiction|book|author|write me)\b/i',
                '/\b(sport|football|soccer|basketball|baseball|tennis|cricket|olympics|team|score|player)\b/i',
                '/\b(game(?!r|ing)|video game|play|puzzle|chess|trivia|quiz)\b/i',
                // Personal / life advice
                '/\b(relationship|dating|love|marriage|divorce|boyfriend|girlfriend|crush)\b/i',
                '/\b(medical|health|symptom|disease|medicine|doctor|hospital|diagnos|treatment|therapy|mental health)\b/i',
                '/\b(legal|lawyer|attorney|lawsuit|court|law|rights|sue)\b/i',
                '/\b(finance|invest|stock|crypto|bitcoin|forex|trading|mortgage|loan|insurance|tax|retirement)\b/i',
                '/\b(horoscope|zodiac|astrology|fortune|tarot|dream meaning|spiritual)\b/i',
                // Travel
                '/\b(travel|vacation|holiday|flight|hotel|airbnb|tourist|visa|passport|itinerary)\b/i',
                // Education
                '/\b(homework|assignment|essay|thesis|exam|test|study|university|college|school|teacher|student|class)\b/i',
                '/\b(translate|translation|meaning of|define|definition|synonym|antonym)\b/i',
                // Weather / news
                '/\b(weather|forecast|temperature|rain|snow|sunny|climate)\b/i',
                '/\b(news|headline|breaking|current events|what happened)\b/i',
                // AI / philosophy
                '/\b(who are you|what are you|are you ai|are you human|meaning of life|consciousness|philosophy|ethical|moral)\b/i',
                '/\b(chatgpt|openai|gemini|bard|copilot|midjourney|dall-?e|stable diffusion)\b/i',
            );

            // If no allowed keyword found, or if a block pattern matches → reject
            if ( ! $is_on_topic ) {
                $is_blocked = true;
            } else {
                // Even if an allow keyword matched, check block patterns for dominant off-topic intent
                $is_blocked = false;
                foreach ( $block_patterns as $pattern ) {
                    if ( preg_match( $pattern, $msg_clean ) ) {
                        $is_blocked = true;
                        break;
                    }
                }
            }

            if ( $is_blocked ) {
                $bot_name = get_option( 'shopys_ai_bot_name', 'Shopping Assistant' );
                $decline_messages = array(
                    "I'm **{$bot_name}**, your dedicated product assistant! I'm here to help you find the perfect tech products, compare specs, check prices, and explore our catalog. What are you looking for today?",
                    "That's outside my area of expertise! I specialize in **product recommendations, price comparisons, and helping you find the right tech**. Ask me about laptops, GPUs, peripherals, or anything in our store!",
                    "I appreciate the question, but I'm built specifically to help with **shopping and product advice**! Whether you need a new laptop, want to compare monitors, or need help choosing components — I'm your go-to assistant.",
                );
                $random_msg = $decline_messages[ array_rand( $decline_messages ) ];
                wp_send_json_success( array(
                    'message'  => $random_msg,
                    'products' => array(),
                ) );
            }
        }
    }
    // ── End Product-Only Pre-Filter ──────────────────────────────────

    $catalog    = shopys_ai_get_catalog();
    $store_name = get_bloginfo( 'name' );
    $store_url  = home_url();
    $bot_name   = get_option( 'shopys_ai_bot_name', 'Shopping Assistant' );
    $currency   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

    $catalog_text = '';
    foreach ( $catalog as $p ) {
        $attrs = ! empty( $p['attributes'] ) ? ' | ' . implode( ', ', array_map(
            fn( $k, $v ) => "{$k}: {$v}",
            array_keys( $p['attributes'] ),
            $p['attributes']
        ) ) : '';
        $catalog_text .= "ID:{$p['id']} | {$p['name']} | {$p['price']}" . $attrs . "\n";
    }

    // Get website structure (pages, posts, categories)
    $website_map = shopys_ai_get_website_map();
    $pages_list = '';
    if ( ! empty( $website_map['pages'] ) ) {
        $pages_list = "\n\nWEBSITE PAGES:\n";
        foreach ( array_slice( $website_map['pages'], 0, 20 ) as $page ) {
            $pages_list .= "- {$page['title']}: " . $page['url'] . "\n";
        }
    }

    $posts_list = '';
    if ( ! empty( $website_map['posts'] ) ) {
        $posts_list = "\n\nRECENT BLOG POSTS:\n";
        foreach ( array_slice( $website_map['posts'], 0, 15 ) as $post ) {
            $posts_list .= "- {$post['title']} (" . implode( ', ', $post['categories'] ) . "): " . $post['url'] . "\n";
        }
    }

    $categories_list = '';
    if ( ! empty( $website_map['categories'] ) ) {
        $categories_list = "\n\nBLOG CATEGORIES:\n";
        foreach ( array_slice( $website_map['categories'], 0, 10 ) as $cat ) {
            $categories_list .= "- {$cat['name']} ({$cat['post_count']} posts): " . $cat['url'] . "\n";
        }
    }

    $product_categories_list = '';
    if ( ! empty( $website_map['product_categories'] ) ) {
        $product_categories_list = "\n\nPRODUCT CATEGORIES:\n";
        foreach ( array_slice( $website_map['product_categories'], 0, 15 ) as $pcat ) {
            $product_categories_list .= "- {$pcat['name']} ({$pcat['count']} products): " . $pcat['url'] . "\n";
        }
    }

    $menus_list = '';
    if ( ! empty( $website_map['menus'] ) ) {
        $menus_list = "\n\nWEBSITE NAVIGATION MENUS:\n";
        foreach ( array_slice( $website_map['menus'], 0, 5 ) as $menu ) {
            $menus_list .= "Menu: {$menu['name']}\n";
            foreach ( array_slice( $menu['items'], 0, 15 ) as $item ) {
                $menus_list .= "  - {$item['title']}: " . $item['url'] . "\n";
            }
        }
    }

    // Feature toggle checks
    $is_product_only    = get_option( 'shopys_ai_product_only', '1' ) === '1';
    $is_image_search    = get_option( 'shopys_ai_image_search', '1' ) === '1';
    $is_pdf_reading     = get_option( 'shopys_ai_pdf_reading', '1' ) === '1';
    $is_link_comparison = get_option( 'shopys_ai_link_comparison', '1' ) === '1';
    $is_attachments     = get_option( 'shopys_ai_attachments', '1' ) === '1';

    $system_prompt = "You are {$bot_name}, the AI shopping assistant for **{$store_name}**.

<identity>
You are a knowledgeable, warm, and professional shopping advisor. You combine deep product expertise with genuine helpfulness. You speak with confidence but remain approachable — like a trusted friend who happens to be an expert shopper.
</identity>

<store_context>
- Store: {$store_name}
- URL: {$store_url}
- Currency: {$currency}
</store_context>

<product_catalog>
{$catalog_text}
</product_catalog>
{$pages_list}{$posts_list}{$categories_list}{$product_categories_list}{$menus_list}

<response_formatting>
FORMAT YOUR RESPONSES using proper markdown for a premium reading experience:

**Structure & Hierarchy:**
- Use `## Heading` for main sections and `### Subheading` for subsections
- Use **bold** for product names, key features, and important info
- Use *italic* for emphasis, tips, and side notes
- Use `---` horizontal rules to separate major sections

**Lists & Organization:**
- Use bullet lists (`-`) for features, options, and quick info
- Use numbered lists (`1.`) for steps, rankings, and sequential processes
- Use nested bullets for sub-details

**Code & Technical:**
- Use \`inline code\` for model numbers, SKUs, and technical specs
- Use fenced code blocks with language tag for any code snippets

**Comparisons & Data:**
- When comparing 2+ products, present as a clear structured comparison with headers per product
- Always highlight the **best value** or **recommended** option
- Show prices prominently

**Engagement:**
- Start responses with a brief, direct answer before elaborating
- End with a helpful follow-up question or actionable next step when appropriate
- Use > blockquotes for pro tips, important notes, or customer testimonials
</response_formatting>

<communication_style>
- **Be concise first, detailed on request.** Lead with the answer. Elaborate only when it adds value.
- **Be specific, not generic.** Instead of \"this is a good laptop\", say \"the 16GB RAM and RTX 4060 make this ideal for 1080p gaming and video editing.\"
- **Be honest about trade-offs.** If a cheaper option exists, mention it. If a product has weaknesses, say so tactfully.
- **Match the user's energy.** Quick question → quick answer. Detailed research → thorough breakdown.
- **Use natural language.** Avoid robotic phrasing. Write like you speak — clear, friendly, professional.
- **Never fabricate information.** If you don't know something, say so and suggest where to find it.
</communication_style>

<safety_guidelines>
- Never share personal opinions as facts — present balanced information and let the customer decide
- Never pressure customers to buy — inform, advise, and respect their decision
- If a product might not be right for the customer's stated needs, say so honestly
- Do not make medical, legal, or financial claims about products
- If asked about competitor pricing, provide factual comparisons without disparaging
- Protect customer privacy — never ask for or store sensitive personal information
- If unsure about product availability or specs, say \"let me check\" rather than guessing
</safety_guidelines>

<image_analysis>
When a user uploads an image:
1. Immediately identify what you see — product, object, document, screenshot, etc.
2. If it's a **product photo**: Describe it, then search the catalog for matching or similar items. Always recommend relevant products.
3. If it's a **document/receipt/screenshot**: Read and extract the text clearly, formatted neatly.
4. If it's a **general image**: Describe what you see and ask how you can help.
5. Be specific about visual details — colors, brands, model numbers, text visible in the image.
</image_analysis>

<pdf_analysis>
When a user uploads a PDF:
1. Read through the full document content
2. Provide a clear, well-structured summary with key points
3. Highlight actionable information, deadlines, or important figures
4. Format extracted content cleanly using appropriate markdown
5. Ask if the user wants you to focus on a specific section
</pdf_analysis>

<product_recommendations>
When recommending products, ALWAYS add this tag on its own line at the end of your reply:
[[PRODUCTS:id1,id2,id3]]

Rules:
- Use ONLY product IDs from the catalog above. Maximum 6 IDs.
- Do NOT include the tag if you are not recommending products.
- Recommend products proactively when relevant to the conversation.
- When a user uploads a product image, ALWAYS try to find matching items.
</product_recommendations>

<capabilities>
You have access to:
- Full store product catalog with prices and availability
- Website structure, pages, posts, and navigation menus
- Live website browsing and content analysis
- Image analysis and product visual matching
- PDF document reading and summarization
- Multi-website product comparison (up to 5 URLs)
- Promotion and deal detection
- Page layout and structure analysis
</capabilities>";

    // Product-only mode restriction
    if ( $is_product_only ) {
        $system_prompt .= "

<scope_restriction>
IMPORTANT: You are STRICTLY a product/store assistant. You must ONLY respond to questions related to:
- Product recommendations, search, and catalog browsing
- Product comparisons (including with outside links when allowed)
- Product features, specifications, pricing, and availability
- Store information (shipping, returns, policies, categories)
- Image-based product matching (when enabled)
- PDF product documents (when enabled)

For ANY question that is NOT related to products, shopping, or this store, politely decline and redirect:
\"I'm here to help you with product recommendations and shopping! 😊 Feel free to ask about products, compare items, or search our catalog. How can I help you find what you're looking for?\"

DO NOT answer questions about: general knowledge, coding, math, history, science, entertainment, personal advice, or any topic unrelated to shopping and products.
</scope_restriction>";
    }

    // Conditional capability instructions
    if ( ! $is_image_search ) {
        $system_prompt .= "

<disabled_feature>
Image product search is currently DISABLED. If a user uploads an image asking to find matching products, let them know this feature is not available right now.
</disabled_feature>";
    }

    if ( ! $is_pdf_reading ) {
        $system_prompt .= "

<disabled_feature>
PDF reading and summarization is currently DISABLED. If a user uploads a PDF, let them know this feature is not available right now.
</disabled_feature>";
    }

    if ( ! $is_link_comparison ) {
        $system_prompt .= "

<disabled_feature>
Outside link comparison is currently DISABLED. If a user pastes product URLs from other websites to compare, let them know this feature is not available right now.
</disabled_feature>";
    }


    // Build message history
    $messages = array();
    if ( is_array( $history ) ) {
        foreach ( array_slice( $history, -10 ) as $msg ) {
            if ( ! isset( $msg['role'], $msg['text'] ) ) continue;
            $messages[] = array(
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => sanitize_text_field( $msg['text'] ),
            );
        }
    }
    
    // Check if user is asking "what page am I on?" or similar
    $current_page_keywords = array( 'what page', 'which page', 'what am i on', 'current page', 'what page am i', 'which page am i', 'page i am on', 'page i\'m on', 'where am i', 'what\'s this page' );
    $user_message_lower = strtolower( $message );
    $asking_about_current_page = false;
    foreach ( $current_page_keywords as $keyword ) {
        if ( strpos( $user_message_lower, $keyword ) !== false ) {
            $asking_about_current_page = true;
            break;
        }
    }

    // If asking about current page, try to get page info from referrer or URL hint
    if ( $asking_about_current_page ) {
        $current_page_url = null;
        
        // Check if HTTP_REFERER is available
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $current_page_url = sanitize_url( $_SERVER['HTTP_REFERER'] );
        }
        // Also check for page_url in POST data (can be sent from frontend)
        if ( empty( $current_page_url ) && ! empty( $_POST['page_url'] ) ) {
            $current_page_url = sanitize_url( $_POST['page_url'] );
        }

        if ( ! empty( $current_page_url ) ) {
            $page_info = shopys_ai_identify_page_from_url( $current_page_url );
            if ( $page_info['type'] !== 'unknown' ) {
                $page_context = "The user is currently on the " . $page_info['type'] . ": ";
                if ( $page_info['type'] === 'homepage' ) {
                    $page_context .= "Home/Homepage";
                } elseif ( $page_info['type'] === 'page' ) {
                    $page_context .= "Page: " . $page_info['title'];
                } elseif ( $page_info['type'] === 'post' ) {
                    $page_context .= "Blog Post: " . $page_info['title'] . " (Categories: " . implode( ', ', $page_info['categories'] ) . ")";
                } elseif ( $page_info['type'] === 'category' ) {
                    $page_context .= "Category: " . $page_info['title'] . " (" . $page_info['count'] . " posts)";
                } elseif ( $page_info['type'] === 'product_category' ) {
                    $page_context .= "Product Category: " . $page_info['title'] . " (" . $page_info['count'] . " products)";
                } elseif ( $page_info['type'] === 'archive' ) {
                    $page_context .= "Archive: " . $page_info['title'];
                }
                $page_context .= "\nURL: " . $current_page_url;

                // Add this info to message
                $messages[] = array(
                    'role'    => 'user',
                    'content' => $page_context . "\n\n" . $message
                );

                $result = shopys_ai_call_claude( $api_key, $system_prompt, $messages, $model );

                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( array( 'message' => 'Sorry, something went wrong: ' . $result->get_error_message() ) );
                }

                $product_ids = array();
                $ai_message  = $result;

                if ( preg_match( '/\[\[PRODUCTS:([\d,\s]+)\]\]/i', $result, $match ) ) {
                    $ai_message = preg_replace( '/\[\[PRODUCTS:([\d,\s]+)\]\]/i', '', $result );
                    $ids = array_map( 'intval', array_filter( explode( ',', $match[1] ) ) );
                    $product_ids = array_slice( array_unique( $ids ), 0, 6 );
                }

                $ai_message = trim( $ai_message );

                wp_send_json_success( array(
                    'message'  => $ai_message,
                    'products' => $product_ids,
                ) );
            }
        }
    }
    
    // Check if user is asking about layout, images, or promotions
    $layout_keywords = array( 'layout', 'design', 'structure', 'how does', 'looks like', 'appear', 'navigation bar', 'header', 'footer', 'sidebar', 'organized', 'visual', 'style', 'image', 'photo', 'picture' );
    $promo_keywords = array( 'promotion', 'sale', 'discount', 'offer', 'deal', 'special', 'free shipping', 'coupon', 'price', 'cost', 'cheaper', 'expensive' );
    $is_layout_request = false;
    $is_image_request = false;
    $is_promo_request = false;
    
    foreach ( $layout_keywords as $keyword ) {
        if ( strpos( $user_message_lower, $keyword ) !== false ) {
            $is_layout_request = true;
            if ( strpos( $user_message_lower, 'image' ) !== false || strpos( $user_message_lower, 'photo' ) !== false || strpos( $user_message_lower, 'picture' ) !== false ) {
                $is_image_request = true;
            }
            break;
        }
    }
    
    foreach ( $promo_keywords as $keyword ) {
        if ( strpos( $user_message_lower, $keyword ) !== false ) {
            $is_promo_request = true;
            break;
        }
    }

    // Check if asking about menu count or navigation structure
    $menu_counting_keywords = array( 'how many menu', 'count menu', 'menu items', 'navigation items', 'how many links', 'menu structure', 'menu count' );
    $is_menu_count_request = false;
    foreach ( $menu_counting_keywords as $keyword ) {
        if ( strpos( $user_message_lower, $keyword ) !== false ) {
            $is_menu_count_request = true;
            break;
        }
    }
    
    // If menu count request, provide menu information
    if ( $is_menu_count_request ) {
        $menu_summary = shopys_ai_count_menu_items();
        $menu_context = "MENU STRUCTURE ANALYSIS:\n";
        if ( ! empty( $menu_summary ) ) {
            foreach ( $menu_summary as $menu ) {
                $menu_context .= "- Menu '{$menu['name']}' has " . $menu['count'] . " items: " . implode( ', ', $menu['items'] ) . "\n";
            }
        } else {
            $menu_context .= "No menus found on this website.\n";
        }

        $messages[] = array(
            'role'    => 'user',
            'content' => $menu_context . "\nUser question: " . $message
        );

        $result = shopys_ai_call_claude( $api_key, $system_prompt, $messages, $model );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Sorry, something went wrong: ' . $result->get_error_message() ) );
        }

        $product_ids = array();
        $ai_message  = $result;

        if ( preg_match( '/\[\[PRODUCTS:([\d,\s]+)\]\]/i', $result, $match ) ) {
            $ai_message = preg_replace( '/\[\[PRODUCTS:([\d,\s]+)\]\]/i', '', $result );
            $ids = array_map( 'intval', array_filter( explode( ',', $match[1] ) ) );
            $product_ids = array_slice( array_unique( $ids ), 0, 6 );
        }

        $ai_message = trim( $ai_message );

        wp_send_json_success( array(
            'message'  => $ai_message,
            'products' => $product_ids,
        ) );
    }
    
    // Check if user is asking for product comparison from other websites
    $comparison_keywords = array( 'compare', 'comparison', 'vs', 'versus', 'better', 'difference', 'similar products', 'competitor', 'alternative', 'same price', 'cheaper', 'expensive' );
    $is_comparison_request = false;
    foreach ( $comparison_keywords as $keyword ) {
        if ( strpos( $user_message_lower, $keyword ) !== false ) {
            $is_comparison_request = true;
            break;
        }
    }

    // If comparison request, try to fetch multiple URLs if provided
    // Block outside link comparison if feature is disabled
    if ( ! $is_link_comparison && $is_comparison_request ) {
        // Check if message contains external URLs
        $has_external_url = false;
        if ( preg_match_all( '/(https?:\/\/[^\s]+|www\.[^\s]+)/i', $message, $ext_matches ) ) {
            foreach ( $ext_matches[1] as $ext_url ) {
                $full_url = strpos( $ext_url, 'http' ) !== 0 ? 'https://' . $ext_url : $ext_url;
                if ( strpos( $full_url, $store_url ) !== 0 ) {
                    $has_external_url = true;
                    break;
                }
            }
        }
        if ( $has_external_url ) {
            $is_comparison_request = false; // Disable external comparison
        }
    }

    if ( $is_comparison_request ) {
        // Extract ALL URLs from the message (not just first one)
        $all_url_matches = array();
        if ( preg_match_all( '/(https?:\/\/[^\s]+|www\.[^\s]+)/i', $message, $matches ) ) {
            foreach ( $matches[1] as $url ) {
                $http_url = $url;
                if ( strpos( $http_url, 'http' ) !== 0 ) {
                    $http_url = 'https://' . $http_url;
                }
                $all_url_matches[] = $http_url;
            }
        }

        if ( ! empty( $all_url_matches ) ) {
            $should_fetch_website = true;
            $urls_to_fetch = array_slice( array_unique( $all_url_matches ), 0, 5 ); // Fetch up to 5 URLs for comparison
        } else {
            $should_fetch_website = true; // Still fetch for content even without explicit URLs
        }
    }
    
    // Check if user is asking about website content (pages, posts, categories, etc)
    $web_keywords = array( 'menu', 'header', 'navigation', 'page', 'link', 'structure', 'layout', 'section', 'website', 'site', 'post', 'article', 'blog', 'category', 'product', 'page content', 'about', 'contact' );
    $should_fetch_website = $is_comparison_request ? $should_fetch_website : false;
    if ( ! $should_fetch_website ) {
        $should_fetch_website = false;
    }

    // Check for specific keywords and extract potential page/post names
    if ( ! $is_comparison_request ) {
        foreach ( $web_keywords as $keyword ) {
            if ( strpos( $user_message_lower, $keyword ) !== false ) {
                $should_fetch_website = true;
                break;
            }
        }
    }

    // ALSO check if user provided direct URLs (http, https, www)
    if ( ! $is_comparison_request && preg_match( '/(https?:\/\/[^\s]+|www\.[^\s]+)/i', $message, $url_matches ) ) {
        $should_fetch_website = true;
        $provided_url = $url_matches[1];
        // Add protocol if only www provided
        if ( strpos( $provided_url, 'http' ) !== 0 ) {
            $provided_url = 'https://' . $provided_url;
        }
        $urls_to_fetch[] = $provided_url;
    }

    // If asking about website content, identify which URLs to fetch
    if ( $should_fetch_website ) {
        if ( empty( $urls_to_fetch ) ) {
            $website_map = shopys_ai_get_website_map();
            
            // Try to match user question to specific pages/posts/categories
            foreach ( $website_map['pages'] as $page ) {
                $page_title_lower = strtolower( $page['title'] );
                if ( strpos( $user_message_lower, strtolower( str_replace( array( '-', '_' ), ' ', $page['slug'] ) ) ) !== false ||
                     strpos( $user_message_lower, $page_title_lower ) !== false ) {
                    $urls_to_fetch[] = $page['url'];
                }
            }

            foreach ( $website_map['posts'] as $post ) {
                $post_title_lower = strtolower( $post['title'] );
                if ( strpos( $user_message_lower, $post_title_lower ) !== false ||
                     strpos( $user_message_lower, strtolower( str_replace( array( '-', '_' ), ' ', $post['slug'] ) ) ) !== false ) {
                    $urls_to_fetch[] = $post['url'];
                }
            }

            foreach ( $website_map['categories'] as $cat ) {
                $cat_name_lower = strtolower( $cat['name'] );
                if ( strpos( $user_message_lower, $cat_name_lower ) !== false ) {
                    $urls_to_fetch[] = $cat['url'];
                }
            }

            // Match product categories
            foreach ( $website_map['product_categories'] as $pcat ) {
                $pcat_name_lower = strtolower( $pcat['name'] );
                if ( strpos( $user_message_lower, $pcat_name_lower ) !== false ||
                     strpos( $user_message_lower, strtolower( str_replace( array( '-', '_' ), ' ', $pcat['slug'] ) ) ) !== false ) {
                    $urls_to_fetch[] = $pcat['url'];
                }
            }

            // Match menu items
            foreach ( $website_map['menus'] as $menu ) {
                foreach ( $menu['items'] as $item ) {
                    $item_title_lower = strtolower( $item['title'] );
                    if ( strpos( $user_message_lower, $item_title_lower ) !== false ) {
                        $urls_to_fetch[] = $item['url'];
                    }
                }
            }

            // If no specific match, fetch homepage
            if ( empty( $urls_to_fetch ) ) {
                $urls_to_fetch[] = $store_url;
            }
        }

        // Fetch content from identified URLs
        $all_content = '';
        $fetch_limit = $is_comparison_request ? 5 : 3; // Allow more URLs for comparisons
        foreach ( array_slice( $urls_to_fetch, 0, $fetch_limit ) as $url ) {
            $website_content = shopys_ai_fetch_url( $url );
            if ( ! is_wp_error( $website_content ) ) {
                $content_label = $is_comparison_request ? "[PRODUCT FROM: {$url}]" : "[CONTENT FROM: {$url}]";
                $all_content .= $content_label . "\n" . $website_content['content'] . "\n\n";
                
                // Add layout/structure information if requested
                if ( $is_layout_request && ! empty( $website_content['layout_structure'] ) ) {
                    $all_content .= "[LAYOUT ANALYSIS FOR: {$url}]\n";
                    $all_content .= "Page Title: " . ( $website_content['page_title'] ?: 'N/A' ) . "\n";
                    $all_content .= "Has Navigation: " . $website_content['layout_structure']['has_navigation'] . "\n";
                    $all_content .= "Has Header: " . $website_content['layout_structure']['has_header'] . "\n";
                    $all_content .= "Has Footer: " . $website_content['layout_structure']['has_footer'] . "\n";
                    $all_content .= "Has Sidebar: " . $website_content['layout_structure']['has_sidebar'] . "\n";
                    $all_content .= "Total Images: " . $website_content['layout_structure']['image_count'] . "\n";
                    $all_content .= "Total Links: " . $website_content['layout_structure']['link_count'] . "\n\n";
                }
                
                // Add image information if requested
                if ( $is_image_request && ! empty( $website_content['images'] ) ) {
                    $all_content .= "[IMAGES ON: {$url}]\n";
                    foreach ( $website_content['images'] as $img ) {
                        $all_content .= "- Image: " . $img['alt'] . " (URL: " . $img['src'] . ")\n";
                    }
                    $all_content .= "\n";
                }
                
                // Add promotion information if requested
                if ( $is_promo_request && ! empty( $website_content['promotions'] ) ) {
                    $all_content .= "[PROMOTIONS/SALES ON: {$url}]\n";
                    foreach ( $website_content['promotions'] as $promo ) {
                        $all_content .= "- " . $promo . "\n";
                    }
                    $all_content .= "\n";
                }
            }
        }

        if ( ! empty( $all_content ) ) {
            $content_instruction = $is_comparison_request 
                ? "Based on the above product information from multiple sources, provide a detailed comparison including prices, features, availability, ratings, and specifications. Highlight similarities and differences."
                : ( $is_layout_request 
                    ? "Based on the above website layout and structure information, describe how the website is organized, its visual structure, and component placement."
                    : ( $is_image_request
                        ? "Based on the above image information, describe what images are on the website and what they represent."
                        : ( $is_promo_request
                            ? "Based on the above promotions and sales information, tell me about the current offers and deals."
                            : "Based on the above website content, "
                        )
                    )
                );
            
            $messages[] = array(
                'role'    => 'user',
                'content' => "[WEBSITE CONTENT ANALYSIS]\n\n" . $all_content . "[END CONTENT]\n\n" . $content_instruction . " " . $message
            );
        } else {
            $messages[] = array( 'role' => 'user', 'content' => $message );
        }
    } else {
        $messages[] = array( 'role' => 'user', 'content' => $message );
    }

    // Block attachments if feature is disabled
    if ( ! $is_attachments && ! empty( $file_attachments ) ) {
        $file_attachments = array();
    }

    // Block PDF attachments if PDF reading is disabled
    if ( ! $is_pdf_reading && ! empty( $file_attachments ) ) {
        $file_attachments = array_filter( $file_attachments, function( $att ) {
            return ! isset( $att['type'] ) || $att['type'] !== 'application/pdf';
        } );
        $file_attachments = array_values( $file_attachments );
    }

    // Block image attachments if image search is disabled
    if ( ! $is_image_search && ! empty( $file_attachments ) ) {
        $allowed_image_types_check = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        $file_attachments = array_filter( $file_attachments, function( $att ) use ( $allowed_image_types_check ) {
            return ! isset( $att['type'] ) || ! in_array( $att['type'], $allowed_image_types_check, true );
        } );
        $file_attachments = array_values( $file_attachments );
    }

    // Inject image/PDF attachments into the last user message
    if ( ! empty( $file_attachments ) && is_array( $file_attachments ) ) {
        $last_idx = count( $messages ) - 1;
        $last_msg = $messages[ $last_idx ];

        // Detect special commands from the message
        $user_text = is_string( $last_msg['content'] ) ? $last_msg['content'] : '';
        $is_find_product = strpos( $user_text, '[FIND_PRODUCT]' ) !== false;
        $is_read_text    = strpos( $user_text, '[READ_TEXT]' ) !== false;
        $is_summarize    = strpos( $user_text, '[SUMMARIZE]' ) !== false;

        // Strip command tags from user text
        $clean_text = trim( preg_replace( '/\[(FIND_PRODUCT|READ_TEXT|SUMMARIZE)\]\s*/', '', $user_text ) );

        // Build smart prompt based on command
        if ( $is_find_product ) {
            $prompt_text = "Look at this image carefully. Identify the product, item, or object shown. "
                . "Then search the STORE PRODUCTS list and recommend the closest matching or most similar products. "
                . "Describe what you see first, then recommend products. Always include the [[PRODUCTS:id1,id2,...]] tag.";
            if ( ! empty( $clean_text ) ) {
                $prompt_text .= "\n\nUser note: " . $clean_text;
            }
        } elseif ( $is_read_text ) {
            $prompt_text = "Extract and read ALL text content from this image/document. "
                . "Present the text clearly and in the original order. If it's a receipt, invoice, or document, format it neatly.";
            if ( ! empty( $clean_text ) ) {
                $prompt_text .= "\n\nUser note: " . $clean_text;
            }
        } elseif ( $is_summarize ) {
            $prompt_text = "Provide a detailed summary of this image/document. "
                . "Include key points, main topics, important details, and any actionable information.";
            if ( ! empty( $clean_text ) ) {
                $prompt_text .= "\n\nUser note: " . $clean_text;
            }
        } elseif ( ! empty( $clean_text ) ) {
            $prompt_text = $clean_text;
        } else {
            $prompt_text = 'Please analyze this image and describe what you see. If it looks like a product, recommend similar items from the store.';
        }

        // Convert to multi-part array for Claude vision API
        $content_parts = array();

        // Add image blocks
        $allowed_image_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        foreach ( array_slice( $file_attachments, 0, 5 ) as $att ) {
            if ( ! isset( $att['type'], $att['data'] ) ) continue;
            if ( in_array( $att['type'], $allowed_image_types, true ) ) {
                $content_parts[] = array(
                    'type'   => 'image',
                    'source' => array(
                        'type'         => 'base64',
                        'media_type'   => $att['type'],
                        'data'         => $att['data'],
                    ),
                );
            } elseif ( $att['type'] === 'application/pdf' ) {
                $content_parts[] = array(
                    'type'   => 'document',
                    'source' => array(
                        'type'         => 'base64',
                        'media_type'   => 'application/pdf',
                        'data'         => $att['data'],
                    ),
                );
            }
        }

        // Add text prompt after media
        $content_parts[] = array( 'type' => 'text', 'text' => $prompt_text );

        if ( ! empty( $content_parts ) ) {
            $messages[ $last_idx ]['content'] = $content_parts;
        }
    }

    $result = shopys_ai_call_claude( $api_key, $system_prompt, $messages, $model );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => 'Sorry, something went wrong: ' . $result->get_error_message() ) );
    }

    // Parse [[PRODUCTS:...]] tag out of Claude's plain-text response
    $product_ids = array();
    $ai_message  = $result;

    if ( preg_match( '/\[\[PRODUCTS:([\d,\s]+)\]\]/i', $result, $match ) ) {
        $product_ids = array_filter( array_map( 'intval', explode( ',', $match[1] ) ) );
        $ai_message  = trim( preg_replace( '/\[\[PRODUCTS:[\d,\s]+\]\]/i', '', $result ) );
    }

    // Fetch full product data for the cards
    $recommended = array();
    foreach ( $product_ids as $pid ) {
        $product = wc_get_product( $pid );
        if ( ! $product || $product->get_status() !== 'publish' ) continue;

        $image    = '';
        $thumb_id = $product->get_image_id();
        if ( $thumb_id ) {
            $src = wp_get_attachment_image_src( $thumb_id, 'woocommerce_thumbnail' );
            if ( $src ) $image = $src[0];
        }
        if ( empty( $image ) ) {
            $image = wc_placeholder_img_src( 'woocommerce_thumbnail' );
        }

        $categories = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'names' ) );

        $recommended[] = array(
            'id'          => $pid,
            'name'        => $product->get_name(),
            'price_html'  => $product->get_price_html(),
            'price'       => $product->get_price(),
            'image'       => $image,
            'url'         => get_permalink( $pid ),
            'category'    => ( ! is_wp_error( $categories ) && ! empty( $categories ) ) ? $categories[0] : '',
            'stock'       => $product->get_stock_status(),
            'add_to_cart' => $product->is_type( 'simple' ) ? '?add-to-cart=' . $pid : get_permalink( $pid ),
            'type'        => $product->get_type(),
        );
    }

    wp_send_json_success( array(
        'message'  => $ai_message,
        'products' => $recommended,
    ) );
}

/* ═══════════════════════════════════════════════════════════════════
   5. WEB BROWSING AJAX ENDPOINT
   ═══════════════════════════════════════════════════════════════════ */

add_action( 'wp_ajax_shopys_ai_fetch_url', 'shopys_ai_fetch_url_handler' );
function shopys_ai_fetch_url_handler() {
    check_ajax_referer( 'shopys_ai_nonce', 'nonce' );

    $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
    
    if ( empty( $url ) ) {
        wp_send_json_error( array( 'message' => 'No URL provided' ) );
    }

    $result = shopys_ai_fetch_url( $url );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => 'Failed to fetch: ' . $result->get_error_message() ) );
    }

    wp_send_json_success( $result );
}

/* ═══════════════════════════════════════════════════════════════════
   6. ENQUEUE ASSETS & RENDER WIDGET
   ═══════════════════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', 'shopys_ai_chatbot_assets' );
function shopys_ai_chatbot_assets() {
    if ( get_option( 'shopys_ai_enabled', '1' ) !== '1' ) return;
    if ( ! class_exists( 'WooCommerce' ) ) return;
    if ( is_admin() ) return;

    // Highlight.js for code syntax highlighting
    wp_enqueue_style(
        'highlightjs-css',
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css',
        array(),
        '11.9.0'
    );

    wp_enqueue_script(
        'highlightjs',
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
        array(),
        '11.9.0',
        true
    );

    wp_enqueue_style(
        'shopys-ai-chatbot',
        get_stylesheet_directory_uri() . '/css/ai-chatbot.css',
        array( 'highlightjs-css' ),
        filemtime( get_stylesheet_directory() . '/css/ai-chatbot.css' )
    );

    wp_enqueue_script(
        'shopys-ai-chatbot-js',
        get_stylesheet_directory_uri() . '/js/ai-chatbot.js',
        array( 'highlightjs' ),
        filemtime( get_stylesheet_directory() . '/js/ai-chatbot.js' ),
        true
    );

    $bot_name    = get_option( 'shopys_ai_bot_name', 'Shopping Assistant' );
    $welcome_msg = get_option( 'shopys_ai_welcome_msg', "Hi! I'm your shopping assistant.\nAsk me anything — I can recommend products based on your needs!" );

    wp_localize_script( 'shopys-ai-chatbot-js', 'shopysAI', array(
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'nonce'           => wp_create_nonce( 'shopys_ai_nonce' ),
        'bot_name'        => $bot_name,
        'welcome_msg'     => $welcome_msg,
        'store_name'      => get_bloginfo( 'name' ),
        'feat_image_search'    => get_option( 'shopys_ai_image_search', '1' ),
        'feat_pdf_reading'     => get_option( 'shopys_ai_pdf_reading', '1' ),
        'feat_link_comparison' => get_option( 'shopys_ai_link_comparison', '1' ),
        'feat_attachments'     => get_option( 'shopys_ai_attachments', '1' ),
    ) );
}

add_action( 'wp_footer', 'shopys_ai_chatbot_widget', 50 );
function shopys_ai_chatbot_widget() {
    if ( get_option( 'shopys_ai_enabled', '1' ) !== '1' ) return;
    if ( ! class_exists( 'WooCommerce' ) ) return;
    if ( is_admin() ) return;
    ?>
    <!-- AI Chatbot Widget -->
    <div id="sai-chatbot" class="sai-chatbot">
        <!-- Chat Toggle Button -->
        <button class="sai-toggle" id="sai-toggle" aria-label="Open chat assistant">
            <svg class="sai-icon-chat" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/>
            </svg>
            <svg class="sai-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <!-- Chat Window -->
        <div class="sai-window" id="sai-window">
            <!-- Resize Handles -->
            <div class="sai-resize-handle sai-resize-handle-top" data-resize="top"></div>
            <div class="sai-resize-handle sai-resize-handle-left" data-resize="left"></div>
            <div class="sai-resize-handle sai-resize-handle-corner" data-resize="corner"></div>
            <!-- Header -->
            <div class="sai-header">
                <div class="sai-header-info">
                    <div class="sai-avatar">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/>
                            <path d="M9 14h6a5 5 0 0 1 5 5v1H4v-1a5 5 0 0 1 5-5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="sai-header-name" id="sai-header-name"></div>
                        <div class="sai-header-status">Online — Ask me anything</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <button class="sai-new-chat" id="sai-new-chat" aria-label="New chat" title="New chat">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </button>
                    <button class="sai-fullscreen" id="sai-fullscreen" aria-label="Expand to full screen">
                        <svg class="sai-icon-expand" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/>
                            <line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
                        </svg>
                        <svg class="sai-icon-collapse" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/>
                            <line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/>
                        </svg>
                    </button>
                    <button class="sai-close" id="sai-close" aria-label="Close chat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="sai-messages" id="sai-messages"></div>

            <!-- Model Toolbar -->
            <div class="sai-toolbar">
                <label class="sai-model-label" for="sai-model-select">Model:</label>
                <select class="sai-model-select" id="sai-model-select" aria-label="Select AI model">
                    <option value="claude-haiku-4-5-20251001">Haiku — Fast</option>
                    <option value="claude-sonnet-4-6">Sonnet — Smart</option>
                    <option value="claude-opus-4-6" selected>Opus — Most Capable</option>
                </select>
            </div>

            <!-- Attachment Preview -->
            <div class="sai-attach-preview" id="sai-attach-preview"></div>

            <!-- Input Area -->
            <div class="sai-input-area">
                <input type="file" id="sai-file-input" accept="image/*,.pdf" multiple style="display:none" />
                <button class="sai-attach-btn" id="sai-attach-btn" aria-label="Attach file" title="Attach image or PDF">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                </button>
                <textarea class="sai-input" id="sai-input" placeholder="Ask about products, recommendations..." rows="1"></textarea>
                <button class="sai-send" id="sai-send" aria-label="Send message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php
}