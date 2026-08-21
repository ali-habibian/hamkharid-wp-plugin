<?php
/**
 * Lightweight WordPress/WooCommerce integration test for native Add-to-Cart UX.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Frontend\ProductRecommendations;
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

	if ( ! class_exists( 'WC_Product_Simple' ) || ! class_exists( 'WC_Product_Variable' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "WooCommerce must be active before running this test.\n" );
		exit( 1 );
	}

	$created_terms      = array();
	$created_products   = array();
	$created_variations = array();
	$created_rule_id    = 0;
	$previous_post      = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$previous_product   = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
	$previous_query     = isset( $GLOBALS['wp_query'] ) ? clone $GLOBALS['wp_query'] : null;

	try {
		$source_result = wp_insert_term( 'Add To Cart Source ' . wp_generate_uuid4(), 'product_cat' );
		$target_result = wp_insert_term( 'Add To Cart Target ' . wp_generate_uuid4(), 'product_cat' );

		foreach ( array( $source_result, $target_result ) as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$source_id = (int) $source_result['term_id'];
		$target_id = (int) $target_result['term_id'];

		$current_product = new \WC_Product_Simple();
		$current_product->set_name( 'Add To Cart Current ' . wp_generate_uuid4() );
		$current_product->set_status( 'publish' );
		$current_product->set_regular_price( '30' );
		$current_product->set_category_ids( array( $source_id ) );
		$current_product->set_stock_status( 'instock' );
		$current_product->set_catalog_visibility( 'visible' );
		$current_product_id = $current_product->save();
		$created_products[] = (int) $current_product_id;

		$simple_product = new \WC_Product_Simple();
		$simple_product->set_name( 'Add To Cart Simple ' . wp_generate_uuid4() );
		$simple_product->set_status( 'publish' );
		$simple_product->set_regular_price( '25' );
		$simple_product->set_category_ids( array( $target_id ) );
		$simple_product->set_stock_status( 'instock' );
		$simple_product->set_catalog_visibility( 'visible' );
		$simple_product_id  = $simple_product->save();
		$created_products[] = (int) $simple_product_id;

		$variable_product = new \WC_Product_Variable();
		$variable_product->set_name( 'Add To Cart Variable ' . wp_generate_uuid4() );
		$variable_product->set_status( 'publish' );
		$variable_product->set_category_ids( array( $target_id ) );
		$variable_product->set_stock_status( 'instock' );
		$variable_product->set_catalog_visibility( 'visible' );

		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$variable_product->set_attributes( array( $attribute ) );
		$variable_product_id = $variable_product->save();
		$created_products[]  = (int) $variable_product_id;

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $variable_product_id );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( '35' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'size' => 'Small' ) );
		$variation_id         = $variation->save();
		$created_variations[] = (int) $variation_id;
		\WC_Product_Variable::sync( $variable_product_id );
		wc_delete_product_transients( $variable_product_id );

		$repository      = new RuleRepository();
		$created_rule_id = $repository->create(
			'Native Add To Cart Rule',
			true,
			array( $source_id ),
			$target_id,
			2,
			Rule::SORT_NEWEST
		);

		if ( is_wp_error( $created_rule_id ) ) {
			throw new RuntimeException( $created_rule_id->get_error_message() );
		}

		$current_post = get_post( $current_product_id );

		if ( ! $current_post instanceof \WP_Post ) {
			throw new RuntimeException( 'The current Add-to-Cart test product could not be loaded.' );
		}

		$GLOBALS['post']    = $current_post;
		$GLOBALS['product'] = wc_get_product( $current_product_id );
		setup_postdata( $current_post );

		$GLOBALS['wp_query']->is_singular       = true;
		$GLOBALS['wp_query']->is_single         = true;
		$GLOBALS['wp_query']->is_page           = false;
		$GLOBALS['wp_query']->queried_object    = $current_post;
		$GLOBALS['wp_query']->queried_object_id = $current_product_id;

		$renderer = new ProductRecommendations(
			new RuleMatcher( $repository ),
			new RecommendationEngine()
		);

		ob_start();
		$renderer->render();
		$html = (string) ob_get_clean();

		$simple_product   = wc_get_product( $simple_product_id );
		$variable_product = wc_get_product( $variable_product_id );

		if ( ! $simple_product instanceof \WC_Product_Simple || ! $variable_product instanceof \WC_Product_Variable ) {
			throw new RuntimeException( 'The Add-to-Cart test products could not be reloaded.' );
		}

		$button_fragment = static function ( string $markup, int $product_id ): string {
			$pattern = '~<(?:a|button)\b[^>]*data-product_id="'
				. preg_quote( (string) $product_id, '~' )
				. '"[^>]*>.*?</(?:a|button)>~s';

			if ( 1 !== preg_match( $pattern, $markup, $matches ) ) {
				throw new RuntimeException( 'A native WooCommerce product-loop action could not be located.' );
			}

			return (string) $matches[0];
		};

		$simple_button = $button_fragment( $html, $simple_product_id );

		if (
			false === strpos( $simple_button, 'product_type_simple' )
			|| false === strpos( $simple_button, 'add_to_cart_button' )
		) {
			throw new RuntimeException( 'The simple complement did not render WooCommerce native Add-to-Cart markup.' );
		}

		if ( false === strpos( $simple_button, esc_url( $simple_product->add_to_cart_url() ) ) ) {
			throw new RuntimeException( 'The simple complement does not use WooCommerce\'s native Add-to-Cart URL.' );
		}

		$variable_button = $button_fragment( $html, $variable_product_id );

		if ( false === strpos( $variable_button, 'product_type_variable' ) ) {
			throw new RuntimeException( 'The variable complement did not render WooCommerce native loop action markup.' );
		}

		if ( false !== strpos( $variable_button, 'ajax_add_to_cart' ) ) {
			throw new RuntimeException( 'The variable complement incorrectly attempts direct AJAX Add-to-Cart.' );
		}

		if ( false === strpos( $variable_button, esc_url( $variable_product->get_permalink() ) ) ) {
			throw new RuntimeException( 'The variable complement does not link to its product page for option selection.' );
		}

		if ( false === strpos( $variable_button, esc_html( $variable_product->add_to_cart_text() ) ) ) {
			throw new RuntimeException( 'The variable complement does not use WooCommerce\'s native option-selection label.' );
		}

		if ( false !== strpos( $html, 'variations_form' ) || false !== strpos( $html, 'name="variation_id"' ) ) {
			throw new RuntimeException( 'The complement renderer attempted custom variation selection in the product loop.' );
		}

		if ( ! isset( $GLOBALS['post'] ) || (int) $GLOBALS['post']->ID !== $current_product_id ) {
			throw new RuntimeException( 'The global post was not restored after Add-to-Cart rendering.' );
		}

		$restored_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;

		if ( ! $restored_product instanceof \WC_Product || $restored_product->get_id() !== $current_product_id ) {
			throw new RuntimeException( 'The global WooCommerce product was not restored after Add-to-Cart rendering.' );
		}

		echo "Native Add-to-Cart integration test passed.\n";
	} finally {
		if ( $created_rule_id > 0 ) {
			wp_delete_post( $created_rule_id, true );
		}

		foreach ( $created_variations as $variation_id ) {
			wp_delete_post( $variation_id, true );
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
