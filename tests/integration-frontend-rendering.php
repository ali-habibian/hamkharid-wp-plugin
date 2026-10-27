<?php
/**
 * Lightweight WordPress/WooCommerce integration test for frontend rendering.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Frontend\ProductRecommendations;
use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;
use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\Rule;
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

	$created_terms     = array();
	$created_products  = array();
	$created_rule_id   = 0;
	$previous_post     = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$previous_product  = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
	$previous_query    = isset( $GLOBALS['wp_query'] ) ? clone $GLOBALS['wp_query'] : null;
	$previous_settings = get_option( DisplaySettings::OPTION_NAME, false );

	try {
		$source_result = wp_insert_term( 'Frontend Source ' . wp_generate_uuid4(), 'product_cat' );
		$target_result = wp_insert_term( 'Frontend Target ' . wp_generate_uuid4(), 'product_cat' );

		foreach ( array( $source_result, $target_result ) as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$source_id = (int) $source_result['term_id'];
		$target_id = (int) $target_result['term_id'];

		$create_product = static function ( string $name, int $category_id ) use ( &$created_products ): int {
			$product = new \WC_Product_Simple();
			$product->set_name( $name . ' ' . wp_generate_uuid4() );
			$product->set_status( 'publish' );
			$product->set_regular_price( '25' );
			$product->set_category_ids( array( $category_id ) );
			$product->set_stock_status( 'instock' );
			$product->set_catalog_visibility( 'visible' );
			$product_id = $product->save();

			if ( $product_id <= 0 ) {
				throw new RuntimeException( 'A frontend rendering test product could not be created.' );
			}

			$created_products[] = (int) $product_id;

			return (int) $product_id;
		};

		$current_product_id = $create_product( 'Frontend Current', $source_id );
		$recommended_one_id = $create_product( 'Frontend Complement One', $target_id );
		$recommended_two_id = $create_product( 'Frontend Complement Two', $target_id );
		$repository         = new RuleRepository();
		$created_rule_id    = $repository->create(
			'Frontend Rendering Rule',
			true,
			array( $source_id ),
			$target_id,
			2,
			Rule::SORT_NEWEST
		);

		if ( is_wp_error( $created_rule_id ) ) {
			throw new RuntimeException( $created_rule_id->get_error_message() );
		}

		$current_product = wc_get_product( $current_product_id );
		$current_post    = get_post( $current_product_id );

		if ( ! $current_product instanceof \WC_Product || ! $current_post instanceof \WP_Post ) {
			throw new RuntimeException( 'The current frontend test product could not be loaded.' );
		}

		$GLOBALS['post']    = $current_post;
		$GLOBALS['product'] = $current_product;
		setup_postdata( $current_post );

		$GLOBALS['wp_query']->is_singular       = true;
		$GLOBALS['wp_query']->is_single         = true;
		$GLOBALS['wp_query']->is_page           = false;
		$GLOBALS['wp_query']->queried_object    = $current_post;
		$GLOBALS['wp_query']->queried_object_id = $current_product_id;
		$automatic_settings                     = DisplaySettings::get();
		$automatic_settings['mode']             = 'automatic';
		update_option( DisplaySettings::OPTION_NAME, $automatic_settings, false );

		$renderer = new ProductRecommendations(
			new RuleMatcher( $repository ),
			new RecommendationEngine()
		);
		$renderer->register_hooks();

		if ( 18 !== has_action( 'woocommerce_after_single_product_summary', array( $renderer, 'render' ) ) ) {
			throw new RuntimeException( 'The classic single-product rendering hook was not registered at priority 18.' );
		}

		if ( 5 !== has_action( 'woocommerce_after_main_content', array( $renderer, 'render' ) ) ) {
			throw new RuntimeException( 'The block-compatible fallback hook was not registered at priority 5.' );
		}

		ob_start();
		$renderer->render();
		$renderer->render();
		$html = (string) ob_get_clean();

		if ( 1 !== substr_count( $html, '<section class="hamkharid-complements' ) ) {
			throw new RuntimeException( 'Frontend recommendations were missing or rendered more than once.' );
		}

		$expected_heading = esc_html__( 'Complete your purchase', 'hamkharid' );

		if ( false === strpos( $html, $expected_heading ) ) {
			throw new RuntimeException( 'The complement section heading was not rendered for the active WordPress locale.' );
		}

		$heading_id = 'hamkharid-complements-title-' . $current_product_id;

		if (
			false === strpos( $html, 'aria-labelledby="' . esc_attr( $heading_id ) . '"' )
			|| false === strpos( $html, 'id="' . esc_attr( $heading_id ) . '"' )
		) {
			throw new RuntimeException( 'The complement section is not labelled by its visible heading.' );
		}

		foreach ( array( $recommended_one_id, $recommended_two_id ) as $recommended_product_id ) {
			$recommended_product = wc_get_product( $recommended_product_id );

			if ( ! $recommended_product instanceof \WC_Product || false === strpos( $html, $recommended_product->get_name() ) ) {
				throw new RuntimeException( 'An expected complement product was not rendered.' );
			}
		}

		if ( false !== strpos( $html, $current_product->get_name() ) ) {
			throw new RuntimeException( 'The current product appeared in its own complement section.' );
		}

		if ( false === has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ) ) {
			throw new RuntimeException( 'The native WooCommerce Add-to-Cart loop callback is unavailable.' );
		}

		if ( ! shortcode_exists( 'hamkharid_complements' ) ) {
			throw new RuntimeException( 'The manual placement shortcode was not registered.' );
		}

		$manual_settings = array(
			'schema_version'          => DisplaySettings::SCHEMA_VERSION,
			'mode'                    => 'manual',
			'placement'               => 'after_summary',
			'title_fa'                => '',
			'title_en'                => '',
			'allow_backorders'        => false,
			'clear_data_on_uninstall' => false,
		);
		update_option( DisplaySettings::OPTION_NAME, $manual_settings, false );

		$manual_renderer = new ProductRecommendations(
			new RuleMatcher( $repository ),
			new RecommendationEngine()
		);
		$manual_renderer->register_hooks();

		if ( false !== has_action( 'woocommerce_after_single_product_summary', array( $manual_renderer, 'render' ) ) ) {
			throw new RuntimeException( 'Manual mode did not disable automatic placement.' );
		}

		$GLOBALS['product']                     = $current_product;
		$GLOBALS['wp_query']->is_single         = false;
		$GLOBALS['wp_query']->queried_object_id = 0;
		$shortcode_html                         = $manual_renderer->render_shortcode( array( 'product_id' => (string) $current_product_id ) );
		$duplicate_shortcode_html               = $manual_renderer->render_shortcode( array( 'product_id' => (string) $current_product_id ) );

		if ( 1 !== substr_count( $shortcode_html, '<section class="hamkharid-complements' ) || '' !== $duplicate_shortcode_html ) {
			throw new RuntimeException( 'Explicit product shortcode did not render once on a non-product page.' );
		}

		$block_renderer = new ProductRecommendations(
			new RuleMatcher( $repository ),
			new RecommendationEngine()
		);
		$block_html     = $block_renderer->render_block( array( 'productId' => $current_product_id ) );

		if ( 1 !== substr_count( $block_html, '<section class="hamkharid-complements' ) ) {
			throw new RuntimeException( 'The dynamic block render adapter did not accept an explicit product ID.' );
		}

		if ( '' !== $block_renderer->render_block( array() ) ) {
			throw new RuntimeException( 'The dynamic block rendered without a valid product context.' );
		}

		if ( ! isset( $GLOBALS['post'] ) || (int) $GLOBALS['post']->ID !== $current_product_id ) {
			throw new RuntimeException( 'The global post was not restored after complement rendering.' );
		}

		$restored_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;

		if ( ! $restored_product instanceof \WC_Product || $restored_product->get_id() !== $current_product_id ) {
			throw new RuntimeException( 'The global WooCommerce product was not restored after complement rendering.' );
		}

		remove_action( 'woocommerce_after_single_product_summary', array( $renderer, 'render' ), 18 );
		remove_action( 'woocommerce_after_main_content', array( $renderer, 'render' ), 5 );
		remove_action( 'woocommerce_after_single_product_summary', array( $manual_renderer, 'render' ), 18 );
		remove_action( 'woocommerce_after_main_content', array( $manual_renderer, 'render' ), 5 );

		echo "Frontend rendering integration test passed.\n";
	} finally {
		if ( false === $previous_settings ) {
			delete_option( DisplaySettings::OPTION_NAME );
		} else {
			update_option( DisplaySettings::OPTION_NAME, $previous_settings, false );
		}

		if ( $created_rule_id > 0 ) {
			wp_delete_post( $created_rule_id, true );
		}

		foreach ( $created_products as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		foreach ( array_reverse( $created_terms ) as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}

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
