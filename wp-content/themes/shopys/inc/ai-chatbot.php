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
   3. CLAUDE API CALLER
   ═══════════════════════════════════════════════════════════════════ */

function shopys_ai_call_claude( $api_key, $system_prompt, $messages, $model = 'claude-haiku-4-5-20251001' ) {
    $body = array(
        'model'      => $model,
        'max_tokens' => 1024,
        'system'     => $system_prompt,
        'messages'   => $messages,
    );

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'headers' => array(
            'Content-Type'      => 'application/json',
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
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
    $model = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'claude-haiku-4-5-20251001';
    if ( ! in_array( $model, $allowed_models, true ) ) {
        $model = 'claude-haiku-4-5-20251001';
    }

    if ( empty( $message ) ) {
        wp_send_json_error( array( 'message' => 'Please type a message.' ) );
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

    $system_prompt = "You are {$bot_name}, a helpful and friendly shopping assistant for {$store_name} ({$store_url}).
Currency: {$currency}

STORE PRODUCTS:
{$catalog_text}

INSTRUCTIONS:
- Chat naturally and helpfully like an assistant. Answer any question the customer asks.
- For greetings, small talk, or general questions — just reply conversationally. Do NOT show products.
- Only recommend products when the customer is clearly asking about buying, looking for, or comparing products.
- When you do recommend products, add this tag at the very end of your reply (on its own line): [[PRODUCTS:id1,id2,id3]]
  Use only IDs from the store products list above. Maximum 6 IDs.
- If not recommending any products, do NOT include the [[PRODUCTS:...]] tag at all.";

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
    $messages[] = array( 'role' => 'user', 'content' => $message );

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
   5. ENQUEUE ASSETS & RENDER WIDGET
   ═══════════════════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', 'shopys_ai_chatbot_assets' );
function shopys_ai_chatbot_assets() {
    if ( get_option( 'shopys_ai_enabled', '1' ) !== '1' ) return;
    if ( ! class_exists( 'WooCommerce' ) ) return;
    if ( is_admin() ) return;

    wp_enqueue_style(
        'shopys-ai-chatbot',
        get_stylesheet_directory_uri() . '/css/ai-chatbot.css',
        array(),
        filemtime( get_stylesheet_directory() . '/css/ai-chatbot.css' )
    );

    wp_enqueue_script(
        'shopys-ai-chatbot-js',
        get_stylesheet_directory_uri() . '/js/ai-chatbot.js',
        array(),
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
                    <option value="claude-opus-4-6">Opus — Most Capable</option>
                </select>
            </div>

            <!-- Input Area -->
            <div class="sai-input-area">
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