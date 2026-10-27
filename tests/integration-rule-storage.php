<?php
/**
 * Lightweight WordPress integration smoke test for rule persistence.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

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

	if ( ! post_type_exists( RulePostType::POST_TYPE ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "The Hamkharid rule post type is not registered.\n" );
		exit( 1 );
	}

	$created_terms = array();
	$rule_id       = 0;

	try {
		foreach ( array( 'Hamkharid Source A', 'Hamkharid Source B', 'Hamkharid Target' ) as $term_name ) {
			$term_result = wp_insert_term( $term_name . ' ' . wp_generate_uuid4(), 'product_cat' );

			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$repository = new RuleRepository();
		$invalid    = $repository->create(
			'Invalid Integration Rule',
			true,
			array( PHP_INT_MAX ),
			PHP_INT_MAX
		);

		if ( ! is_wp_error( $invalid ) || 'hamkharid_source_category_required' !== $invalid->get_error_code() ) {
			throw new RuntimeException( 'Invalid category IDs were not rejected before persistence.' );
		}

		$result = $repository->create(
			'Integration Rule',
			true,
			array( $created_terms[0], $created_terms[1], $created_terms[0] ),
			$created_terms[2],
			4,
			Rule::SORT_NEWEST,
			73
		);

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( $result->get_error_message() );
		}

		$rule_id = $result;
		$rule    = $repository->find( $rule_id );

		if ( null === $rule ) {
			throw new RuntimeException( 'The stored rule could not be read.' );
		}

		$expected_sources = array( $created_terms[0], $created_terms[1] );

		if ( $expected_sources !== $rule->source_category_ids() ) {
			throw new RuntimeException( 'Source categories were not stored as independent, unique values.' );
		}

		if ( ! $rule->is_enabled() || 4 !== $rule->recommendation_count() || Rule::SORT_NEWEST !== $rule->sort_strategy() || 73 !== $rule->priority() ) {
			throw new RuntimeException( 'Stored rule values did not survive a round trip.' );
		}

		$stored_source_rows = get_post_meta( $rule_id, RulePostType::META_SOURCE_CATEGORY, false );

		if ( array( (string) $created_terms[0], (string) $created_terms[1] ) !== $stored_source_rows ) {
			throw new RuntimeException( 'Source categories were not persisted as separate metadata rows.' );
		}

		delete_post_meta( $rule_id, RulePostType::META_PRIORITY );
		$legacy_rule = $repository->find( $rule_id );

		if ( null === $legacy_rule || Rule::DEFAULT_PRIORITY !== $legacy_rule->priority() ) {
			throw new RuntimeException( 'A rule without priority metadata did not default to zero.' );
		}

		update_post_meta( $rule_id, RulePostType::META_PRIORITY, 73 );

		$matched_rule_ids = get_posts(
			array(
				'post_type'      => RulePostType::POST_TYPE,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Verifies the planned exact-match rule lookup.
				'meta_key'       => RulePostType::META_SOURCE_CATEGORY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Verifies the planned exact-match rule lookup.
				'meta_value'     => $created_terms[0],
			)
		);

		if ( ! in_array( $rule_id, $matched_rule_ids, true ) ) {
			throw new RuntimeException( 'A rule could not be queried by an exact source category metadata value.' );
		}

		$invalid_updates = array(
			array( 0, Rule::SORT_RANDOM, 0, 'hamkharid_recommendation_count_invalid' ),
			array( 7, Rule::SORT_RANDOM, 0, 'hamkharid_recommendation_count_invalid' ),
			array( 3, Rule::SORT_RANDOM, 101, 'hamkharid_priority_invalid' ),
			array( 3, 'invalid-sort', 0, 'hamkharid_sort_strategy_invalid' ),
		);

		foreach ( $invalid_updates as $invalid_update ) {
			$rejected = $repository->update(
				$rule_id,
				'Invalid Updated Rule',
				false,
				array( $created_terms[1] ),
				$created_terms[2],
				$invalid_update[0],
				$invalid_update[1],
				$invalid_update[2]
			);

			if ( ! is_wp_error( $rejected ) || $invalid_update[3] !== $rejected->get_error_code() ) {
				throw new RuntimeException( 'Invalid count, priority, or sort strategy was not rejected with its validation error.' );
			}

			$unchanged = $repository->find( $rule_id );

			if ( null === $unchanged || 'Integration Rule' !== $unchanged->name() || ! $unchanged->is_enabled() || 4 !== $unchanged->recommendation_count() || Rule::SORT_NEWEST !== $unchanged->sort_strategy() || 73 !== $unchanged->priority() ) {
				throw new RuntimeException( 'A rejected update changed the previously valid rule.' );
			}
		}

		$updated = $repository->update(
			$rule_id,
			'Updated Integration Rule',
			false,
			array( $created_terms[1] ),
			$created_terms[2],
			6,
			Rule::SORT_RANDOM,
			100
		);

		if ( is_wp_error( $updated ) ) {
			throw new RuntimeException( $updated->get_error_message() );
		}

		$rule = $repository->find( $rule_id );

		if (
			null === $rule
			|| $rule->is_enabled()
			|| array( $created_terms[1] ) !== $rule->source_category_ids()
			|| 6 !== $rule->recommendation_count()
			|| Rule::SORT_RANDOM !== $rule->sort_strategy()
			|| 100 !== $rule->priority()
		) {
			throw new RuntimeException( 'Valid updated values were not persisted correctly.' );
		}

		if ( false === $repository->delete( $rule_id, true ) || null !== $repository->find( $rule_id ) ) {
			throw new RuntimeException( 'The rule was not deleted cleanly.' );
		}

		$rule_id = 0;

		echo "Rule storage integration test passed.\n";
	} finally {
		if ( $rule_id > 0 ) {
			wp_delete_post( $rule_id, true );
		}

		foreach ( $created_terms as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}
	}
} )();
