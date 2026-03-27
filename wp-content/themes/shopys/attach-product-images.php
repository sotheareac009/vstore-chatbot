<?php
/**
 * Attach product images — curl-to-local-file strategy (bypasses WP URL validator).
 * Run: wp eval-file wp-content/themes/shopys/attach-product-images.php
 *      --path=/Applications/MAMP/htdocs/custom-template --allow-root
 */

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Category → picsum base seed (each product in the category gets seed+index)
$category_seeds = [
    'Laptops'        => 10,
    'Desktops'       => 60,
    'Monitors'       => 110,
    'Keyboards'      => 150,
    'Mice'           => 200,
    'Headsets'       => 250,
    'Graphics Cards' => 300,
    'Processors'     => 350,
    'Memory'         => 400,
    'Storage'        => 450,
    'Networking'     => 500,
    'Webcams'        => 550,
    'Accessories'    => 600,
];

$upload_dir   = wp_upload_dir();
$tmp_dir      = $upload_dir['basedir'] . '/product-import-tmp';
if ( ! file_exists( $tmp_dir ) ) {
    wp_mkdir_p( $tmp_dir );
}

$cat_counters = [];

$products = wc_get_products( [ 'limit' => -1, 'status' => 'publish' ] );

if ( empty( $products ) ) {
    WP_CLI::error( 'No products found.' );
    return;
}

$done = $skipped = $failed = 0;

foreach ( $products as $product ) {

    $product_id = $product->get_id();

    if ( has_post_thumbnail( $product_id ) ) {
        WP_CLI::warning( "Already has image — skipping [{$product_id}]: " . $product->get_name() );
        $skipped++;
        continue;
    }

    // Resolve category
    $cat_name = 'Accessories';
    $cat_ids  = $product->get_category_ids();
    if ( ! empty( $cat_ids ) ) {
        $term = get_term( $cat_ids[0], 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) {
            $cat_name = $term->name;
        }
    }

    // Build picsum URL and download via curl (follows redirects, gets the real JPEG)
    $base  = $category_seeds[ $cat_name ] ?? 1;
    $idx   = $cat_counters[ $cat_name ] ?? 0;
    $seed  = $base + $idx;
    $cat_counters[ $cat_name ] = $idx + 1;

    $remote_url = "https://picsum.photos/seed/{$seed}/600/600";
    $tmp_file   = $tmp_dir . "/product-{$product_id}-{$seed}.jpg";

    // Download with curl
    $ch = curl_init( $remote_url );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 WordPress/Product-Import',
    ] );
    $image_data = curl_exec( $ch );
    $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );

    if ( ! $image_data || $http_code !== 200 ) {
        WP_CLI::warning( "curl failed [{$product_id}] HTTP {$http_code}: " . $product->get_name() );
        $failed++;
        continue;
    }

    file_put_contents( $tmp_file, $image_data );

    // Build file array for media_handle_sideload
    $file_array = [
        'name'     => sanitize_file_name( $product->get_name() ) . "-{$seed}.jpg",
        'tmp_name' => $tmp_file,
        'error'    => 0,
        'size'     => filesize( $tmp_file ),
    ];

    $attachment_id = media_handle_sideload( $file_array, $product_id, $product->get_name() );

    // media_handle_sideload moves the tmp file, but let's clean up if it remains
    if ( file_exists( $tmp_file ) ) {
        @unlink( $tmp_file );
    }

    if ( is_wp_error( $attachment_id ) ) {
        WP_CLI::warning( "Sideload failed [{$product_id}]: " . $attachment_id->get_error_message() );
        $failed++;
        continue;
    }

    set_post_thumbnail( $product_id, $attachment_id );
    WP_CLI::success( "[{$product_id}] {$product->get_name()} → attachment #{$attachment_id} (seed {$seed})" );
    $done++;

    usleep( 80000 ); // 80ms pause
}

// Clean up tmp dir
@rmdir( $tmp_dir );

WP_CLI::log( "\n✅ Done — Attached: {$done} | Skipped: {$skipped} | Failed: {$failed}" );
