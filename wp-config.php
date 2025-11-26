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
define( 'DB_NAME', 'wpdb' );

/** Database username */
define( 'DB_USER', 'wpuser' );

/** Database password */
define( 'DB_PASSWORD', 'WpUserPass2025!' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

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
$table_prefix = 'wp_';

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

define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', 'review-template.com');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
define('COOKIE_DOMAIN', '');
define('DISALLOW_FILE_EDIT', false);
define( 'OPENAI_API_KEY', '' );



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
define('AUTH_KEY',         'k ,Xvmk?wj5-Nf9?Q1#c<MWbYh+{4`Z(9BIJX#|+$]42nrS6!*uPn`bK0,~@-6{b');
define('SECURE_AUTH_KEY',  ')D(PUzRTk`O8]u%g{L3mj _6wV/,D/OXHKaTxlB>4WK%Z<RMw]a]hOLyUhEA|[&B');
define('LOGGED_IN_KEY',    'cbO~K`%_=[In&fG-aF2XL=oKMG(jB-JshJ)Pb->BlaM=h+wP!nAr=!&FZp9b_B)7');
define('NONCE_KEY',        '>$Wb!}}>K%U9h4*n:LX4:qk=#?ixF!!BeeL%0?|`xx>YG|CuZ<2Y&XoR/F{y]f^+');
define('AUTH_SALT',        'xFL2aEVKSXNt::q=_AerUpE/aLu--m|Lurz|{1C-U(p#?E3qE~5#m84NTd8GG{4l');
define('SECURE_AUTH_SALT', 'b?-mx}A?1QDJbee%Pndj$6RNgd`]p(U0j_>JcZ5+=v9[`~HHBAyaY3iYlKN$gTO8');
define('LOGGED_IN_SALT',   'A-*M7j~<J6O|Y]WuG3lkKk&YE:pJvP#j$*S/T- a5^-/vd&.7<LNb;m-+1?s&pfQ');
define('NONCE_SALT',       'JrE.?w+sIv7QTtZdB(%c.EX Jf39*S8%nhQsEtPr-y>n1O7V){bK%~f^=F7?~0>n');
