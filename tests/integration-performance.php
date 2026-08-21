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

		$created_rules[] = (int) $rule_id;
		$matcher         = new RuleMatcher( $repository );
		$engine          = new RecommendationEngine();

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
			if ( 1 !== $query_args['posts_per_page'] || 'ids' !== $query_args['fields'] ) {
				throw new RuntimeException( 'A rule matching query was not bounded to one rule ID.' );
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

		if ( 1 !== count( $newest['queries'] ) || 6 !== $newest['queries'][0]['limit'] || $newest['queries'][0]['paginate'] ) {
			throw new RuntimeException( 'Newest recommendations did not stay within a single bounded product query.' );
		}

		$best_selling = $run_strategy( Rule::SORT_BEST_SELLING );

		if ( 1 !== count( $best_selling['queries'] ) || 6 !== $best_selling['queries'][0]['limit'] || $best_selling['queries'][0]['paginate'] ) {
			throw new RuntimeException( 'Best-selling recommendations did not stay within a single bounded product query.' );
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

		if ( $candidate_limit_total > 24 ) {
			throw new RuntimeException( 'Random candidate hydration exceeded the bounded 24-product pool.' );
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

			if ( preg_match( '/\b(?:add|update)_option\s*\(/i', $contents ) ) {
				throw new RuntimeException( 'The plugin introduced an autoloadable option write without performance evidence.' );
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
