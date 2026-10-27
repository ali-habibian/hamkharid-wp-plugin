<?php
/**
 * Lightweight WordPress/WooCommerce integration test for performance contracts.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\Rule;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to a WordPress installation before running this test.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';

	if ( ! class_exists( 'WC_Product_Simple' ) || ! post_type_exists( RulePostType::POST_TYPE ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "WooCommerce and the Hamkharid rule post type must be active before running this test.\n" );
		exit( 1 );
	}

	$created_terms    = array();
	$created_products = array();
	$created_rules    = array();

	try {
		$source_root_result = wp_insert_term( 'Performance Source Root ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $source_root_result ) ) {
			throw new RuntimeException( $source_root_result->get_error_message() );
		}

		$source_parent_result = wp_insert_term(
			'Performance Source Parent ' . wp_generate_uuid4(),
			'product_cat',
			array( 'parent' => $source_root_result['term_id'] )
		);

		if ( is_wp_error( $source_parent_result ) ) {
			throw new RuntimeException( $source_parent_result->get_error_message() );
		}

		$source_child_result = wp_insert_term(
			'Performance Source Child ' . wp_generate_uuid4(),
			'product_cat',
			array( 'parent' => $source_parent_result['term_id'] )
		);
		$target_result       = wp_insert_term( 'Performance Target ' . wp_generate_uuid4(), 'product_cat' );

		foreach ( array( $source_child_result, $target_result ) as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}
		}

		$source_root_id   = (int) $source_root_result['term_id'];
		$source_parent_id = (int) $source_parent_result['term_id'];
		$source_child_id  = (int) $source_child_result['term_id'];
		$target_id        = (int) $target_result['term_id'];
		$created_terms    = array( $source_root_id, $source_parent_id, $source_child_id, $target_id );

		$create_product = static function ( string $name, array $category_ids, int $sales = 0 ) use ( &$created_products ): int {
			$product = new \WC_Product_Simple();
			$product->set_name( $name . ' ' . wp_generate_uuid4() );
			$product->set_status( 'publish' );
			$product->set_regular_price( '10' );
			$product->set_category_ids( $category_ids );
			$product->set_stock_status( 'instock' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_total_sales( $sales );
			$product_id = $product->save();

			if ( $product_id <= 0 ) {
				throw new RuntimeException( 'A performance test product could not be created.' );
			}

			$created_products[] = (int) $product_id;

			return (int) $product_id;
		};

		$current_product_id = $create_product( 'Performance Current', array( $source_child_id ) );

		for ( $index = 1; $index <= 30; $index++ ) {
			$create_product( 'Performance Candidate ' . $index, array( $target_id ), $index );
		}

		$repository = new RuleRepository();
		$rule_id    = $repository->create(
			'Performance Root Rule',
			true,
			array( $source_root_id ),
			$target_id,
			6,
			Rule::SORT_RANDOM
		);

		if ( is_wp_error( $rule_id ) ) {
			throw new RuntimeException( $rule_id->get_error_message() );
		}

		$created_rules[]         = (int) $rule_id;
		$matcher                 = new RuleMatcher( $repository );
		$engine                  = new RecommendationEngine();
		$variation_target_result = wp_insert_term( 'Performance Variation Target ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $variation_target_result ) ) {
			throw new RuntimeException( $variation_target_result->get_error_message() );
		}

		$variation_target_id = (int) $variation_target_result['term_id'];
		$created_terms[]     = $variation_target_id;
		$variation_attribute = new \WC_Product_Attribute();
		$variation_attribute->set_id( 0 );
		$variation_attribute->set_name( 'Size' );
		$variation_attribute->set_options( array( 'Small', 'Large' ) );
		$variation_attribute->set_visible( true );
		$variation_attribute->set_variation( true );
		$variable_parent = new \WC_Product_Variable();
		$variable_parent->set_name( 'Performance Variable ' . wp_generate_uuid4() );
		$variable_parent->set_status( 'publish' );
		$variable_parent->set_attributes( array( $variation_attribute ) );
		$variable_parent->set_category_ids( array( $variation_target_id ) );
		$variable_parent->set_stock_status( 'instock' );
		$variable_parent->set_catalog_visibility( 'visible' );
		$variable_parent_id = (int) $variable_parent->save();

		if ( $variable_parent_id <= 0 ) {
			throw new RuntimeException( 'A variable product for the variation scan limit fixture could not be created.' );
		}

		$created_products[] = $variable_parent_id;
		$variation          = new \WC_Product_Variation();
		$variation->set_parent_id( $variable_parent_id );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( '10' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'size' => 'Small' ) );
		$real_variation_id = (int) $variation->save();

		if ( $real_variation_id <= 0 ) {
			throw new RuntimeException( 'A purchasable variation for the scan limit fixture could not be created.' );
		}

		$created_products[] = $real_variation_id;
		\WC_Product_Variable::sync( $variable_parent_id );
		wc_delete_product_transients( $variable_parent_id );
		$second_variable_parent = wc_get_product( $variable_parent_id );
		$second_variable_parent->set_id( 0 );
		$second_variable_parent->set_name( 'Performance Second Variable ' . wp_generate_uuid4() );
		$second_variable_parent_id = (int) $second_variable_parent->save();

		if ( $second_variable_parent_id <= 0 ) {
			throw new RuntimeException( 'A second variable product for the global scan limit fixture could not be created.' );
		}

		$created_products[]    = $second_variable_parent_id;
		$second_real_variation = wc_get_product( $real_variation_id );
		$second_real_variation->set_id( 0 );
		$second_real_variation->set_parent_id( $second_variable_parent_id );
		$second_real_variation_id = (int) $second_real_variation->save();

		if ( $second_real_variation_id <= 0 ) {
			throw new RuntimeException( 'A second purchasable variation for the global scan limit fixture could not be created.' );
		}

		$created_products[] = $second_real_variation_id;
		\WC_Product_Variable::sync( $second_variable_parent_id );
		wc_delete_product_transients( $second_variable_parent_id );
		$variation_rule_id = $repository->create(
			'Performance Variation Rule',
			true,
			array( $source_root_id ),
			$variation_target_id,
			1,
			Rule::SORT_NEWEST
		);

		if ( is_wp_error( $variation_rule_id ) ) {
			throw new RuntimeException( $variation_rule_id->get_error_message() );
		}

		$created_rules[]       = (int) $variation_rule_id;
		$variation_query_count = 0;
		$variable_parent_ids   = array( $variable_parent_id, $second_variable_parent_id );
		$variation_probe       = static function ( $results, array $args ) use ( $variable_parent_ids, &$variation_query_count ) {
			if ( ! isset( $args['parent'] ) || ! in_array( (int) $args['parent'], $variable_parent_ids, true ) ) {
				return $results;
			}

			++$variation_query_count;
			$parent_id = (int) $args['parent'];
			$offset    = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
			$limit     = isset( $args['limit'] ) ? (int) $args['limit'] : 1;
			$count     = min( $limit, max( 0, 501 - $offset ) );

			if ( 'ids' === ( $args['return'] ?? '' ) ) {
				return $count > 0 ? range( $offset + 10000, $offset + 9999 + $count ) : array();
			}

			$synthetic_variations = array();
			for ( $index = 0; $index < $count; $index++ ) {
				$synthetic_variation = new \WC_Product_Variation();
				$synthetic_variation->set_id( 100000 + $offset + $index );
				$synthetic_variation->set_parent_id( $parent_id );
				$synthetic_variation->set_status( 'publish' );
				$synthetic_variation->set_stock_status( 'instock' );
				$synthetic_variations[] = $synthetic_variation;
			}

			return $synthetic_variations;
		};
		add_filter( 'woocommerce_product_object_query', $variation_probe, 999, 2 );
		$variation_scan_engine          = new RecommendationEngine();
		$variation_scan_recommendations = $variation_scan_engine->recommend( $current_product_id, $repository->find( (int) $variation_rule_id ) );
		$variation_diagnostics          = $variation_scan_engine->diagnostics();
		remove_filter( 'woocommerce_product_object_query', $variation_probe, 999 );

		if ( 500 !== $variation_diagnostics['variation_candidates_scanned'] || empty( $variation_diagnostics['variation_scan_limit_reached'] ) || $variation_query_count > 22 ) {
			throw new RuntimeException(
				'Variable product scan limit mismatch: ' . wp_json_encode(
					array(
						'diagnostics'          => $variation_diagnostics,
						'query_count'          => $variation_query_count,
						'recommendation_count' => count( $variation_scan_recommendations ),
						'parent_purchasable'   => wc_get_product( $variable_parent_id )->is_purchasable(),
						'parent_category_ids'  => wc_get_product( $variable_parent_id )->get_category_ids(),
						'expected_target_id'   => $variation_target_id,
					)
				)
			);
		}

		$rule_queries = array();
		$rule_probe   = static function ( $query ) use ( &$rule_queries ): void {
			if ( RulePostType::POST_TYPE !== $query->get( 'post_type' ) ) {
				return;
			}

			$rule_queries[] = array(
				'fields'         => $query->get( 'fields' ),
				'posts_per_page' => (int) $query->get( 'posts_per_page' ),
			);
		};
		add_action( 'pre_get_posts', $rule_probe );

		$match_started = microtime( true );
		$matched_rule  = $matcher->match( $current_product_id );
		$match_elapsed = microtime( true ) - $match_started;
		remove_action( 'pre_get_posts', $rule_probe );

		if ( null === $matched_rule || (int) $rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'The performance fixture did not resolve its expected rule.' );
		}

		if ( 3 !== count( $rule_queries ) ) {
			throw new RuntimeException( 'Rule matching did not use one bounded query per category-specificity tier.' );
		}

		foreach ( $rule_queries as $query_args ) {
			if ( $query_args['posts_per_page'] < 1 || $query_args['posts_per_page'] > 50 || 'ids' !== $query_args['fields'] ) {
				throw new RuntimeException( 'A rule matching query exceeded the bounded page-size budget or loaded full post objects.' );
			}
		}

		$run_strategy = static function ( string $sort_strategy ) use ( $engine, $current_product_id, $matched_rule ): array {
			$captured_queries = array();
			$query_probe      = static function ( array $wp_query_args, array $query_vars ) use ( &$captured_queries ): array {
				$captured_queries[] = array(
					'limit'    => isset( $query_vars['limit'] ) ? (int) $query_vars['limit'] : 0,
					'orderby'  => isset( $query_vars['orderby'] ) ? (string) $query_vars['orderby'] : '',
					'paginate' => ! empty( $query_vars['paginate'] ),
					'return'   => isset( $query_vars['return'] ) ? (string) $query_vars['return'] : '',
				);

				return $wp_query_args;
			};
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_probe, 999, 2 );

			$rule = new Rule(
				$matched_rule->id(),
				$matched_rule->name(),
				true,
				$matched_rule->source_category_ids(),
				$matched_rule->target_category_id(),
				6,
				$sort_strategy
			);

			$started  = microtime( true );
			$products = $engine->recommend( $current_product_id, $rule );
			$elapsed  = microtime( true ) - $started;
			remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $query_probe, 999 );

			if ( 6 !== count( $products ) ) {
				throw new RuntimeException( 'A performance strategy did not respect the bounded recommendation count.' );
			}

			return array(
				'queries' => $captured_queries,
				'elapsed' => $elapsed,
			);
		};

		$newest = $run_strategy( Rule::SORT_NEWEST );

		if ( 1 !== count( $newest['queries'] ) || $newest['queries'][0]['limit'] > 24 || $newest['queries'][0]['paginate'] ) {
			throw new RuntimeException( 'Newest recommendations did not use one bounded candidate page without a full count query.' );
		}

		$best_selling = $run_strategy( Rule::SORT_BEST_SELLING );

		if ( 1 !== count( $best_selling['queries'] ) || $best_selling['queries'][0]['limit'] > 24 || $best_selling['queries'][0]['paginate'] ) {
			throw new RuntimeException( 'Best-selling recommendations did not use one bounded candidate page without a full count query.' );
		}

		$random = $run_strategy( Rule::SORT_RANDOM );

		if ( count( $random['queries'] ) < 2 || count( $random['queries'] ) > 3 ) {
			throw new RuntimeException( 'Random recommendations exceeded the expected count-plus-bounded-window query budget.' );
		}

		$count_query = array_shift( $random['queries'] );

		if ( 1 !== $count_query['limit'] || ! $count_query['paginate'] || 'ids' !== $count_query['return'] ) {
			throw new RuntimeException( 'Random population counting is not using the expected ID-only paginated query.' );
		}

		$candidate_limit_total = 0;

		foreach ( $random['queries'] as $candidate_query ) {
			if ( $candidate_query['paginate'] || $candidate_query['limit'] <= 0 || false !== stripos( $candidate_query['orderby'], 'rand' ) ) {
				throw new RuntimeException( 'Random candidate selection used an unbounded, paginated, or RAND-based query.' );
			}

			$candidate_limit_total += $candidate_query['limit'];
		}

		if ( $candidate_limit_total > 500 ) {
			throw new RuntimeException( 'Random candidate hydration exceeded the bounded 500-product inspection budget.' );
		}

		$root_production_files   = glob( dirname( __DIR__ ) . '/src/*.php' );
		$nested_production_files = glob( dirname( __DIR__ ) . '/src/*/*.php' );
		$production_files        = array_merge(
			false === $root_production_files ? array() : $root_production_files,
			false === $nested_production_files ? array() : $nested_production_files
		);

		foreach ( $production_files as $production_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local CLI regression test reads project source only.
			$contents = file_get_contents( $production_file );

			if ( false === $contents ) {
				throw new RuntimeException( 'A production source file could not be read during the performance audit.' );
			}

			$option_write_count   = preg_match_all( '/\b(?:add|update)_option\s*\(/i', $contents );
			$safe_settings_writes = preg_match_all(
				'/\b(?:add_option\s*\(\s*self::OPTION_NAME\s*,\s*self::defaults\(\s*\$mode\s*\)\s*,\s*\'\'\s*,\s*false\s*\)|update_option\s*\(\s*self::OPTION_NAME\s*,\s*\$settings\s*,\s*false\s*\))/i',
				$contents
			);

			if ( $option_write_count !== $safe_settings_writes ) {
				throw new RuntimeException( 'A plugin option write is not explicitly non-autoloaded or is outside the versioned display settings contract.' );
			}
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI benchmark output contains numeric timings only.
		printf(
			"Performance integration test passed. Fixture timings: match %.2f ms, newest %.2f ms, best-selling %.2f ms, random %.2f ms.\n",
			$match_elapsed * 1000,
			$newest['elapsed'] * 1000,
			$best_selling['elapsed'] * 1000,
			$random['elapsed'] * 1000
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	} finally {
		foreach ( $created_rules as $created_rule_id ) {
			wp_delete_post( $created_rule_id, true );
		}

		foreach ( $created_products as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		foreach ( array_reverse( $created_terms ) as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}
	}
} )();
