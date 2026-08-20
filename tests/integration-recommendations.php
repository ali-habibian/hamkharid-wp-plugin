<?php
/**
 * Lightweight WordPress/WooCommerce integration test for recommendations.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
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

	$created_terms    = array();
	$created_products = array();

	try {
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

		$create_product( 'Excluded Out Of Stock', array( $target_id ), '2026-08-12 00:00:00', 999, 'outofstock' );
		$create_product( 'Excluded Hidden', array( $target_id ), '2026-08-11 00:00:00', 998, 'instock', 'hidden' );
		$create_product( 'Excluded Search Only', array( $target_id ), '2026-08-10 00:00:00', 997, 'instock', 'search' );
		$create_product( 'Excluded Draft', array( $target_id ), '2026-08-09 00:00:00', 996, 'instock', 'visible', 'draft' );
		$create_product( 'Excluded Other Category', array( $other_id ), '2026-08-14 00:00:00', 1001 );

		$engine             = new RecommendationEngine();
		$eligible_ids       = array( $parent_product_id, $child_product_id, $backorder_id, $third_product_id );
		$newest_rule        = new Rule( 1, 'Newest', true, array( $other_id ), $target_id, 3, Rule::SORT_NEWEST );
		$newest_products    = $engine->recommend( $current_product_id, $newest_rule );
		$newest_product_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$newest_products
		);

		if ( array( $child_product_id, $backorder_id, $third_product_id ) !== $newest_product_ids ) {
			throw new RuntimeException( 'Newest recommendations did not respect date ordering, descendants, or exclusions.' );
		}

		$best_selling_rule        = new Rule( 2, 'Best Selling', true, array( $other_id ), $target_id, 3, Rule::SORT_BEST_SELLING );
		$best_selling_products    = $engine->recommend( $current_product_id, $best_selling_rule );
		$best_selling_product_ids = array_map(
			static function ( $product ): int {
				return $product->get_id();
			},
			$best_selling_products
		);

		if ( array( $backorder_id, $child_product_id, $parent_product_id ) !== $best_selling_product_ids ) {
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
	}
} )();
