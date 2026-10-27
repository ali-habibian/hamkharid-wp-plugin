<?php
/**
 * Lightweight integration test for responsive and accessible frontend presentation.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Frontend\ProductRecommendations;
use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Integration test intentionally emulates WordPress/WooCommerce global request context.
( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to a WordPress installation before running this test.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';

	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "WooCommerce must be active before running this test.\n" );
		exit( 1 );
	}

	$created_product_id = 0;
	$previous_post      = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$previous_product   = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
	$previous_query     = isset( $GLOBALS['wp_query'] ) ? clone $GLOBALS['wp_query'] : null;

	try {
		$renderer = new ProductRecommendations(
			new RuleMatcher( new RuleRepository() ),
			new RecommendationEngine()
		);
		$renderer->register_hooks();

		if ( 10 !== has_action( 'wp_enqueue_scripts', array( $renderer, 'enqueue_styles' ) ) ) {
			throw new RuntimeException( 'The frontend stylesheet hook was not registered at the default priority.' );
		}

		wp_dequeue_style( 'hamkharid-complements' );
		wp_deregister_style( 'hamkharid-complements' );

		$GLOBALS['wp_query']->is_singular       = false;
		$GLOBALS['wp_query']->is_single         = false;
		$GLOBALS['wp_query']->is_page           = false;
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;

		$renderer->enqueue_styles();

		if ( wp_style_is( 'hamkharid-complements', 'enqueued' ) ) {
			throw new RuntimeException( 'The complement stylesheet was enqueued outside a product page.' );
		}

		$product = new \WC_Product_Simple();
		$product->set_name( 'Presentation Product ' . wp_generate_uuid4() );
		$product->set_status( 'publish' );
		$product->set_regular_price( '20' );
		$product->set_stock_status( 'instock' );
		$product->set_catalog_visibility( 'visible' );
		$created_product_id = $product->save();

		$current_post = get_post( $created_product_id );

		if ( ! $current_post instanceof \WP_Post ) {
			throw new RuntimeException( 'The presentation test product could not be loaded.' );
		}

		$GLOBALS['post']    = $current_post;
		$GLOBALS['product'] = wc_get_product( $created_product_id );
		setup_postdata( $current_post );

		$GLOBALS['wp_query']->is_singular       = true;
		$GLOBALS['wp_query']->is_single         = true;
		$GLOBALS['wp_query']->is_page           = false;
		$GLOBALS['wp_query']->queried_object    = $current_post;
		$GLOBALS['wp_query']->queried_object_id = $created_product_id;

		$renderer->enqueue_styles();

		if ( ! wp_style_is( 'hamkharid-complements', 'enqueued' ) ) {
			throw new RuntimeException( 'The complement stylesheet was not enqueued on a product page.' );
		}

		$wp_styles = wp_styles();
		$style     = isset( $wp_styles->registered['hamkharid-complements'] )
			? $wp_styles->registered['hamkharid-complements']
			: null;

		if ( null === $style || false === strpos( (string) $style->src, 'assets/css/frontend.css' ) ) {
			throw new RuntimeException( 'The complement stylesheet points to an unexpected asset.' );
		}

		if ( HAMKHARID_VERSION !== (string) $style->ver ) {
			throw new RuntimeException( 'The complement stylesheet is not versioned with the plugin version.' );
		}

		$stylesheet_path = HAMKHARID_PATH . 'assets/css/frontend.css';

		if ( ! is_readable( $stylesheet_path ) ) {
			throw new RuntimeException( 'The complement stylesheet is missing.' );
		}

		// CLI test reads a local project asset directly.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$css = (string) file_get_contents( $stylesheet_path );

		foreach (
			array(
				'grid-template-columns: repeat(auto-fit, minmax(min(100%, 13.75rem), 1fr))',
				':focus-visible',
				'overflow-wrap: anywhere',
				'inline-size',
				'block-size',
				'min-inline-size: 0',
				'max-inline-size: 100%',
				'color: #5a430d',
				'background-color: #5a430d !important',
				'color: #fff !important',
			) as $required_css
		) {
			if ( false === strpos( $css, $required_css ) ) {
				throw new RuntimeException( 'A required responsive/accessibility CSS rule is missing: ' . $required_css );
			}
		}

		$important_matches = array();
		preg_match_all( '/([^{}]+)\{([^{}]*!important[^{}]*)\}/', $css, $important_matches, PREG_SET_ORDER );

		$allowed_important = array(
			array(
				'selector'    => '.hamkharid-complements.related.products ul.products li.product .woocommerce-loop-product__title',
				'declaration' => 'color: #5a430d !important',
			),
			array(
				'selector'    => '.hamkharid-complements.related.products ul.products li.product',
				'declaration' => 'margin: 0 !important',
			),
			array(
				'selector'    => '.hamkharid-complements.related.products ul.products li.product .button',
				'declaration' => 'background-color: #5a430d !important',
			),
		);

		if ( count( $allowed_important ) !== count( $important_matches ) ) {
			throw new RuntimeException( 'The component stylesheet contains an unexpected important declaration.' );
		}

		foreach ( $important_matches as $index => $important_match ) {
			if ( false === strpos( $important_match[1], $allowed_important[ $index ]['selector'] ) || false === strpos( $important_match[2], $allowed_important[ $index ]['declaration'] ) ) {
				throw new RuntimeException( 'An important declaration is outside its scoped theme compatibility or contrast override.' );
			}
		}

		if ( preg_match( '/(?:^|[;{]\s*)(?:left|right)\s*:|\b(?:margin|padding|border)-(?:left|right)\b/m', $css ) ) {
			throw new RuntimeException( 'The component stylesheet contains direction-specific left/right positioning.' );
		}

		remove_action( 'wp_enqueue_scripts', array( $renderer, 'enqueue_styles' ) );
		remove_action( 'woocommerce_after_single_product_summary', array( $renderer, 'render' ), 18 );
		remove_action( 'woocommerce_after_main_content', array( $renderer, 'render' ), 5 );

		echo "Frontend presentation integration test passed.\n";
	} finally {
		if ( $created_product_id > 0 ) {
			wp_delete_post( $created_product_id, true );
		}

		wp_dequeue_style( 'hamkharid-complements' );
		wp_deregister_style( 'hamkharid-complements' );
		wp_reset_postdata();

		if ( null !== $previous_post ) {
			$GLOBALS['post'] = $previous_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		if ( null !== $previous_product ) {
			$GLOBALS['product'] = $previous_product;
		} else {
			unset( $GLOBALS['product'] );
		}

		if ( null !== $previous_query ) {
			$GLOBALS['wp_query'] = $previous_query;
		}
	}
} )();
// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
