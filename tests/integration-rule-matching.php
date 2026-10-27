<?php
/**
 * Lightweight WordPress integration test for complement rule matching.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
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
	$created_posts = array();
	$created_rules = array();

	try {
		$grandparent_result = wp_insert_term( 'Match Grandparent ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $grandparent_result ) ) {
			throw new RuntimeException( $grandparent_result->get_error_message() );
		}

		$parent_result = wp_insert_term(
			'Match Parent ' . wp_generate_uuid4(),
			'product_cat',
			array( 'parent' => $grandparent_result['term_id'] )
		);

		if ( is_wp_error( $parent_result ) ) {
			throw new RuntimeException( $parent_result->get_error_message() );
		}

		$child_result  = wp_insert_term(
			'Match Child ' . wp_generate_uuid4(),
			'product_cat',
			array( 'parent' => $parent_result['term_id'] )
		);
		$target_result = wp_insert_term( 'Match Target ' . wp_generate_uuid4(), 'product_cat' );
		$other_result  = wp_insert_term( 'Match Other ' . wp_generate_uuid4(), 'product_cat' );

		$term_results = array( $grandparent_result, $parent_result, $child_result, $target_result, $other_result );

		foreach ( $term_results as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Matching Product ' . wp_generate_uuid4(),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $product_id ) ) {
			throw new RuntimeException( $product_id->get_error_message() );
		}

		$created_posts[] = (int) $product_id;
		wp_set_object_terms( $product_id, array( $created_terms[2] ), 'product_cat' );

		$outside_product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Outside Product ' . wp_generate_uuid4(),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $outside_product_id ) ) {
			throw new RuntimeException( $outside_product_id->get_error_message() );
		}

		$created_posts[] = (int) $outside_product_id;
		wp_set_object_terms( $outside_product_id, array( $created_terms[4] ), 'product_cat' );

		$uncategorized_product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Uncategorized Matching Product ' . wp_generate_uuid4(),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $uncategorized_product_id ) ) {
			throw new RuntimeException( $uncategorized_product_id->get_error_message() );
		}

		$created_posts[] = (int) $uncategorized_product_id;
		wp_set_object_terms( $uncategorized_product_id, array(), 'product_cat' );

		$repository = new RuleRepository();
		$matcher    = new RuleMatcher( $repository );

		// Create less-specific rules first so their IDs are lower. Specificity must still win.
		$grandparent_rule_id         = $repository->create(
			'Grandparent Match Rule',
			true,
			array( $created_terms[0] ),
			$created_terms[3],
			3,
			'newest',
			100
		);
		$parent_rule_id              = $repository->create(
			'Parent Match Rule',
			true,
			array( $created_terms[1] ),
			$created_terms[3],
			3,
			'newest',
			90
		);
		$direct_rule_id              = $repository->create(
			'Direct Match Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3],
			3,
			'newest',
			10
		);
		$direct_tie_rule_id          = $repository->create(
			'Direct Tie Match Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3],
			3,
			'newest',
			20
		);
		$direct_priority_tie_rule_id = $repository->create(
			'Direct Priority Tie Match Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3],
			3,
			'newest',
			20
		);
		$legacy_zero_priority_id     = $repository->create(
			'Legacy Zero Priority Match Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3]
		);
		$explicit_zero_priority_id   = $repository->create(
			'Explicit Zero Priority Match Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3],
			3,
			'newest',
			0
		);

		if ( is_wp_error( $legacy_zero_priority_id ) || is_wp_error( $explicit_zero_priority_id ) ) {
			if ( ! is_wp_error( $legacy_zero_priority_id ) ) {
				wp_delete_post( (int) $legacy_zero_priority_id, true );
			}

			if ( ! is_wp_error( $explicit_zero_priority_id ) ) {
				wp_delete_post( (int) $explicit_zero_priority_id, true );
			}

			throw new RuntimeException( 'The zero-priority matching fixtures could not be created.' );
		}

		delete_post_meta( (int) $legacy_zero_priority_id, RulePostType::META_PRIORITY );
		$disabled_rule_id = $repository->create(
			'Disabled Match Rule',
			false,
			array( $created_terms[2] ),
			$created_terms[3]
		);
		$rule_ids         = array(
			$grandparent_rule_id,
			$parent_rule_id,
			$direct_rule_id,
			$direct_tie_rule_id,
			$direct_priority_tie_rule_id,
			$legacy_zero_priority_id,
			$explicit_zero_priority_id,
			$disabled_rule_id,
		);

		foreach ( $rule_ids as $rule_id ) {
			if ( is_wp_error( $rule_id ) ) {
				throw new RuntimeException( $rule_id->get_error_message() );
			}

			$created_rules[] = (int) $rule_id;
		}

		$matched_rule = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $direct_tie_rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'Specificity and priority did not select the highest-priority direct-category rule.' );
		}

		wp_delete_post( $direct_tie_rule_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $direct_tie_rule_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $direct_priority_tie_rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'Equal priorities did not use ascending rule ID.' );
		}

		wp_delete_post( $direct_priority_tie_rule_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $direct_priority_tie_rule_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $direct_rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'A lower-priority direct rule did not remain ahead of ancestors.' );
		}

		wp_delete_post( $direct_rule_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $direct_rule_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $legacy_zero_priority_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'A missing legacy priority was not treated as zero ahead of a later zero-priority rule.' );
		}

		wp_delete_post( $legacy_zero_priority_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $legacy_zero_priority_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $explicit_zero_priority_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'An explicit zero-priority rule did not remain eligible.' );
		}

		wp_delete_post( $explicit_zero_priority_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $explicit_zero_priority_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $parent_rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'The nearest ancestor rule did not outrank a farther ancestor rule.' );
		}

		$broken_target_result = wp_insert_term( 'Broken Direct Target ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $broken_target_result ) ) {
			throw new RuntimeException( $broken_target_result->get_error_message() );
		}

		$broken_target_id = (int) $broken_target_result['term_id'];
		$created_terms[]  = $broken_target_id;
		$broken_direct_id = $repository->create(
			'Broken High Priority Direct Rule',
			true,
			array( $created_terms[2] ),
			$broken_target_id,
			3,
			'newest',
			100
		);
		$valid_direct_id  = $repository->create(
			'Valid Lower Priority Direct Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3],
			3,
			'newest',
			5
		);

		foreach ( array( $broken_direct_id, $valid_direct_id ) as $fallback_rule_id ) {
			if ( is_wp_error( $fallback_rule_id ) ) {
				throw new RuntimeException( $fallback_rule_id->get_error_message() );
			}

			$created_rules[] = (int) $fallback_rule_id;
		}

		wp_delete_term( $broken_target_id, 'product_cat' );
		$created_terms = array_values( array_diff( $created_terms, array( $broken_target_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $valid_direct_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'A deleted target did not fall through to the next valid rule in its specificity tier.' );
		}

		wp_delete_post( $broken_direct_id, true );
		wp_delete_post( $valid_direct_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $broken_direct_id, (int) $valid_direct_id ) ) );

		wp_delete_post( $parent_rule_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $parent_rule_id ) ) );
		$matched_rule  = $matcher->match( $product_id );

		if ( null === $matched_rule || (int) $grandparent_rule_id !== $matched_rule->id() ) {
			throw new RuntimeException( 'The farther ancestor rule was not selected after more specific rules were removed.' );
		}

		if ( null !== $matcher->match( $outside_product_id ) ) {
			throw new RuntimeException( 'A product outside every source category matched a rule.' );
		}

		if ( null !== $matcher->match( $uncategorized_product_id ) ) {
			throw new RuntimeException( 'A product without source categories matched a rule.' );
		}

		if ( null !== $matcher->match( 0 ) ) {
			throw new RuntimeException( 'An invalid product ID matched a rule.' );
		}

		wp_delete_post( $grandparent_rule_id, true );
		$created_rules = array_values( array_diff( $created_rules, array( (int) $grandparent_rule_id ) ) );

		if ( null !== $matcher->match( $product_id ) ) {
			throw new RuntimeException( 'A disabled rule matched a product.' );
		}

		$deleted_source_result = wp_insert_term( 'Deleted Source ' . wp_generate_uuid4(), 'product_cat' );

		if ( is_wp_error( $deleted_source_result ) ) {
			throw new RuntimeException( $deleted_source_result->get_error_message() );
		}

		$deleted_source_id         = (int) $deleted_source_result['term_id'];
		$created_terms[]           = $deleted_source_id;
		$deleted_source_product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Deleted Source Product ' . wp_generate_uuid4(),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $deleted_source_product_id ) ) {
			throw new RuntimeException( $deleted_source_product_id->get_error_message() );
		}

		$created_posts[] = (int) $deleted_source_product_id;
		wp_set_object_terms( $deleted_source_product_id, array( $deleted_source_id ), 'product_cat' );

		$deleted_source_rule_id = $repository->create(
			'Deleted Source Rule',
			true,
			array( $deleted_source_id ),
			$created_terms[3]
		);

		if ( is_wp_error( $deleted_source_rule_id ) ) {
			throw new RuntimeException( $deleted_source_rule_id->get_error_message() );
		}

		$created_rules[] = (int) $deleted_source_rule_id;
		wp_delete_term( $deleted_source_id, 'product_cat' );
		$created_terms = array_values( array_diff( $created_terms, array( $deleted_source_id ) ) );

		if ( null !== $matcher->match( $deleted_source_product_id ) ) {
			throw new RuntimeException( 'A rule with a deleted source category matched a product.' );
		}

		$deleted_target_rule_id = $repository->create(
			'Deleted Target Rule',
			true,
			array( $created_terms[2] ),
			$created_terms[3]
		);

		if ( is_wp_error( $deleted_target_rule_id ) ) {
			throw new RuntimeException( $deleted_target_rule_id->get_error_message() );
		}

		$created_rules[] = (int) $deleted_target_rule_id;
		wp_delete_term( $created_terms[3], 'product_cat' );
		$created_terms = array_values( array_diff( $created_terms, array( $created_terms[3] ) ) );

		if ( null !== $matcher->match( $product_id ) ) {
			throw new RuntimeException( 'A rule with a deleted target category matched a product.' );
		}

		echo "Rule matching integration test passed.\n";
	} finally {
		foreach ( $created_rules as $rule_id ) {
			wp_delete_post( $rule_id, true );
		}

		foreach ( $created_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		foreach ( $created_terms as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}
	}
} )();
