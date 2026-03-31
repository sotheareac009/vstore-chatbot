<?php
/**********************/
// child style enqueue
/**********************/
function shopys_styles(){

    // Enqueue our child style.css with our own version for cache busting
    $childVersion = filemtime( get_stylesheet_directory() . '/style.css' );

    wp_enqueue_style('shopys-styles', get_stylesheet_uri(), array(), $childVersion);

    wp_add_inline_style('shopys-styles', shopys_custom_styles());

}

add_action('wp_enqueue_scripts', 'shopys_styles', 100);

define('shopys_FOOTER_LAYOUT_TWO', get_theme_file_uri(). "/images/widget-footer-2.png");

/**********************/
//customize setting
/**********************/

function shopys_setting( $wp_customize ){

/******************/
// theme color
/******************/
 $wp_customize->add_setting('open_shop_theme_clr', array(
        'default'        => '#0a0101',
        'capability'     => 'edit_theme_options',
        'sanitize_callback' => 'open_shop_sanitize_color',
        'transport'         => 'postMessage',
    ));
$wp_customize->add_control( 
    new WP_Customize_Color_Control($wp_customize,'open_shop_theme_clr', array(
        'label'      => __('Theme Color', 'shopys' ),
        'section'    => 'open-shop-gloabal-color',
        'settings'   => 'open_shop_theme_clr',
        'priority' => 1,
    ) ) 
 );    
/***********************************/  
// menu alignment
/***********************************/ 
$wp_customize->add_setting('open_shop_menu_alignment', array(
                'default'               => 'right',
                'sanitize_callback'     => 'open_shop_sanitize_select',
            ) );
$wp_customize->add_control( new Open_Shop_Customizer_Buttonset_Control( $wp_customize, 'open_shop_menu_alignment', array(
                'label'                 => esc_html__( 'Menu Alignment', 'shopys' ),
                'section'               => 'open-shop-main-header',
                'settings'              => 'open_shop_menu_alignment',
                'choices'               => array(
                    'left'              => esc_html__( 'Left', 'shopys' ),
                    'center'        => esc_html__( 'center', 'shopys' ),
                    'right'             => esc_html__( 'Right', 'shopys' ),
                ),
        ) ) );
// excerpt length
    $wp_customize->add_setting('open_shop_blog_expt_length', array(
            'default'           =>'15',
            'capability'        => 'edit_theme_options',
            'sanitize_callback' =>'open_shop_sanitize_number',
        )
    );
    $wp_customize->add_control('open_shop_blog_expt_length', array(
            'type'        => 'number',
            'section'     => 'open-shop-section-blog-group',
            'label'       => __( 'Excerpt Length', 'shopys' ),
            'input_attrs' => array(
                'min'  => 0,
                'step' => 1,
                'max'  => 3000,
            ),
             'priority'   =>10,
        )
    );
//Main menu option
$wp_customize->add_setting('open_shop_main_header_option', array(
        'default'        => 'none',
        'capability'     => 'edit_theme_options',
        'sanitize_callback' => 'open_shop_sanitize_select',
    ));
$wp_customize->add_control( 'open_shop_main_header_option', array(
        'settings' => 'open_shop_main_header_option',
        'label'    => __('Column 1','shopys'),
        'section'  => 'open-shop-main-header',
        'type'     => 'select',
        'choices'    => array(
        'none'       => __('None','shopys'),
        'callto'     => __('Call-To','shopys'),
        'button'     => __('Button','shopys'),
        'widget'     => __('Widget','shopys'),     
        ),
    ));


/******************/
//Widegt footer
/******************/
if(class_exists('Open_Shop_WP_Customize_Control_Radio_Image')){
               $wp_customize->add_setting(
               'open_shop_bottom_footer_widget_layout', array(
               'default'           => 'ft-wgt-none',
               'sanitize_callback' => 'sanitize_text_field',
            )
        );
$wp_customize->add_control(
            new Open_Shop_WP_Customize_Control_Radio_Image(
                $wp_customize, 'open_shop_bottom_footer_widget_layout', array(
                    'label'    => esc_html__( 'Layout','shopys'),
                    'section'  => 'open-shop-widget-footer',
                    'choices'  => array(
                       'ft-wgt-none'   => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_NONE,
                        ),
                        'ft-wgt-one'   => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_1,
                        ),
                        'ft-wgt-two' => array(
                            'url' => shopys_FOOTER_LAYOUT_TWO,
                        ),
                        'ft-wgt-three' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_3,
                        ),
                        'ft-wgt-four' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_4,
                        ),
                        'ft-wgt-five' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_5,
                        ),
                        'ft-wgt-six' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_6,
                        ),
                        'ft-wgt-seven' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_7,
                        ),
                        'ft-wgt-eight' => array(
                            'url' => OPEN_SHOP_FOOTER_WIDGET_LAYOUT_8,
                        ),
                    ),
                )
            )
        );
    } 

/******************************/
/* Widget Redirect
/****************************/
if (class_exists('Open_Shop_Widegt_Redirect')){ 
$wp_customize->add_setting(
            'open_shop_bottom_footer_widget_redirect', array(
            'sanitize_callback' => 'sanitize_text_field',
     )
);
$wp_customize->add_control(
            new Open_Shop_Widegt_Redirect(
                $wp_customize, 'open_shop_bottom_footer_widget_redirect', array(
                    'section'      => 'open-shop-widget-footer',
                    'button_text'  => esc_html__( 'Go To Widget', 'shopys' ),
                    'button_class' => 'focus-customizer-widget-redirect',  
                )
            )
        );
} 

}

add_action( 'customize_register', 'shopys_setting', 100 );

/***************************/
//custom style
/***************************/
function shopys_custom_styles(){
$open_shop_theme_clr = esc_html(get_theme_mod('open_shop_theme_clr','#0a0101'));
$open_shop_color_scheme = esc_html(get_theme_mod('open_shop_color_scheme','opn-light'));

$shopys_custom_style=""; 

$shopys_custom_style.="a:hover, .open-shop-menu li a:hover, .open-shop-menu .current-menu-item a,.woocommerce .thunk-woo-product-list .price,.thunk-product-hover .th-button.add_to_cart_button, .woocommerce ul.products .thunk-product-hover .add_to_cart_button, .woocommerce .thunk-product-hover a.th-butto, .woocommerce ul.products li.product .product_type_variable, .woocommerce ul.products li.product a.button.product_type_grouped,.thunk-compare .compare-button a:hover, .thunk-product-hover .th-button.add_to_cart_button:hover, .woocommerce ul.products .thunk-product-hover .add_to_cart_button :hover, .woocommerce .thunk-product-hover a.th-button:hover,.thunk-product .yith-wcwl-wishlistexistsbrowse.show:before, .thunk-product .yith-wcwl-wishlistaddedbrowse.show:before,.woocommerce ul.products li.product.thunk-woo-product-list .price,.summary .yith-wcwl-add-to-wishlist.show .add_to_wishlist::before, .summary .yith-wcwl-add-to-wishlist .yith-wcwl-wishlistaddedbrowse.show a::before, .summary .yith-wcwl-add-to-wishlist .yith-wcwl-wishlistexistsbrowse.show a::before,.woocommerce .entry-summary a.compare.button.added:before,.header-icon a:hover,.thunk-related-links .nav-links a:hover,.woocommerce .thunk-list-view ul.products li.product.thunk-woo-product-list .price,.woocommerce .woocommerce-error .button, .woocommerce .woocommerce-info .button, .woocommerce .woocommerce-message .button,article.thunk-post-article .thunk-readmore.button,.thunk-wishlist a:hover, .thunk-compare a:hover,.woocommerce .thunk-product-hover a.th-button,.woocommerce ul.cart_list li .woocommerce-Price-amount, .woocommerce ul.product_list_widget li .woocommerce-Price-amount,.open-shop-load-more button, 
.summary .yith-wcwl-add-to-wishlist .yith-wcwl-wishlistaddedbrowse a::before,
 .summary .yith-wcwl-add-to-wishlist .yith-wcwl-wishlistexistsbrowse a::before,.thunk-hglt-icon,.thunk-product .yith-wcwl-wishlistexistsbrowse:before, .thunk-product .yith-wcwl-wishlistaddedbrowse:before,.woocommerce a.button.product_type_simple,.woosw-btn:hover:before,.woosw-added:before,.wooscp-btn:hover:before,.woocommerce #reviews #comments .star-rating span ,.woocommerce p.stars a,.woocommerce .woocommerce-product-rating .star-rating,.woocommerce .star-rating span::before, .woocommerce .entry-summary a.th-product-compare-btn.btn_type:before{color:{$open_shop_theme_clr};} header #thaps-search-button,header #thaps-search-button:hover,.nav-links .page-numbers.current, .nav-links .page-numbers:hover{background:{$open_shop_theme_clr};}";

 if($open_shop_color_scheme=='opn-dark'){
$shopys_custom_style.="body.open-shop-dark a:hover, body.open-shop-dark .open-shop-menu > li > a:hover, body.open-shop-dark .open-shop-menu li ul.sub-menu li a:hover,body.open-shop-dark .thunk-product-cat-list li a:hover,body.open-shop-dark .main-header a:hover, body.open-shop-dark #sidebar-primary .open-shop-widget-content a:hover,.open-shop-dark .thunk-woo-product-list .woocommerce-loop-product__title a:hover{color:{$open_shop_theme_clr}} body.open-shop-dark #searchform [type='submit']{background:{$open_shop_theme_clr};border-color:{$open_shop_theme_clr}}";
}

$shopys_custom_style.=".toggle-cat-wrap,#search-button,.thunk-icon .cart-icon, .single_add_to_cart_button.button.alt, .woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt, .woocommerce #respond input#submit, .woocommerce button.button, .woocommerce input.button,.thunk-woo-product-list .thunk-quickview a,.cat-list a:after,.tagcloud a:hover, .thunk-tags-wrapper a:hover,.btn-main-header,.woocommerce div.product form.cart .button, .thunk-icon .cart-icon .taiowc-cart-item{background:{$open_shop_theme_clr}}
  .open-cart p.buttons a:hover,
  .woocommerce #respond input#submit.alt:hover, .woocommerce a.button.alt:hover, .woocommerce button.button.alt:hover, .woocommerce input.button.alt:hover, .woocommerce #respond input#submit:hover, .woocommerce button.button:hover, .woocommerce input.button:hover,.thunk-slide .owl-nav button.owl-prev:hover, .thunk-slide .owl-nav button.owl-next:hover, .open-shop-slide-post .owl-nav button.owl-prev:hover, .open-shop-slide-post .owl-nav button.owl-next:hover,.thunk-list-grid-switcher a.selected, .thunk-list-grid-switcher a:hover,.woocommerce .woocommerce-error .button:hover, .woocommerce .woocommerce-info .button:hover, .woocommerce .woocommerce-message .button:hover,#searchform [type='submit']:hover,article.thunk-post-article .thunk-readmore.button:hover,.open-shop-load-more button:hover,.woocommerce nav.woocommerce-pagination ul li a:focus, .woocommerce nav.woocommerce-pagination ul li a:hover, .woocommerce nav.woocommerce-pagination ul li span.current{background-color:{$open_shop_theme_clr};} 
  .thunk-product-hover .th-button.add_to_cart_button, .woocommerce ul.products .thunk-product-hover .add_to_cart_button, .woocommerce .thunk-product-hover a.th-butto, .woocommerce ul.products li.product .product_type_variable, .woocommerce ul.products li.product a.button.product_type_grouped,.open-cart p.buttons a:hover,.thunk-slide .owl-nav button.owl-prev:hover, .thunk-slide .owl-nav button.owl-next:hover, .open-shop-slide-post .owl-nav button.owl-prev:hover, .open-shop-slide-post .owl-nav button.owl-next:hover,body .woocommerce-tabs .tabs li a::before,.thunk-list-grid-switcher a.selected, .thunk-list-grid-switcher a:hover,.woocommerce .woocommerce-error .button, .woocommerce .woocommerce-info .button, .woocommerce .woocommerce-message .button,#searchform [type='submit']:hover,article.thunk-post-article .thunk-readmore.button,.woocommerce .thunk-product-hover a.th-button,.open-shop-load-more button,.woocommerce a.button.product_type_simple{border-color:{$open_shop_theme_clr}} .loader {
    border-right: 4px solid {$open_shop_theme_clr};
    border-bottom: 4px solid {$open_shop_theme_clr};
    border-left: 4px solid {$open_shop_theme_clr};}";

    //ribbon  
 $shopys_custom_style.=".openshop-site section.thunk-ribbon-section .content-wrap:before {
    content:'';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background:{$open_shop_theme_clr};}";

return $shopys_custom_style;
}

function shopys_customizer_script_registers(){
wp_enqueue_script( 'shopys_custom_customizer_script', get_theme_file_uri() . '/customizer/js/customizer.js', array("jquery"), '', true  ); 
}
add_action('customize_controls_enqueue_scripts', 'shopys_customizer_script_registers',100 );


// customizer style
function shopys_store_style(){ ?>
<style>
.customize-control-radio-image .ui-state-active img {
    border-color: #00b6ff!important;
    -webkit-box-shadow: 0 0 1px #3ec8ff!important;
    box-shadow: 0 0 5px #3ec8fe!important;
}
</style>
<?php }
add_action('customize_controls_print_styles','shopys_store_style',100 );

/**********************/
// Premium Product Grid
/**********************/
if ( class_exists( 'WooCommerce' ) ) {
    require_once get_stylesheet_directory() . '/template-parts/product-grid.php';
    require_once get_stylesheet_directory() . '/template-parts/marvo-product-grid.php';
    require_once get_stylesheet_directory() . '/template-parts/product-by-category.php';
    require_once get_stylesheet_directory() . '/template-parts/cart-summary.php';
    require_once get_stylesheet_directory() . '/inc/cart-invoice.php';
}

// Telegram Login — defines bot constants needed by AI Chatbot
require_once get_stylesheet_directory() . '/inc/telegram-login.php';

// AI Chatbot — always loads so the Settings page is always available
require_once get_stylesheet_directory() . '/inc/ai-chatbot.php';

// ── Fixed $2 shipping — no zone setup required ────────────────────────────────
add_filter( 'woocommerce_package_rates', 'shopys_force_flat_2_shipping', 99, 2 );
function shopys_force_flat_2_shipping( $rates, $package ) {
    $fixed_rate = new WC_Shipping_Rate(
        'shopys_flat_2',              // rate ID
        __( 'Shipping', 'shopys' ),   // label shown on cart/checkout
        2,                            // cost — always $2
        array(),                      // no taxes
        'shopys_flat'                 // method ID
    );
    return array( 'shopys_flat_2' => $fixed_rate );
}


function shopys_product_grid_assets() {
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style(
            'shopys-product-grid',
            get_stylesheet_directory_uri() . '/css/product-grid.css',
            array(),
            filemtime( get_stylesheet_directory() . '/css/product-grid.css' )
        );
        wp_enqueue_script(
            'shopys-product-grid-js',
            get_stylesheet_directory_uri() . '/js/product-grid.js',
            array(),
            filemtime( get_stylesheet_directory() . '/js/product-grid.js' ),
            true
        );
        wp_localize_script( 'shopys-product-grid-js', 'ppgParams', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ppg_ajax_nonce' ),
        ) );
        // Products-by-category CSS
        wp_enqueue_style(
            'shopys-product-by-category',
            get_stylesheet_directory_uri() . '/css/product-by-category.css',
            array( 'shopys-product-grid' ),
            filemtime( get_stylesheet_directory() . '/css/product-by-category.css' )
        );
        // Single product premium CSS
        if ( function_exists( 'is_product' ) && is_product() ) {
            wp_enqueue_style(
                'shopys-single-product',
                get_stylesheet_directory_uri() . '/css/single-product.css',
                array(),
                filemtime( get_stylesheet_directory() . '/css/single-product.css' )
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'shopys_product_grid_assets' );

/**********************/
// Force custom single product template
/**********************/
function shopys_force_single_product_template( $template ) {
    if ( function_exists( 'is_product' ) && is_product() ) {
        $custom = get_stylesheet_directory() . '/single-product.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'shopys_force_single_product_template', 99 );

/**********************/
// Advanced Product Search
/**********************/
require_once get_stylesheet_directory() . '/inc/advanced-search.php';

function shopys_advanced_search_assets() {
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style(
            'shopys-advanced-search',
            get_stylesheet_directory_uri() . '/css/advanced-search.css',
            array(),
            filemtime( get_stylesheet_directory() . '/css/advanced-search.css' )
        );
        wp_enqueue_script(
            'shopys-advanced-search-js',
            get_stylesheet_directory_uri() . '/js/advanced-search.js',
            array(),
            filemtime( get_stylesheet_directory() . '/js/advanced-search.js' ),
            true
        );
        wp_localize_script( 'shopys-advanced-search-js', 'shopys_search_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'shopys_advanced_search_assets' );

/**********************/
// Force custom search template for product search
/**********************/
function shopys_force_product_search_template( $template ) {
    if ( is_search() && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'product' ) {
        $custom = get_stylesheet_directory() . '/search.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'shopys_force_product_search_template', 999 );

/**********************/
// Force custom taxonomy template for WooCommerce product categories
/**********************/
function shopys_force_product_cat_template( $template ) {
    if ( is_tax( 'product_cat' ) ) {
        $custom = get_stylesheet_directory() . '/taxonomy-product_cat.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'shopys_force_product_cat_template', 999 );

/**********************/
// Force custom taxonomy template for WooCommerce product brands
/**********************/
function shopys_force_product_brand_template( $template ) {
    if ( is_tax( 'product_brand' ) ) {
        $custom = get_stylesheet_directory() . '/taxonomy-product_brand.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
add_filter( 'template_include', 'shopys_force_product_brand_template', 999 );

/**********************/
// Premium Announcement Banner (above search area)
/**********************/
function shopys_announcement_banner() {
    ?>
    <div class="shopys-announcement-bar">
        <div class="shopys-ann-inner">
            <span class="shopys-ann-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </span>
            <span class="shopys-ann-text">No Thai Products Here</span>
            <span class="shopys-ann-badge">Notice</span>
        </div>
    </div>
    <style>
    .shopys-announcement-bar {
        background: linear-gradient(135deg, #13e800 0%, #0fb500 100%);
        padding: 0;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(19,232,0,0.2);
    }
    .shopys-announcement-bar::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -5%;
        width: 140px;
        height: 140px;
        background: rgba(255,255,255,0.07);
        border-radius: 50%;
        pointer-events: none;
    }
    .shopys-ann-inner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 6px 16px;
        max-width: 1400px;
        margin: 0 auto;
    }
    .shopys-ann-icon {
        display: flex;
        align-items: center;
        color: #fff;
        opacity: 0.9;
        flex-shrink: 0;
    }
    .shopys-ann-text {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.3px;
    }
    .shopys-ann-badge {
        background: rgb(255,193,212);
        color: #d40040;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border: 1px solid rgb(255,173,198);
    }
    </style>
    <?php
}
add_action( 'open_shop_below_header', 'shopys_announcement_banner', 1 );

