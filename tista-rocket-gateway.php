<?php 
/**
* Plugin Name: Tista Rocket Gateway
* Plugin URI: 
* Description: Rocket gateway plugin for woocommerce
* Version: 4.2.1
* Author: TistaTeam
* Author URI: 
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Domain Path: /languages
* Text Domain: trg
*
* @package TistaTeam
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Set plugin version constant. */
define( 'TRG_VERSION', '1.2.1' );

/* Debug output control. */
define( 'TRG_DEBUG_OUTPUT', 0 );

/* Set constant path to the plugin directory. */
define( 'TRG_SLUG', basename( plugin_dir_path( __FILE__ ) ) );

/* Set constant path to the main file for activation call */
define( 'TRG_CORE_FILE', __FILE__ );

/* Set constant path to the plugin directory. */
define( 'TRG_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

/* Set the constant path to the plugin directory URI. */
define( 'TRG_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		// Makes sure the plugin functions are defined before trying to use them.
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}
	define( 'TRG_NETWORK_ACTIVATED', is_plugin_active_for_network( TRG_SLUG . '/tista-rocket-gateway.php' ) );
/* Tista_Rocket_Gateway Class */
	require_once TRG_PATH . 'inc/class-tista-rocket-gateway.php';
	
	
	if ( ! function_exists( 'tista_rocket_gateway' ) ) :
		/**
		 * The main function responsible for returning the one true
		 * Tista_Rocket_Gateway Instance to functions everywhere.
		 *
		 * Use this function like you would a global variable, except
		 * without needing to declare the global.
		 *
		 * Example: <?php $tista_rocket_gateway = tista_rocket_gateway(); ?>
		 *
		 * @since 1.0.0
		 * @return Tista_Rocket_Gateway The one true Tista_Rocket_Gateway Instance
		 */
		function tista_rocket_gateway() {
			return Tista_Rocket_Gateway::instance();
		}
	endif;

	/**
	 * Loads the main instance of Tista_Rocket_Gateway to prevent
	 * the need to use globals.
	 *
	 * This doesn't fire the activation hook correctly if done in 'after_setup_theme' hook.
	 *
	 * @since 1.0.0
	 * @return object Tista_Rocket_Gateway
	 */
	tista_rocket_gateway();