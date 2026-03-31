<?php
/**
 * Shortcode Guide — WP Admin sidebar page
 *
 * Adds a "Shortcode Guide" menu item under the Shopys top-level menu
 * so site admins can quickly reference every shortcode the theme provides.
 *
 * @package Shopys
 */

// ── Register top-level menu + sub-page ───────────────────────────────────────
add_action( 'admin_menu', 'shopys_shortcode_guide_menu' );
function shopys_shortcode_guide_menu() {
    // Top-level "Shopys" menu (icon: dashicons-store)
    add_menu_page(
        __( 'Shopys', 'shopys' ),
        __( 'Shopys', 'shopys' ),
        'edit_posts',
        'shopys-dashboard',
        'shopys_shortcode_guide_page',
        'dashicons-store',
        59
    );

    // Sub-page: Shortcode Guide
    add_submenu_page(
        'shopys-dashboard',
        __( 'Shortcode Guide', 'shopys' ),
        __( 'Shortcode Guide', 'shopys' ),
        'edit_posts',
        'shopys-dashboard',
        'shopys_shortcode_guide_page'
    );
}

// ── Render the guide page ────────────────────────────────────────────────────
function shopys_shortcode_guide_page() {
    ?>
    <div class="wrap shopys-sg-wrap">
        <h1><?php esc_html_e( 'Shopys — Shortcode Guide', 'shopys' ); ?></h1>
        <p class="shopys-sg-intro">
            <?php esc_html_e( 'Copy any shortcode below and paste it into a page, post, or widget. Click a shortcode card to expand its full attribute reference.', 'shopys' ); ?>
        </p>

        <!-- ================================================================
             1. premium_products
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-premium-products">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[premium_products]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'Display all products with grid/table view, filtering & pagination.', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[premium_products]</pre>
                <pre class="shopys-sg-code">[premium_products limit="8" columns="3" filter="true" category="electronics,phones"]</pre>
                <pre class="shopys-sg-code">[premium_products listing_type="table" cart="true" pagination_type="infinite"]</pre>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>limit</code></td><td>12</td><td><?php esc_html_e( 'Any number', 'shopys' ); ?></td><td><?php esc_html_e( 'Products per page', 'shopys' ); ?></td></tr>
                        <tr><td><code>columns</code></td><td>4</td><td>2 – 5</td><td><?php esc_html_e( 'Grid columns', 'shopys' ); ?></td></tr>
                        <tr><td><code>category</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Filter by category', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter_tabs</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Category tabs shown in the filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>orderby</code></td><td>date</td><td>date, title, price, rand, menu_order</td><td><?php esc_html_e( 'Sort field', 'shopys' ); ?></td></tr>
                        <tr><td><code>order</code></td><td>DESC</td><td>ASC, DESC</td><td><?php esc_html_e( 'Sort direction', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter</code></td><td>false</td><td>true, false</td><td><?php esc_html_e( 'Show category filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>cart</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Add-to-Cart button', 'shopys' ); ?></td></tr>
                        <tr><td><code>pagination_type</code></td><td>normal</td><td>normal, infinite</td><td><?php esc_html_e( 'Pagination style', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_description</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show product specs/bullets', 'shopys' ); ?></td></tr>
                        <tr><td><code>listing_type</code></td><td>grid</td><td>grid, table</td><td><?php esc_html_e( 'Layout mode', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_product_listing_header</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show table header row (table mode)', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================
             2. featured_products
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-featured-products">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[featured_products]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'Display only WooCommerce "Featured" products.', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[featured_products]</pre>
                <pre class="shopys-sg-code">[featured_products limit="6" columns="3" filter="true"]</pre>
                <pre class="shopys-sg-code">[featured_products listing_type="table" pagination_type="infinite"]</pre>

                <p class="shopys-sg-tip">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e( 'To mark a product as "Featured", click the star icon on the Products list page or enable it in the product editor under Product data > Advanced.', 'shopys' ); ?>
                </p>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>limit</code></td><td>12</td><td><?php esc_html_e( 'Any number', 'shopys' ); ?></td><td><?php esc_html_e( 'Products per page', 'shopys' ); ?></td></tr>
                        <tr><td><code>columns</code></td><td>4</td><td>2 – 5</td><td><?php esc_html_e( 'Grid columns', 'shopys' ); ?></td></tr>
                        <tr><td><code>category</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Filter by category', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter_tabs</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Category tabs shown in the filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>orderby</code></td><td>date</td><td>date, title, price, rand, menu_order</td><td><?php esc_html_e( 'Sort field', 'shopys' ); ?></td></tr>
                        <tr><td><code>order</code></td><td>DESC</td><td>ASC, DESC</td><td><?php esc_html_e( 'Sort direction', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter</code></td><td>false</td><td>true, false</td><td><?php esc_html_e( 'Show category filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>cart</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Add-to-Cart button', 'shopys' ); ?></td></tr>
                        <tr><td><code>pagination_type</code></td><td>normal</td><td>normal, infinite</td><td><?php esc_html_e( 'Pagination style', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_description</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show product specs/bullets', 'shopys' ); ?></td></tr>
                        <tr><td><code>listing_type</code></td><td>grid</td><td>grid, table</td><td><?php esc_html_e( 'Layout mode', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_product_listing_header</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show table header row (table mode)', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================
             3. latest_products
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-latest-products">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[latest_products]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'Display the most recently added products (newest first).', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[latest_products]</pre>
                <pre class="shopys-sg-code">[latest_products limit="8" columns="3" filter="true"]</pre>
                <pre class="shopys-sg-code">[latest_products listing_type="table" category="electronics" pagination_type="infinite"]</pre>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>limit</code></td><td>12</td><td><?php esc_html_e( 'Any number', 'shopys' ); ?></td><td><?php esc_html_e( 'Products per page', 'shopys' ); ?></td></tr>
                        <tr><td><code>columns</code></td><td>4</td><td>2 – 5</td><td><?php esc_html_e( 'Grid columns', 'shopys' ); ?></td></tr>
                        <tr><td><code>category</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Filter by category', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter_tabs</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Category tabs shown in the filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>orderby</code></td><td>date</td><td>date, title, price, rand, menu_order</td><td><?php esc_html_e( 'Sort field', 'shopys' ); ?></td></tr>
                        <tr><td><code>order</code></td><td>DESC</td><td>ASC, DESC</td><td><?php esc_html_e( 'Sort direction', 'shopys' ); ?></td></tr>
                        <tr><td><code>filter</code></td><td>false</td><td>true, false</td><td><?php esc_html_e( 'Show category filter bar', 'shopys' ); ?></td></tr>
                        <tr><td><code>cart</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Add-to-Cart button', 'shopys' ); ?></td></tr>
                        <tr><td><code>pagination_type</code></td><td>normal</td><td>normal, infinite</td><td><?php esc_html_e( 'Pagination style', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_description</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show product specs/bullets', 'shopys' ); ?></td></tr>
                        <tr><td><code>listing_type</code></td><td>grid</td><td>grid, table</td><td><?php esc_html_e( 'Layout mode', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_product_listing_header</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show table header row (table mode)', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================
             4. products_by_category (was 3)
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-products-by-category">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[products_by_category]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'List all products grouped by their category with section headers.', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[products_by_category]</pre>
                <pre class="shopys-sg-code">[products_by_category columns="3" cart="false" exclude_cat="uncategorized,misc"]</pre>
                <pre class="shopys-sg-code">[products_by_category orderby="title" order="ASC"]</pre>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>columns</code></td><td>4</td><td>2 – 5</td><td><?php esc_html_e( 'Grid columns', 'shopys' ); ?></td></tr>
                        <tr><td><code>orderby</code></td><td>date</td><td>date, title, price, rand, menu_order</td><td><?php esc_html_e( 'Sort field', 'shopys' ); ?></td></tr>
                        <tr><td><code>order</code></td><td>DESC</td><td>ASC, DESC</td><td><?php esc_html_e( 'Sort direction', 'shopys' ); ?></td></tr>
                        <tr><td><code>cart</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Add-to-Cart button', 'shopys' ); ?></td></tr>
                        <tr><td><code>exclude_cat</code></td><td>uncategorized</td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Categories to exclude', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================
             5. marvo_premium_products
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-marvo-premium-products">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[marvo_premium_products]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'Simple single-category product grid with specs and SKU.', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[marvo_premium_products category="electronics"]</pre>
                <pre class="shopys-sg-code">[marvo_premium_products category="electronics,phones"]</pre>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>category</code></td><td><em><?php esc_html_e( 'empty (required)', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Slug(s), comma-separated', 'shopys' ); ?></td><td><?php esc_html_e( 'Category to display products from', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================
             6. cart_summary
             ================================================================ -->
        <div class="shopys-sg-card" id="sg-cart-summary">
            <div class="shopys-sg-header" onclick="this.parentElement.classList.toggle('open')">
                <span class="shopys-sg-tag">[cart_summary]</span>
                <span class="shopys-sg-desc"><?php esc_html_e( 'Shopping cart summary with items table, totals & checkout button.', 'shopys' ); ?></span>
                <span class="shopys-sg-toggle dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="shopys-sg-body">
                <h4><?php esc_html_e( 'Quick Examples', 'shopys' ); ?></h4>
                <pre class="shopys-sg-code">[cart_summary]</pre>
                <pre class="shopys-sg-code">[cart_summary title="Your Cart" show_checkout="true" show_print="true"]</pre>
                <pre class="shopys-sg-code">[cart_summary show_print="false"]</pre>

                <h4><?php esc_html_e( 'Attributes', 'shopys' ); ?></h4>
                <table class="shopys-sg-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Attribute', 'shopys' ); ?></th><th><?php esc_html_e( 'Default', 'shopys' ); ?></th><th><?php esc_html_e( 'Options', 'shopys' ); ?></th><th><?php esc_html_e( 'Description', 'shopys' ); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>title</code></td><td><em><?php esc_html_e( 'empty', 'shopys' ); ?></em></td><td><?php esc_html_e( 'Any text', 'shopys' ); ?></td><td><?php esc_html_e( 'Heading above the cart table', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_checkout</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Checkout button', 'shopys' ); ?></td></tr>
                        <tr><td><code>show_print</code></td><td>true</td><td>true, false</td><td><?php esc_html_e( 'Show Print Invoice button', 'shopys' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- .shopys-sg-wrap -->

    <style>
        .shopys-sg-wrap { max-width: 960px; }
        .shopys-sg-intro { font-size: 14px; color: #555; margin-bottom: 24px; }

        .shopys-sg-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .shopys-sg-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

        .shopys-sg-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            cursor: pointer;
            user-select: none;
        }
        .shopys-sg-header:hover { background: #f9f9f9; }

        .shopys-sg-tag {
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
            background: #f0f0f1;
            padding: 4px 10px;
            border-radius: 4px;
            white-space: nowrap;
        }
        .shopys-sg-desc {
            flex: 1;
            font-size: 13px;
            color: #666;
        }
        .shopys-sg-toggle {
            transition: transform 0.25s;
            color: #999;
        }
        .shopys-sg-card.open .shopys-sg-toggle {
            transform: rotate(180deg);
        }

        .shopys-sg-body {
            display: none;
            padding: 0 20px 20px;
            border-top: 1px solid #eee;
        }
        .shopys-sg-card.open .shopys-sg-body { display: block; }

        .shopys-sg-body h4 {
            margin: 16px 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
        }

        .shopys-sg-code {
            background: #f6f7f7;
            border: 1px solid #e2e4e7;
            border-radius: 4px;
            padding: 10px 14px;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
            margin: 6px 0;
            cursor: pointer;
            position: relative;
            transition: background 0.15s;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .shopys-sg-code:hover { background: #eef; }
        .shopys-sg-code.copied::after {
            content: "Copied!";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #2271b1;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
        }

        .shopys-sg-table { margin-top: 4px; }
        .shopys-sg-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .shopys-sg-table td { font-size: 13px; vertical-align: top; }
        .shopys-sg-table code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .shopys-sg-tip {
            background: #fef8ee;
            border-left: 4px solid #dba617;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 13px;
            border-radius: 0 4px 4px 0;
        }
        .shopys-sg-tip .dashicons { color: #dba617; margin-right: 4px; vertical-align: middle; font-size: 16px; }
    </style>

    <script>
    (function(){
        // Click-to-copy on code blocks
        document.querySelectorAll('.shopys-sg-code').forEach(function(el){
            el.title = 'Click to copy';
            el.addEventListener('click', function(){
                var text = this.textContent.trim();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                this.classList.add('copied');
                var self = this;
                setTimeout(function(){ self.classList.remove('copied'); }, 1200);
            });
        });
    })();
    </script>
    <?php
}
