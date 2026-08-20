<?php
/**
 * Lightweight WordPress/WooCommerce integration test for compatibility contracts.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Hamkharid\SmartCategoryComplement\Frontend\ProductRecommendations;
use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to a WordPress installation before running this test.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';

	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( FeaturesUtil::class ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "WooCommerce with FeaturesUtil support must be active before running this test.\n" );
		exit( 1 );
	}

	if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '8.2', '<' ) ) {
		throw new RuntimeException( 'The active WooCommerce version is below Hamkharid\'s supported minimum of 8.2.' );
	}

	$plugin_name  = plugin_basename( HAMKHARID_FILE );
	$features     = FeaturesUtil::get_compatible_features_for_plugin( $plugin_name );
	$compatible   = isset( $features['compatible'] ) && is_array( $features['compatible'] )
		? $features['compatible']
		: array();
	$incompatible = isset( $features['incompatible'] ) && is_array( $features['incompatible'] )
		? $features['incompatible']
		: array();

	if ( ! in_array( 'custom_order_tables', $compatible, true ) ) {
		throw new RuntimeException( 'Hamkharid did not declare HPOS compatibility.' );
	}

	if ( in_array( 'custom_order_tables', $incompatible, true ) ) {
		throw new RuntimeException( 'Hamkharid is simultaneously marked incompatible with HPOS.' );
	}

	$renderer = new ProductRecommendations(
		new RuleMatcher( new RuleRepository() ),
		new RecommendationEngine()
	);
	$renderer->register_hooks();

	if ( 18 !== has_action( 'woocommerce_after_single_product_summary', array( $renderer, 'render' ) ) ) {
		throw new RuntimeException( 'The classic single-product compatibility hook is not registered at priority 18.' );
	}

	if ( 5 !== has_action( 'woocommerce_after_main_content', array( $renderer, 'render' ) ) ) {
		throw new RuntimeException( 'The block/classic fallback compatibility hook is not registered at priority 5.' );
	}

	if ( 10 !== has_action( 'wp_enqueue_scripts', array( $renderer, 'enqueue_styles' ) ) ) {
		throw new RuntimeException( 'The frontend compatibility stylesheet hook is not registered at the default priority.' );
	}

	$stylesheet_path = HAMKHARID_PATH . 'assets/css/frontend.css';

	if ( ! is_readable( $stylesheet_path ) ) {
		throw new RuntimeException( 'The frontend stylesheet is missing.' );
	}

	// CLI test reads a local project asset directly.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$css = (string) file_get_contents( $stylesheet_path );

	foreach ( array( '.storefront', '.astra', '.elementor', '.woodmart' ) as $theme_selector ) {
		if ( false !== stripos( $css, $theme_selector ) ) {
			throw new RuntimeException(
				'The frontend stylesheet contains a theme/page-builder-specific selector: ' . $theme_selector
			);
		}
	}

	remove_action( 'wp_enqueue_scripts', array( $renderer, 'enqueue_styles' ) );
	remove_action( 'woocommerce_after_single_product_summary', array( $renderer, 'render' ), 18 );
	remove_action( 'woocommerce_after_main_content', array( $renderer, 'render' ), 5 );

	echo "Compatibility integration test passed.\n";
} )();
