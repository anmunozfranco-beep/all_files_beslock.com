<?php

//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'yksG0732Rx8zCO076WHcY8tpKnmVAYffBR4f6pIbQzK8MCNXuJ7rhDmbyKWbcr6W');
//END Really Simple Security key
define('WP_CACHE', true); // Added by SpeedyCache

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
define( 'DB_NAME', 'andres38_wp718' );

/** Database username */
define( 'DB_USER', 'andres38_wp718' );

/** Database password */
define( 'DB_PASSWORD', '[B5V5c1(Sp' );

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
define( 'AUTH_KEY',         'npqwytn2ygha68t22b7i1jzpfia7ujw4tbgiwkurkqpqr0renf2b6pazcbu2hmy9' );
define( 'SECURE_AUTH_KEY',  'td0c4ml1hyng3bsovgqzzw89luowt5nokafhe9ycmg64m6d6ueicfu63yaaypz7b' );
define( 'LOGGED_IN_KEY',    'n0e1rgblwr15xuj5iu5grjvgvrc8goj7lchokgfx0qbd9ptiibksdbtf7pk7lwcf' );
define( 'NONCE_KEY',        'kk5vfaavnmky4exoirnwtueqg5ubedi6nubwgk7jehtygse3ls04qoahksh5coe9' );
define( 'AUTH_SALT',        'gsflmahfsgelxjisoypg3wbqeiu2h3cees3chnldcshgsiiccabkvah3ygntxoqd' );
define( 'SECURE_AUTH_SALT', 'xnvgtvm8httneyhbckn6pltldewao1fkg7yy2d7odw19vsprtcxrss6fhzzrcuix' );
define( 'LOGGED_IN_SALT',   'trjzbhkscis705fe6s5ecl3bxolepcrdzqi79ua3qm5ubi5wsypt3d34pqax7rcg' );
define( 'NONCE_SALT',       'vmpkftv7x9qziaszfojgq47z6fa3johpxztyirfcn8pujhhgn2kxv2jhhmhdgnor' );

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
$table_prefix = 'wptq_';

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
