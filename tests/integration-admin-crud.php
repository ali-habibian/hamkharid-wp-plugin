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
		$admin->register_hooks();
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
		$_POST           = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[1] ),
			'hamkharid_target_category_id'   => (string) $created_terms[2],
			'hamkharid_recommendation_count' => '7',
			'hamkharid_sort_strategy'        => 'unsupported',
			'hamkharid_priority'             => '101',
		);
		$submitted_title = 'Invalid Submitted Rule Title';
		wp_update_post(
			array(
				'ID'          => $rule_id,
				'post_title'  => $submitted_title,
				'post_status' => 'publish',
			)
		);
		$invalid_count_redirect = $admin->add_save_error_to_redirect( admin_url( 'post.php?post=' . $rule_id . '&action=edit' ) );

		if ( false === strpos( $invalid_count_redirect, 'hamkharid_rule_error=hamkharid_recommendation_count_invalid' ) ) {
			throw new RuntimeException( 'The admin form did not reject an out-of-range recommendation count.' );
		}

		$preserved_rule = $repository->find( $rule_id );
		$submitted      = get_user_meta( $user_id, RuleAdmin::form_values_meta_key( $rule_id ), true );

		if (
			'publish' !== get_post_status( $rule_id )
			|| 'Admin Integration Rule' !== get_the_title( $rule_id )
			|| null === $preserved_rule
			|| ! $preserved_rule->is_enabled()
			|| array( $created_terms[0], $created_terms[1] ) !== $preserved_rule->source_category_ids()
			|| $created_terms[2] !== $preserved_rule->target_category_id()
			|| 5 !== $preserved_rule->recommendation_count()
			|| Rule::SORT_BEST_SELLING !== $preserved_rule->sort_strategy()
			|| 0 !== $preserved_rule->priority()
		) {
			throw new RuntimeException( 'An invalid update changed the active rule title, status, or stored metadata.' );
		}

		if ( ! is_array( $submitted ) || $submitted_title !== $submitted['name'] || '7' !== $submitted['recommendation_count'] || '101' !== $submitted['priority'] || 'unsupported' !== $submitted['sort_strategy'] ) {
			throw new RuntimeException( 'Invalid submitted form values were not preserved for the submitting administrator.' );
		}

		ob_start();
		$meta_box = new \Hamkharid\SmartCategoryComplement\Admin\RuleMetaBox( $repository );
		$meta_box->render( get_post( $rule_id ) );
		$rendered_form = (string) ob_get_clean();

		// translators: %s is the unsupported sorting strategy submitted by the test.
		$invalid_sort_label = sprintf( __( 'Invalid submitted value: %s', 'hamkharid' ), 'unsupported' );

		if ( false === strpos( $rendered_form, 'value="7"' ) || false === strpos( $rendered_form, 'value="101"' ) || false === strpos( $rendered_form, $invalid_sort_label ) || false === strpos( $rendered_form, $submitted_title ) ) {
			throw new RuntimeException( 'The admin edit form did not repopulate invalid submitted values.' );
		}

		if ( metadata_exists( 'user', $user_id, RuleAdmin::form_values_meta_key( $rule_id ) ) ) {
			throw new RuntimeException( 'Consumed invalid form data was not cleared after rendering the edit form.' );
		}

		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => '0',
			'hamkharid_recommendation_count' => '2',
			'hamkharid_sort_strategy'        => Rule::SORT_NEWEST,
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );

		if ( 'publish' !== get_post_status( $rule_id ) || 5 !== $repository->find( $rule_id )->recommendation_count() ) {
			throw new RuntimeException( 'An invalid update did not preserve the existing rule status and settings.' );
		}

		$redirect = $admin->add_save_error_to_redirect( admin_url( 'post.php?post=' . $rule_id . '&action=edit' ) );

		if ( false === strpos( $redirect, 'hamkharid_rule_error=hamkharid_target_category_required' ) ) {
			throw new RuntimeException( 'The validation error was not added to the admin redirect.' );
		}

		ob_start();
		$meta_box->render( get_post( $rule_id ) );
		$invalid_target_form = (string) ob_get_clean();

		$empty_target_selected = 1 === preg_match( '/<option value=""[^>]*selected/', $invalid_target_form );
		$saved_target_selected = 1 === preg_match( '/<option value="' . preg_quote( esc_attr( (string) $created_terms[2] ), '/' ) . '"[^>]*selected/', $invalid_target_form );

		if ( ! $empty_target_selected || $saved_target_selected ) {
			preg_match( '/<select id="hamkharid-target-category"[\s\S]*?<\/select>/', $invalid_target_form, $target_markup );
			throw new RuntimeException( 'An invalid empty target selection was not preserved in the edit form: ' . ( $target_markup[0] ?? 'target select not found' ) );
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

		$_POST = array(
			RuleAdmin::NONCE_NAME            => wp_create_nonce( RuleAdmin::NONCE_ACTION ),
			'hamkharid_enabled'              => '1',
			'hamkharid_source_category_ids'  => array( (string) $created_terms[0] ),
			'hamkharid_target_category_id'   => (string) $created_terms[2],
			'hamkharid_recommendation_count' => '۴',
			'hamkharid_sort_strategy'        => Rule::SORT_RANDOM,
			'hamkharid_priority'             => '٢',
		);
		$admin->save( $rule_id, get_post( $rule_id ), true );
		$localized_rule = $repository->find( $rule_id );

		if ( null === $localized_rule || 4 !== $localized_rule->recommendation_count() || 2 !== $localized_rule->priority() ) {
			throw new RuntimeException( 'The admin form did not normalize Persian numerals for count and priority.' );
		}

		update_user_meta( $user_id, 'locale', 'fa_IR' );
		ob_start();
		$meta_box->render( get_post( $rule_id ) );
		$localized_form = (string) ob_get_clean();
		ob_start();
		$list_table->render_column( 'hamkharid_count', $rule_id );
		$list_table->render_column( 'hamkharid_priority', $rule_id );
		$localized_columns = (string) ob_get_clean();

		if ( false === strpos( $localized_form, 'value="۴"' ) || false === strpos( $localized_form, 'value="۲"' ) || false === strpos( $localized_columns, '۴۲' ) ) {
			throw new RuntimeException( 'Human-facing rule counts and priority were not displayed with Persian digits in the Persian admin locale.' );
		}

		$_POST = array();
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
