<?php
/**
 * Lightweight WordPress integration test for admin rule persistence.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Admin\RuleAdmin;
use Hamkharid\SmartCategoryComplement\Admin\RuleListTable;
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

	$created_terms = array();
	$rule_id       = 0;
	$user_id       = 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test harness restores the request global after exercising nonce handling.
	$original_post = $_POST;

	try {
		foreach ( array( 'Admin Source A', 'Admin Source B', 'Admin Target' ) as $term_name ) {
			$term_result = wp_insert_term( $term_name . ' ' . wp_generate_uuid4(), 'product_cat' );

			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => 'hamkharid-admin-' . wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password( 24 ),
				'user_email' => 'hamkharid-' . wp_generate_password( 8, false ) . '@example.test',
				'role'       => 'administrator',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			throw new RuntimeException( $user_id->get_error_message() );
		}

		wp_set_current_user( $user_id );

		$repository = new RuleRepository();
		$rule_id    = $repository->create(
			'Admin Integration Rule',
			false,
			array( $created_terms[0] ),
			$created_terms[2]
		);

		if ( is_wp_error( $rule_id ) ) {
			throw new RuntimeException( $rule_id->get_error_message() );
		}

		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0], (string) $created_terms[1] ),
			'hamkharid_target_category_id'   => (string) $created_terms[2],
			'hamkharid_recommendation_count' => '5',
			'hamkharid_sort_strategy'        => Rule::SORT_BEST_SELLING,
		);

		$admin = new RuleAdmin( $repository );
		$admin->save( $rule_id, get_post( $rule_id ), true );
		$rule = $repository->find( $rule_id );

		if (
			null === $rule
			|| ! $rule->is_enabled()
			|| array( $created_terms[0], $created_terms[1] ) !== $rule->source_category_ids()
			|| 5 !== $rule->recommendation_count()
			|| Rule::SORT_BEST_SELLING !== $rule->sort_strategy()
		) {
			throw new RuntimeException( 'A valid nonce-protected admin save did not persist the submitted values.' );
		}

		$_POST = array(
			'hamkharid_recommendation_count' => '1',
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 5 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'Admin fields changed without a valid nonce.' );
		}

		$test_user = get_userdata( $user_id );
		$test_user->set_role( 'subscriber' );
		wp_set_current_user( 0 );
		wp_set_current_user( $user_id );

		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => (string) $created_terms[2],
			'hamkharid_recommendation_count' => '1',
			'hamkharid_sort_strategy'        => Rule::SORT_RANDOM,
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 5 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'Admin fields changed without the required capability.' );
		}

		$test_user->set_role( 'administrator' );
		wp_set_current_user( 0 );
		wp_set_current_user( $user_id );
		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => '0',
			'hamkharid_recommendation_count' => '2',
			'hamkharid_sort_strategy'        => Rule::SORT_NEWEST,
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 'draft' !== get_post_status( $rule_id ) || 5 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'An invalid rule was not safely drafted without overwriting its stored settings.' );
		}

		$redirect = $admin->add_save_error_to_redirect( admin_url( 'post.php?post=' . $rule_id . '&action=edit' ) );

		if ( false === strpos( $redirect, 'hamkharid_rule_error=hamkharid_target_category_required' ) ) {
			throw new RuntimeException( 'The validation error was not added to the admin redirect.' );
		}

		$list_table   = new RuleListTable( $repository );
		$bulk_actions = $list_table->bulk_actions(
			array(
				'edit'  => 'Edit',
				'trash' => 'Trash',
			)
		);

		if ( isset( $bulk_actions['edit'] ) ) {
			throw new RuntimeException( 'Bulk editing remains available for complement rules.' );
		}

		$row_actions = $list_table->row_actions(
			array( 'inline hide-if-no-js' => 'Quick Edit' ),
			get_post( $rule_id )
		);

		if ( isset( $row_actions['inline hide-if-no-js'] ) || ! isset( $row_actions['hamkharid_toggle'] ) ) {
			throw new RuntimeException( 'Rule row actions did not replace Quick Edit with the secure status action.' );
		}

		wp_trash_post( $rule_id );

		if ( isset( $list_table->row_actions( array(), get_post( $rule_id ) )['hamkharid_toggle'] ) ) {
			throw new RuntimeException( 'The status action remains available for a trashed rule.' );
		}

		echo "Admin CRUD integration test passed.\n";
	} finally {
		$_POST = $original_post;

		if ( $rule_id > 0 ) {
			wp_delete_post( $rule_id, true );
		}

		foreach ( $created_terms as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}

		if ( $user_id > 0 ) {
			wp_delete_user( $user_id );
		}
	}
} )();
