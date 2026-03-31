<?php
/**
 * Telegram Login Integration
 *
 * HOW TO CONFIGURE:
 * 1. Message @BotFather on Telegram → /newbot → get your bot username & token
 * 2. Message @BotFather → /setdomain → select your bot → enter your site domain
 * 3. Replace the two constants below with your real values
 *
 * @package Shopys
 */

// ── Configuration ──────────────────────────────────────────────────────────────
if ( ! defined( 'SHOPYS_TG_BOT_USERNAME' ) ) {
    define( 'SHOPYS_TG_BOT_USERNAME', 'vstorecenter_bot' ); // your bot @username (without @)
}
if ( ! defined( 'SHOPYS_TG_BOT_TOKEN' ) ) {
    define( 'SHOPYS_TG_BOT_TOKEN', '8727268613:AAHqUtx0L7wjF5qMn9CSFYYPweIpKCdzZ7g' ); // your bot token from @BotFather
}

// ── 1. Handle Telegram callback (runs early, before any output) ────────────────
add_action( 'init', 'shopys_handle_telegram_auth' );

function shopys_handle_telegram_auth() {
    if ( ! isset( $_GET['tg_auth'] ) || $_GET['tg_auth'] !== '1' ) {
        return;
    }
    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    // Collect all GET params from Telegram
    $data = $_GET;
    unset( $data['tg_auth'] );

    // Verify the Telegram hash
    if ( ! shopys_verify_telegram_data( $data ) ) {
        wp_die( 'Telegram authentication failed. Invalid data.', 'Auth Error', array( 'response' => 403 ) );
    }

    // Check auth timestamp (max 1 day old)
    if ( ( time() - intval( $data['auth_date'] ) ) > 86400 ) {
        wp_die( 'Telegram authentication expired. Please try again.', 'Auth Expired', array( 'response' => 403 ) );
    }

    $tg_id        = sanitize_text_field( $data['id'] );
    $tg_firstname = sanitize_text_field( $data['first_name'] ?? '' );
    $tg_lastname  = sanitize_text_field( $data['last_name'] ?? '' );
    $tg_username  = sanitize_text_field( $data['username'] ?? '' );
    $tg_photo     = esc_url_raw( $data['photo_url'] ?? '' );

    // Find or create a WP user linked to this Telegram ID
    $user = shopys_get_or_create_telegram_user( $tg_id, $tg_firstname, $tg_lastname, $tg_username, $tg_photo );

    if ( is_wp_error( $user ) ) {
        wp_die( esc_html( $user->get_error_message() ), 'Registration Error', array( 'response' => 500 ) );
    }

    // Log the user in
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );
    do_action( 'wp_login', $user->user_login, $user );

    // Redirect back to wherever they came from (or home)
    $redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : home_url( '/' );
    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Verify Telegram login data hash using the bot token.
 */
function shopys_verify_telegram_data( array $data ) {
    if ( empty( $data['hash'] ) ) {
        return false;
    }
    $hash = $data['hash'];
    unset( $data['hash'] );

    ksort( $data );
    $check_string = implode( "\n", array_map(
        fn( $k, $v ) => "{$k}={$v}",
        array_keys( $data ),
        $data
    ) );

    $secret = hash( 'sha256', SHOPYS_TG_BOT_TOKEN, true );
    $expected_hash = hash_hmac( 'sha256', $check_string, $secret );

    return hash_equals( $expected_hash, $hash );
}

/**
 * Find existing WP user by Telegram ID, or create a new one.
 */
function shopys_get_or_create_telegram_user( $tg_id, $firstname, $lastname, $username, $photo_url ) {
    // Search by stored telegram_id meta
    $existing = get_users( array(
        'meta_key'   => 'telegram_id',
        'meta_value' => $tg_id,
        'number'     => 1,
    ) );

    if ( ! empty( $existing ) ) {
        // Update photo on every login
        if ( $photo_url ) {
            update_user_meta( $existing[0]->ID, 'telegram_photo', $photo_url );
        }
        return $existing[0];
    }

    // Build a unique login name
    $login_base = $username ?: sanitize_user( $firstname . '_' . $tg_id );
    $login      = $login_base;
    $suffix     = 1;
    while ( username_exists( $login ) ) {
        $login = $login_base . '_' . $suffix++;
    }

    // Build a unique email placeholder (Telegram doesn't give emails)
    $email = $login . '@telegram.tg.noreply';

    $user_id = wp_insert_user( array(
        'user_login'   => $login,
        'user_email'   => $email,
        'display_name' => trim( $firstname . ' ' . $lastname ),
        'first_name'   => $firstname,
        'last_name'    => $lastname,
        'role'         => 'subscriber',
        'user_pass'    => wp_generate_password( 32, true, true ),
    ) );

    if ( is_wp_error( $user_id ) ) {
        return $user_id;
    }

    update_user_meta( $user_id, 'telegram_id',    $tg_id );
    update_user_meta( $user_id, 'telegram_photo', $photo_url );

    return get_user_by( 'ID', $user_id );
}

// ── 2. Enqueue CSS ─────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'shopys_tg_login_assets' );
function shopys_tg_login_assets() {
    wp_enqueue_style(
        'shopys-tg-login',
        get_stylesheet_directory_uri() . '/css/telegram-login.css',
        array(),
        filemtime( get_stylesheet_directory() . '/css/telegram-login.css' )
    );
}

// ── 3. Render the Login / User button in the header ───────────────────────────
add_action( 'open_shop_below_header', 'shopys_render_tg_login_button', 5 );

function shopys_render_tg_login_button() {
    // Build the callback URL (current page + tg_auth=1)
    $callback_url = add_query_arg( 'tg_auth', '1', home_url( '/' ) );
    $bot_username = SHOPYS_TG_BOT_USERNAME;

    if ( is_user_logged_in() ) {
        // ── Logged-in state ──────────────────────────────────────────────────
        $user       = wp_get_current_user();
        $photo      = get_user_meta( $user->ID, 'telegram_photo', true );
        $name       = esc_html( $user->display_name ?: $user->user_login );
        $logout_url = esc_url( wp_logout_url( home_url( '/' ) ) );
        ?>
        <div class="shopys-tg-user" id="shopys-tg-user">
            <button class="shopys-tg-avatar-btn" id="shopys-tg-avatar-btn" type="button" aria-haspopup="true" aria-expanded="false">
                <?php if ( $photo ) : ?>
                    <img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo $name; ?>" class="shopys-tg-avatar-img" />
                <?php else : ?>
                    <span class="shopys-tg-avatar-initials"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
                <?php endif; ?>
                <span class="shopys-tg-name"><?php echo $name; ?></span>
                <svg class="shopys-tg-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="shopys-tg-dropdown" id="shopys-tg-dropdown" role="menu">
                <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" class="shopys-tg-dd-item" role="menuitem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    <?php esc_html_e( 'My Account', 'shopys' ); ?>
                </a>
                <a href="<?php echo $logout_url; ?>" class="shopys-tg-dd-item shopys-tg-logout" role="menuitem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <?php esc_html_e( 'Logout', 'shopys' ); ?>
                </a>
            </div>
        </div>
        <script>
        (function(){
            var btn      = document.getElementById('shopys-tg-avatar-btn');
            var dropdown = document.getElementById('shopys-tg-dropdown');
            if (!btn || !dropdown) return;
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                var open = dropdown.classList.toggle('shopys-tg-dd--open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function(){
                dropdown.classList.remove('shopys-tg-dd--open');
                btn.setAttribute('aria-expanded', 'false');
            });
        })();
        </script>
        <?php
    } else {
        // ── Logged-out state: Login button with Telegram Widget dropdown ─────
        ?>
        <div class="shopys-tg-login-wrap" id="shopys-tg-login-wrap">
            <button class="shopys-tg-login-btn" id="shopys-tg-login-trigger" type="button" aria-haspopup="true" aria-expanded="false" title="Login with Telegram">
                <svg class="shopys-tg-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.954l11.57-4.461c.537-.194 1.006.131.833.942z"/>
                </svg>
                <span><?php esc_html_e( 'Login', 'shopys' ); ?></span>
                <svg class="shopys-tg-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="shopys-tg-login-dropdown" id="shopys-tg-login-dropdown">
                <div class="shopys-tg-login-dd-header">Sign in with Telegram</div>
                <div class="shopys-tg-widget-wrap">
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                        data-telegram-login="<?php echo esc_attr( $bot_username ); ?>"
                        data-size="large"
                        data-radius="8"
                        data-auth-url="<?php echo esc_url( $callback_url ); ?>"
                        data-request-access="write">
                    </script>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var btn = document.getElementById('shopys-tg-login-trigger');
            var dd  = document.getElementById('shopys-tg-login-dropdown');
            if (!btn || !dd) return;
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                var open = dd.classList.toggle('shopys-tg-dd--open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function(e){
                if (!dd.contains(e.target)) {
                    dd.classList.remove('shopys-tg-dd--open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        })();
        </script>
        <?php
    }
}
