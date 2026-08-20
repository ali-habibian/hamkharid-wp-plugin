<?php
/**
 * Main plugin coordinator.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement;

use Hamkharid\SmartCategoryComplement\Admin\RuleAdmin;
use Hamkharid\SmartCategoryComplement\Frontend\ProductRecommendations;
use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the plugin lifecycle and integrations.
 */
final class Plugin {

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ), 20 );
	}

	/**
	 * Boots the plugin after all dependencies have had a chance to load.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->load_textdomain();

		if ( ! $this->is_woocommerce_available() ) {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_missing_notice' ) );

			return;
		}

		$rule_post_type  = new RulePostType();
		$rule_repository = new RuleRepository();
		$rule_post_type->register_hooks();

		if ( is_admin() ) {
			$rule_admin = new RuleAdmin( $rule_repository );
			$rule_admin->register_hooks();
		} else {
			$product_recommendations = new ProductRecommendations(
				new RuleMatcher( $rule_repository ),
				new RecommendationEngine()
			);
			$product_recommendations->register_hooks();
		}

		/**
		 * Fires after Hamkharid has verified its runtime dependencies.
		 *
		 * @param Plugin $plugin Main plugin coordinator.
		 */
		do_action( 'hamkharid_loaded', $this );
	}

	/**
	 * Reports whether WooCommerce is available at runtime.
	 *
	 * @return bool
	 */
	public function is_woocommerce_available(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Renders an administrator-facing dependency notice.
	 *
	 * @return void
	 */
	public function render_woocommerce_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Smart Category Complement – Hamkharid requires WooCommerce to be installed and active.', 'hamkharid' )
		);
	}

	/**
	 * Loads bundled translations for non-WordPress.org distributions.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'hamkharid',
			false,
			dirname( plugin_basename( HAMKHARID_FILE ) ) . '/languages'
		);
	}
}
