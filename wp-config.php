<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'packsouq_live' );

/** MySQL database username */
define( 'DB_USER', 'packsouq_live' );

/** MySQL database password */
define( 'DB_PASSWORD', 'z&fo!Psg{7$g' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'yi5sIyFoA5J*1>KNpneWIAYMsERcE-ZN{aA&.f/0E@*kc]^eTOGjHQu$4|&&4@J&' );
define( 'SECURE_AUTH_KEY',  'XF7}=Xm$b2AOA?y@7?wP/zG{3A03X5Vz.@h&PFn0*RFcj5d_e#&QD:NxMNW&T;u/' );
define( 'LOGGED_IN_KEY',    '6zuLmkoYJ6t7tRf g@sY6_ix.y%3ID#*r{<Qxz=sGpSU^E(n^p0zp-`6MgG.S2-+' );
define( 'NONCE_KEY',        'y|+W?t1T_oBfy{&k@*QQET:%Pb=U^@@[c(>7//(:;~@Bk()iBITADQszFbX;Vgu0' );
define( 'AUTH_SALT',        ':<9w#h!>h?;:~61b^_fNKE4eixosZYR/>]`XX/g3ZfA2Fd47?+Wg:^dbrB(@raq/' );
define( 'SECURE_AUTH_SALT', 'B<#GQV6**Tqqa1-:IYxuAdEMZNZE.xQyP,5cm3779yPw`WH.WhiJ4]Q2Q{U_<}Vs' );
define( 'LOGGED_IN_SALT',   'O{Fj9Fh^?PIt}&ZOqYXka0#.*J6c,y[+=S5[@D>/*k?:Q3/UF>7 4bTO~u!)9Our' );
define( 'NONCE_SALT',       'oSa=>R.n^|QmOQJa5#Zy_Oe=hLbb%tf@t9]S>qavlA~sU^:a|k$3Jh}<G54]2k)O' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
  

/* That's all, stop editing! Happy publishing. */


define('WP_MEMORY_LIMIT', '1024M');
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
define('CONCATENATE_SCRIPTS', false); 
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
