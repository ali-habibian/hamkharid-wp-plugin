<?php
/**
 * Lightweight WordPress/WooCommerce integration test for recommendations.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;
use Hamkharid\SmartCategoryComplement\Rules\Rule;

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
	$missing_settings  = new stdClass();
	$previous_settings = get_option( DisplaySettings::OPTION_NAME, $missing_settings );

	try {
		update_option( DisplaySettings::OPTION_NAME, array( 'allow_backorders' => false ) );

		$target_result = wp_insert_term( 'Recommendation Target ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $target_result ) ) {
			throw new RuntimeException( $target_result->get_error_message() );
		}

		$target_id       = (int) $target_result['term_id'];
		$created_terms[] = $target_id;
		$child_result    = wp_insert_term(
			'Recommendation Child ' . wp_generate_uuid4(),
			'product_cat',
			array( 'parent' => $target_id )
		);
		$other_result    = wp_insert_term( 'Recommendation Other ' . wp_generate_uuid4(), 'product_cat' );

		foreach ( array( $child_result, $other_result ) as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$child_id = (int) $child_result['term_id'];
		$other_id = (int) $other_result['term_id'];

		$create_product = static function (
			string $name,
			array $category_ids,
			string $date_created,
			int $total_sales,
			string $stock_status = 'instock',
			string $visibility = 'visible',
			string $status = 'publish'
		) use ( &$created_products ): int {
			$product = new \WC_Product_Simple();
			$product->set_name( $name . ' ' . wp_generate_uuid4() );
			$product->set_status( $status );
			$product->set_regular_price( '10' );
			$product->set_category_ids( $category_ids );
			$product->set_date_created( $date_created );
			$product->set_total_sales( $total_sales );
			$product->set_stock_status( $stock_status );

			if ( 'onbackorder' === $stock_status ) {
				$product->set_manage_stock( true );
				$product->set_stock_quantity( 0 );
				$product->set_backorders( 'yes' );
			}

			$product->set_catalog_visibility( $visibility );
			$product_id = $product->save();

			if ( $product_id <= 0 ) {
				throw new RuntimeException( 'A recommendation test product could not be created.' );
			}

			$created_products[] = (int) $product_id;

			return (int) $product_id;
		};

		$current_product_id = $create_product( 'Current Product', array( $target_id ), '2026-08-13 00:00:00', 1000 );
		$parent_product_id  = $create_product( 'Eligible Parent', array( $target_id ), '2026-01-01 00:00:00', 4, 'instock', 'catalog' );
		$child_product_id   = $create_product( 'Eligible Child', array( $child_id ), '2026-05-01 00:00:00', 10 );
		$backorder_id       = $create_product( 'Eligible Backorder', array( $child_id ), '2026-04-01 00:00:00', 20, 'onbackorder' );
		$third_product_id   = $create_product( 'Eligible Third', array( $target_id ), '2026-03-01 00:00:00', 1 );
		$variable_product   = new \WC_Product_Variable();
		$variable_product->set_name( 'Eligible Variable ' . wp_generate_uuid4() );
		$variable_product->set_status( 'publish' );
		$variable_product->set_category_ids( array( $target_id ) );
		$variable_product->set_date_created( '2026-06-01 00:00:00' );
		$variable_product->set_stock_status( 'instock' );
		$variable_product->set_catalog_visibility( 'visible' );
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$variable_product->set_attributes( array( $attribute ) );
		$variable_product_id = (int) $variable_product->save();
		$created_products[]  = $variable_product_id;
		$variation           = new \WC_Product_Variation();
		$variation->set_parent_id( $variable_product_id );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( '25' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'size' => 'Small' ) );
		$eligible_variation_id = (int) $variation->save();
		$created_products[]    = $eligible_variation_id;
		\WC_Product_Variable::sync( $variable_product_id );
		wc_delete_product_transients( $variable_product_id );

		$unavailable_variable = new \WC_Product_Variable();
		$unavailable_variable->set_name( 'Variable Without Purchasable Variation ' . wp_generate_uuid4() );
		$unavailable_variable->set_status( 'publish' );
		$unavailable_variable->set_category_ids( array( $target_id ) );
		$unavailable_variable->set_stock_status( 'instock' );
		$unavailable_variable->set_catalog_visibility( 'visible' );
		$unavailable_variable->set_attributes( array( $attribute ) );
		$unavailable_variable_id = (int) $unavailable_variable->save();
		$created_products[]      = $unavailable_variable_id;
		$unavailable_variation   = new \WC_Product_Variation();
		$unavailable_variation->set_parent_id( $unavailable_variable_id );
		$unavailable_variation->set_status( 'publish' );
		$unavailable_variation->set_stock_status( 'outofstock' );
		$unavailable_variation->set_attributes( array( 'size' => 'Large' ) );
		$unavailable_variation_id = (int) $unavailable_variation->save();
		$created_products[]       = $unavailable_variation_id;
		\WC_Product_Variable::sync( $unavailable_variable_id );
		$unavailable_variable = wc_get_product( $unavailable_variable_id );
		$unavailable_variable->set_regular_price( '15' );
		$unavailable_variable->set_stock_status( 'instock' );
		$unavailable_variable->save();
		wc_delete_product_transients( $unavailable_variable_id );

		$create_product( 'Excluded Out Of Stock', array( $target_id ), '2026-08-12 00:00:00', 999, 'outofstock' );
		$create_product( 'Excluded Hidden', array( $target_id ), '2026-08-11 00:00:00', 998, 'instock', 'hidden' );
		$create_product( 'Excluded Search Only', array( $target_id ), '2026-08-10 00:00:00', 997, 'instock', 'search' );
		$create_product( 'Excluded Draft', array( $target_id ), '2026-08-09 00:00:00', 996, 'instock', 'visible', 'draft' );
		$create_product( 'Excluded Other Category', array( $other_id ), '2026-08-14 00:00:00', 1001 );
		$unpriced_product = new \WC_Product_Simple();
		$unpriced_product->set_name( 'Excluded Without Price ' . wp_generate_uuid4() );
		$unpriced_product->set_status( 'publish' );
		$unpriced_product->set_category_ids( array( $target_id ) );
		$unpriced_product->set_stock_status( 'instock' );
		$unpriced_product->set_catalog_visibility( 'visible' );
		$unpriced_product_id = (int) $unpriced_product->save();
		$created_products[]  = $unpriced_product_id;

		$external_product = new \WC_Product_External();
		$external_product->set_name( 'Excluded External Product ' . wp_generate_uuid4() );
		$external_product->set_status( 'publish' );
		$external_product->set_regular_price( '12' );
		$external_product->set_product_url( 'https://example.test/external' );
		$external_product->set_button_text( 'Visit product' );
		$external_product->set_category_ids( array( $target_id ) );
		$external_product->set_stock_status( 'instock' );
		$external_product->set_catalog_visibility( 'visible' );
		$external_product_id = (int) $external_product->save();
		$created_products[]  = $external_product_id;

		$grouped_product = new \WC_Product_Grouped();
		$grouped_product->set_name( 'Excluded Grouped Product ' . wp_generate_uuid4() );
		$grouped_product->set_status( 'publish' );
		$grouped_product->set_category_ids( array( $target_id ) );
		$grouped_product->set_stock_status( 'instock' );
		$grouped_product->set_catalog_visibility( 'visible' );
		$grouped_product_id = (int) $grouped_product->save();
		$created_products[] = $grouped_product_id;

		$engine                               = new RecommendationEngine();
		$eligible_ids                         = array( $parent_product_id, $child_product_id, $third_product_id, $variable_product_id );
		$newest_rule                          = new Rule( 1, 'Newest', true, array( $other_id ), $target_id, 3, Rule::SORT_NEWEST );
		$force_unavailable_parent_purchasable = static function ( $purchasable, $product ) use ( $unavailable_variable_id ) {
			return $unavailable_variable_id === $product->get_id() ? true : $purchasable;
		};
		add_filter( 'woocommerce_is_purchasable', $force_unavailable_parent_purchasable, 999, 2 );
		$newest_products = $engine->recommend( $current_product_id, $newest_rule );
		remove_filter( 'woocommerce_is_purchasable', $force_unavailable_parent_purchasable, 999 );
		$newest_product_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$newest_products
		);

		if ( array( $variable_product_id, $child_product_id, $third_product_id ) !== $newest_product_ids ) {
			throw new RuntimeException( 'Newest recommendations did not refill after ineligible product types, products without a price, backorders, or variables without a purchasable variation.' );
		}

		$variable_products = $engine->recommend(
			$current_product_id,
			new Rule( 7, 'Variable', true, array( $other_id ), $target_id, 4, Rule::SORT_NEWEST )
		);
		$variable_ids      = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$variable_products
		);

		if ( ! in_array( $variable_product_id, $variable_ids, true ) ) {
			throw new RuntimeException( 'A variable product with a purchasable in-stock variation was excluded.' );
		}

		update_option( DisplaySettings::OPTION_NAME, array( 'allow_backorders' => true ) );
		$backorder_products    = $engine->recommend( $current_product_id, new Rule( 8, 'Backorders Enabled', true, array( $other_id ), $target_id, 5, Rule::SORT_NEWEST ) );
		$backorder_product_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$backorder_products
		);

		if ( ! in_array( $backorder_id, $backorder_product_ids, true ) || count( $backorder_product_ids ) !== 5 ) {
			throw new RuntimeException( 'Backorders enabled did not admit the purchasable on-backorder product and fill the requested count.' );
		}

		update_option( DisplaySettings::OPTION_NAME, array( 'allow_backorders' => false ) );

		$single_rule     = new Rule(
			6,
			'Single Recommendation',
			true,
			array( $other_id ),
			$target_id,
			1,
			Rule::SORT_NEWEST
		);
		$single_products = $engine->recommend( $current_product_id, $single_rule );

		if ( 1 !== count( $single_products ) || $variable_product_id !== $single_products[0]->get_id() ) {
			throw new RuntimeException( 'Recommendation Count = 1 was not respected.' );
		}

		$best_selling_rule        = new Rule( 2, 'Best Selling', true, array( $other_id ), $target_id, 3, Rule::SORT_BEST_SELLING );
		$best_selling_products    = $engine->recommend( $current_product_id, $best_selling_rule );
		$best_selling_product_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$best_selling_products
		);

		if ( array( $child_product_id, $parent_product_id, $third_product_id ) !== $best_selling_product_ids ) {
			throw new RuntimeException( 'Best-selling recommendations did not follow WooCommerce popularity ordering.' );
		}

		if ( false !== has_filter( 'posts_clauses', array( \WC()->query, 'order_by_popularity_post_clauses' ) ) ) {
			throw new RuntimeException( 'WooCommerce popularity ordering filter leaked after the recommendation query.' );
		}

		$database_rand_used = false;
		$orderby_probe      = static function ( string $orderby ) use ( &$database_rand_used ): string {
			if ( false !== stripos( $orderby, 'RAND(' ) ) {
				$database_rand_used = true;
			}

			return $orderby;
		};
		add_filter( 'posts_orderby', $orderby_probe, 999 );

		$random_rule     = new Rule( 3, 'Random', true, array( $other_id ), $target_id, 3, Rule::SORT_RANDOM );
		$random_products = $engine->recommend( $current_product_id, $random_rule );
		remove_filter( 'posts_orderby', $orderby_probe, 999 );
		$random_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$random_products
		);

		if ( 3 !== count( $random_ids ) || array() !== array_diff( $random_ids, $eligible_ids ) ) {
			throw new RuntimeException( 'Random recommendations returned an invalid product or count.' );
		}

		if ( $database_rand_used ) {
			throw new RuntimeException( 'Random recommendations used database-level ORDER BY RAND().' );
		}

		$disabled_rule = new Rule( 4, 'Disabled', false, array( $other_id ), $target_id, 3, Rule::SORT_NEWEST );

		if ( array() !== $engine->recommend( $current_product_id, $disabled_rule ) ) {
			throw new RuntimeException( 'A disabled rule returned recommendations.' );
		}

		$missing_target_rule = new Rule( 5, 'Missing Target', true, array( $other_id ), PHP_INT_MAX, 3, Rule::SORT_NEWEST );

		if ( array() !== $engine->recommend( $current_product_id, $missing_target_rule ) ) {
			throw new RuntimeException( 'A rule with a missing target category returned recommendations.' );
		}

		if ( array() !== $engine->recommend( 0, $newest_rule ) ) {
			throw new RuntimeException( 'An invalid current product ID returned recommendations.' );
		}

		echo "Recommendation integration test passed.\n";
	} finally {
		foreach ( $created_products as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		foreach ( array_reverse( $created_terms ) as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}

		if ( $missing_settings !== $previous_settings ) {
			update_option( DisplaySettings::OPTION_NAME, $previous_settings );
		} else {
			delete_option( DisplaySettings::OPTION_NAME );
		}
	}
} )();
