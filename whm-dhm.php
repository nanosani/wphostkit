<?php
/**
 * Plugin Name:     WHM Domain and Hosting Manager
 * Plugin URI:      https://www.itechtics.com
 * Description:     Manage WHM servers, hosting accounts, products, orders, and subscriptions from within WordPress.
 * Version:         1.0.0
 * Author:          Itechtics
 * Author URI:      https://www.itechtics.com
 * Text Domain:     whm-dhm
 * Domain Path:     /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/* ------------------------------------------------------------------------- *
 *  Constants
 * ------------------------------------------------------------------------- */
define( 'WHM_DHM_VERSION',      '1.0.0' );
define( 'WHM_DHM_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'WHM_DHM_PLUGIN_URL',   plugin_dir_url(  __FILE__ ) );
define( 'WHM_DHM_PLUGIN_FILE',  __FILE__ );
define( 'WHM_DHM_VENDOR_DIR',   WHM_DHM_PLUGIN_DIR . 'vendor/' );

/* ------------------------------------------------------------------------- *
 *  Activation / De-activation hooks
 * ------------------------------------------------------------------------- */
require_once WHM_DHM_PLUGIN_DIR . 'includes/class-activation.php';
require_once WHM_DHM_PLUGIN_DIR . 'includes/class-deactivation.php';

register_activation_hook(   WHM_DHM_PLUGIN_FILE, [ 'WHM_DHM_Activation',   'activate'   ] );
register_deactivation_hook( WHM_DHM_PLUGIN_FILE, [ 'WHM_DHM_Deactivation', 'deactivate' ] );

/* ------------------------------------------------------------------------- *
 *  Autoloader (includes/ -> class-*.php)
 * ------------------------------------------------------------------------- */
spl_autoload_register( function ( $class ) {
    $prefix = 'WHM_DHM_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return; // Not one of ours.
    }
    $relative = substr( $class, strlen( $prefix ) );       // Strip prefix
    $slug     = str_replace( '_', '-', strtolower( $relative ) );
    $path     = WHM_DHM_PLUGIN_DIR . "includes/class-{$slug}.php";
    if ( file_exists( $path ) ) {
        require $path;
    }
} );

/* ------------------------------------------------------------------------- *
 *  ALWAYS load the low-level WHM API class – it is required
 *  in admin-ajax.php requests before the Core boots.
 * ------------------------------------------------------------------------- */
require_once WHM_DHM_PLUGIN_DIR . 'includes/class-whm-api.php';
require_once WHM_DHM_PLUGIN_DIR . 'includes/class-ajax-handlers.php'; // defines WHM_DHM_Ajax

/* ------------------------------------------------------------------------- *
 *  Bootstrap the plugin once WordPress is fully loaded.
 * ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', function () {
    // Core (assets, templates, cron, etc.)
    ( new WHM_DHM_Core() )->run();

    // AJAX endpoints
    WHM_DHM_Ajax::init();
} );
