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
}

function shopys_ai_settings_page() {
    $enabled     = get_option( 'shopys_ai_enabled', '1' );
    $api_key     = get_option( 'shopys_ai_api_key', '' );
    $bot_name    = get_option( 'shopys_ai_bot_name', 'Shopping Assistant' );
    $welcome_msg = get_option( 'shopys_ai_welcome_msg', "Hi! I'm your shopping assistant.\nAsk me anything — I can recommend products based on your needs!" );
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
        'ajax_url'    => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'shopys_ai_nonce' ),
        'bot_name'    => $bot_name,
        'welcome_msg' => $welcome_msg,
        'store_name'  => get_bloginfo( 'name' ),
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