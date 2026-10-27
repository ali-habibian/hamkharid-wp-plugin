<?php
/**
 * Lightweight WordPress integration test for the plugin security contract.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Admin\RuleAdmin;
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
	require_once ABSPATH . 'wp-admin/includes/user.php';

	$created_terms    = array();
	$rule_id          = 0;
	$administrator_id = 0;
	$subscriber_id    = 0;
	$original_user_id = get_current_user_id();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test harness restores the request global after exercising nonce handling.
	$original_post      = $_POST;
	$expected_meta_keys = array(
		RulePostType::META_SOURCE_CATEGORY,
		RulePostType::META_TARGET_CATEGORY,
		RulePostType::META_RECOMMENDATION_COUNT,
		RulePostType::META_SORT_STRATEGY,
		RulePostType::META_PRIORITY,
	);

	try {
		$root_source_files   = glob( dirname( __DIR__ ) . '/src/*.php' );
		$nested_source_files = glob( dirname( __DIR__ ) . '/src/*/*.php' );
		$production_files    = array_merge(
			array( dirname( __DIR__ ) . '/hamkharid.php' ),
			is_array( $root_source_files ) ? $root_source_files : array(),
			is_array( $nested_source_files ) ? $nested_source_files : array()
		);

		$forbidden_patterns = array(
			'/\\$_REQUEST/',
			'/\\$_COOKIE/',
			'/\\$_FILES/',
			'/\\badmin_post_nopriv_/',
			'/\\bwp_ajax_/',
			'/\\beval\\s*\\(/',
			'/\\bunserialize\\s*\\(/',
			'/\\bmaybe_unserialize\\s*\\(/',
			'/\\$wpdb->(?:query|insert|update|delete|get_var|get_row|get_col|get_results)\\s*\\(/',
		);

		foreach ( $production_files as $production_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source audit in CLI integration test.
			$contents = file_get_contents( $production_file );

			if ( false === $contents ) {
				throw new RuntimeException( 'A production source file could not be read for the security scan.' );
			}

			foreach ( $forbidden_patterns as $pattern ) {
				if ( 1 === preg_match( $pattern, $contents ) ) {
					throw new RuntimeException( 'A forbidden high-risk construct was found in production source: ' . basename( $production_file ) );
				}
			}

			if ( false !== strpos( $production_file, DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR ) && false === strpos( $contents, "defined( 'ABSPATH' ) || exit;" ) ) {
				throw new RuntimeException( 'A production class is missing the direct-access guard: ' . basename( $production_file ) );
			}
		}

		$post_type = get_post_type_object( RulePostType::POST_TYPE );

		if (
			null === $post_type
			|| $post_type->public
			|| $post_type->publicly_queryable
			|| $post_type->show_in_rest
		) {
			throw new RuntimeException( 'The rule post type is unexpectedly exposed publicly or through REST.' );
		}

		$registered_meta = get_registered_meta_keys( 'post', RulePostType::POST_TYPE );

		foreach ( $expected_meta_keys as $meta_key ) {
			if (
				! isset( $registered_meta[ $meta_key ] )
				|| ! empty( $registered_meta[ $meta_key ]['show_in_rest'] )
				|| ! is_callable( $registered_meta[ $meta_key ]['auth_callback'] ?? null )
			) {
				throw new RuntimeException( 'Rule metadata is missing its private/authenticated registration contract.' );
			}
		}

		foreach ( array( 'Security Source', 'Security Target' ) as $term_name ) {
			$term_result = wp_insert_term( $term_name . ' ' . wp_generate_uuid4(), 'product_cat' );

			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$administrator_result = wp_insert_user(
			array(
				'user_login' => 'hamkharid-security-admin-' . wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password( 24 ),
				'user_email' => 'hamkharid-security-admin-' . wp_generate_password( 8, false ) . '@example.test',
				'role'       => 'administrator',
			)
		);
		$subscriber_result    = wp_insert_user(
			array(
				'user_login' => 'hamkharid-security-subscriber-' . wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password( 24 ),
				'user_email' => 'hamkharid-security-subscriber-' . wp_generate_password( 8, false ) . '@example.test',
				'role'       => 'subscriber',
			)
		);

		if ( is_wp_error( $administrator_result ) || is_wp_error( $subscriber_result ) ) {
			throw new RuntimeException( 'Security test users could not be created.' );
		}

		$administrator_id = (int) $administrator_result;
		$subscriber_id    = (int) $subscriber_result;

		wp_set_current_user( $administrator_id );

		$repository  = new RuleRepository();
		$rule_result = $repository->create(
			'<script>alert(1)</script> Security Rule',
			true,
			array( $created_terms[0] ),
			$created_terms[1],
			3,
			Rule::SORT_NEWEST
		);

		if ( is_wp_error( $rule_result ) ) {
			throw new RuntimeException( $rule_result->get_error_message() );
		}

		$rule_id     = (int) $rule_result;
		$stored_rule = $repository->find( $rule_id );

		if ( null === $stored_rule || false !== strpos( $stored_rule->name(), '<script' ) ) {
			throw new RuntimeException( 'Rule names are not sanitized before persistence.' );
		}

		$admin       = new RuleAdmin( $repository );
		$rest_server = rest_get_server();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Integration test initializes the WordPress REST API.
		do_action( 'rest_api_init', $rest_server );
		$request = new WP_REST_Request( 'GET', '/hamkharid/v1/products' );

		wp_set_current_user( $subscriber_id );
		$subscriber_response = rest_do_request( $request );
		$subscriber_status   = $subscriber_response instanceof WP_REST_Response ? $subscriber_response->get_status() : ( $subscriber_response instanceof WP_Error ? (int) $subscriber_response->get_error_data()['status'] : 0 );

		if ( ! in_array( $subscriber_status, array( 401, 403 ), true ) ) {
			throw new RuntimeException( 'A Subscriber unexpectedly accessed the product search REST endpoint.' );
		}

		wp_set_current_user( $administrator_id );
		$admin_response = rest_do_request( $request );

		if ( ! $admin_response instanceof WP_REST_Response || 200 !== $admin_response->get_status() ) {
			throw new RuntimeException( 'An authorized administrator could not access the product search REST endpoint.' );
		}

		wp_set_current_user( $subscriber_id );
		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => (string) $created_terms[1],
			'hamkharid_recommendation_count' => '6',
			'hamkharid_sort_strategy'        => Rule::SORT_RANDOM,
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 3 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'A user without the required capability changed rule settings.' );
		}

		wp_set_current_user( $administrator_id );
		$_POST = array(
			RuleAdmin::NONCE_NAME            => 'invalid-security-nonce',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => (string) $created_terms[1],
			'hamkharid_recommendation_count' => '6',
			'hamkharid_sort_strategy'        => Rule::SORT_RANDOM,
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 3 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'Rule settings changed with an invalid nonce.' );
		}

		$invalid_submissions = array(
			array( '999', Rule::SORT_RANDOM, '0', 'hamkharid_recommendation_count_invalid' ),
			array( '3', '"><script>alert(1)</script>', '0', 'hamkharid_sort_strategy_invalid' ),
			array( '3', Rule::SORT_RANDOM, '101', 'hamkharid_priority_invalid' ),
		);

		foreach ( $invalid_submissions as $invalid_submission ) {
			$_POST = array(
				RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
				'hamkharid_enabled'              => '1',
				'hamkharid_source_category_ids'  => array( (string) $created_terms[0], 'not-an-id' ),
				'hamkharid_target_category_id'   => (string) $created_terms[1],
				'hamkharid_recommendation_count' => $invalid_submission[0],
				'hamkharid_sort_strategy'        => $invalid_submission[1],
				'hamkharid_priority'             => $invalid_submission[2],
			);
			$admin->save( $rule_id, get_post( $rule_id ), true );
			$redirect    = $admin->add_save_error_to_redirect( admin_url( 'post.php?post=' . $rule_id . '&action=edit' ) );
			$stored_rule = $repository->find( $rule_id );

			if ( false === strpos( $redirect, 'hamkharid_rule_error=' . $invalid_submission[3] ) ) {
				throw new RuntimeException( 'The admin form did not reject an invalid count, priority, or sort strategy.' );
			}

			if (
				null === $stored_rule
				|| ! $stored_rule->is_enabled()
				|| array( $created_terms[0] ) !== $stored_rule->source_category_ids()
				|| $created_terms[1] !== $stored_rule->target_category_id()
				|| 3 !== $stored_rule->recommendation_count()
				|| Rule::SORT_NEWEST !== $stored_rule->sort_strategy()
				|| 0 !== $stored_rule->priority()
			) {
				throw new RuntimeException( 'Admin input validation changed a previously valid rule.' );
			}
		}

		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0], array( 'nested' ) ),
			'hamkharid_target_category_id'   => (string) $created_terms[1],
			'hamkharid_recommendation_count' => '3',
			'hamkharid_sort_strategy'        => Rule::SORT_RANDOM,
			'hamkharid_priority'             => '0',
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );
		$malformed_rule = $repository->find( $rule_id );

		if ( null === $malformed_rule || array( $created_terms[0] ) !== $malformed_rule->source_category_ids() ) {
			throw new RuntimeException( 'A nested source-category value was not safely ignored.' );
		}

		echo "Security integration test passed.\n";
	} finally {
		$_POST = $original_post;
		wp_set_current_user( 0 );

		if ( $rule_id > 0 ) {
			wp_delete_post( $rule_id, true );
		}

		foreach ( $created_terms as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}

		if ( $administrator_id > 0 ) {
			wp_delete_user( $administrator_id );
		}

		if ( $subscriber_id > 0 ) {
			wp_delete_user( $subscriber_id );
		}

		if ( $original_user_id > 0 ) {
			wp_set_current_user( $original_user_id );
		}
	}
} )();
