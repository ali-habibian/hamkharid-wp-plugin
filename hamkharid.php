<?php
/**
 * Plugin Name:       Smart Category Complement – Hamkharid
 * Description:       Display WooCommerce product complements based on category rules.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Ali Habibian
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hamkharid
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.2
 * WC tested up to:   11.0
 *
 * @package Hamkharid\SmartCategoryComplement
 */

defined( 'ABSPATH' ) || exit;

define( 'HAMKHARID_VERSION', '0.1.0' );
define( 'HAMKHARID_FILE', __FILE__ );
define( 'HAMKHARID_PATH', plugin_dir_path( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

$hamkharid_autoloader = HAMKHARID_PATH . 'vendor/autoload.php';

if ( ! is_readable( $hamkharid_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Smart Category Complement – Hamkharid could not load because its autoloader is missing. Please reinstall the plugin.', 'hamkharid' )
			);
		}
	);

	return;
}

require_once $hamkharid_autoloader;

register_activation_hook(
	HAMKHARID_FILE,
	array( Hamkharid\SmartCategoryComplement\Admin\DisplaySettings::class, 'initialize_on_activation' )
);

$hamkharid_plugin = new Hamkharid\SmartCategoryComplement\Plugin();
$hamkharid_plugin->register();
