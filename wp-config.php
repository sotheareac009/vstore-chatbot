<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'custom_template' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'vA)Z8hp.%U>#OXkr.tDAi<26O]`-<U?S9V}=-9CUugW-@OV2<r=D (_;xLT$>i`{' );
define( 'SECURE_AUTH_KEY',  '|~O|]C5p^R<L)zw^>HIFgQN,l0une5f%Qg<J#@NJ&uS(WxOqWgj5$X?:>K<}ml(Y' );
define( 'LOGGED_IN_KEY',    'H+V0/zl!}2:z;*H|ba=aVy&2W~ ,D6C.%Z=*m{6d,o[mNzIb|N<M;@6PGtH,gsaZ' );
define( 'NONCE_KEY',        'G8A2J$heaMlN0!?NALm.^iS6d6M3ifeU*/LsJ/=)7iQN4/$Pj?}Epsj*c*X^EGA=' );
define( 'AUTH_SALT',        '{$})xRW&;/GNS5,2yD4X$T Z;rrM3.S-z-j1T:>*fg6j5qx1<|jk.fUxloQeEN/Q' );
define( 'SECURE_AUTH_SALT', '$ SmD3k*fw-qbQzE*ufjnZ%8t4kM[Dsl31~NFUH9WGY{dk]JN{_EduDjv|WjzP4)' );
define( 'LOGGED_IN_SALT',   'WqC0qwczI[0z;w@7DwQ[<iY)ERv=O_p/+PP_?U~7QgBRMeK@~R38v;r(hD/^/TGd' );
define( 'NONCE_SALT',       'hdqHkvB t)8M=g3fSfu1souA_VEJP[=xzB#hjd-EMO}S+ZMeQB}yql69=Gln?n(`' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'ct_wp';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
