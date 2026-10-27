<?php
/**
 * Creates and removes deterministic WordPress/WooCommerce browser QA fixtures.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;
use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;

( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to a WordPress installation before running browser QA.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';

	$action = isset( $_SERVER['argv'][1] ) ? sanitize_key( wp_unslash( (string) $_SERVER['argv'][1] ) ) : '';

	if (
		! class_exists( 'WC_Product_Simple' )
		|| ! class_exists( 'WC_Product_Variable' )
		|| ! class_exists( 'WC_Product_Variation' )
		|| ! class_exists( RuleRepository::class )
	) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "WooCommerce and Hamkharid must be active before running browser QA.\n" );
		exit( 1 );
	}

	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	$meta_key                 = '_hamkharid_e2e_run';
	$theme_snapshot_option    = '_hamkharid_e2e_original_theme';
	$locale_snapshot_option   = '_hamkharid_e2e_original_locale';
	$settings_snapshot_option = '_hamkharid_e2e_original_settings';

	$delete_stale_fixtures = static function () use ( $meta_key, $theme_snapshot_option, $locale_snapshot_option, $settings_snapshot_option ): void {
		$original_theme = (string) get_option( $theme_snapshot_option, '' );

		if ( '' !== $original_theme && wp_get_theme( $original_theme )->exists() ) {
			switch_theme( $original_theme );
		}

		if ( '' !== $original_theme ) {
			delete_option( $theme_snapshot_option );
		}

		$original_locale = get_option( $locale_snapshot_option, null );

		if ( null !== $original_locale ) {
			update_option( 'WPLANG', $original_locale );
			delete_option( $locale_snapshot_option );
		}

		$original_settings = get_option( $settings_snapshot_option, '__missing__' );

		if ( '__missing__' !== $original_settings ) {
			if ( false === $original_settings ) {
				delete_option( DisplaySettings::OPTION_NAME );
			} else {
				update_option( DisplaySettings::OPTION_NAME, $original_settings, false );
			}
			delete_option( $settings_snapshot_option );
		}

		$post_ids = get_posts(
			array(
				'post_type'      => array( 'product', 'product_variation', 'page', RulePostType::POST_TYPE ),
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Private QA cleanup marker.
				'meta_key'       => $meta_key,
			)
		);

		$ui_rule_ids = get_posts(
			array(
				'post_type'      => RulePostType::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				's'              => 'Hamkharid E2E UI Rule',
			)
		);

		$post_ids = array_values( array_unique( array_merge( $post_ids, $ui_rule_ids ) ) );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		$users = get_users(
			array(
				'fields'   => 'ID',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- QA cleanup marker.
				'meta_key' => $meta_key,
			)
		);

		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id );
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- QA cleanup marker.
				'meta_key'   => $meta_key,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( (int) $term->term_id, 'product_cat' );
			}
		}
	};

	if ( 'cleanup' === $action ) {
		$delete_stale_fixtures();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed CLI status output.
		echo "Browser QA fixtures removed.\n";
		return;
	}

	if ( 'switch-theme' === $action ) {
		$theme        = isset( $_SERVER['argv'][2] ) ? sanitize_key( wp_unslash( (string) $_SERVER['argv'][2] ) ) : 'twentytwentyfive';
		$theme_object = wp_get_theme( $theme );

		if ( ! $theme_object->exists() ) {
			throw new RuntimeException( 'The requested QA theme is not installed: ' . esc_html( $theme ) );
		}

		if ( false === get_option( $theme_snapshot_option, false ) ) {
			add_option( $theme_snapshot_option, get_stylesheet(), '', false );
		}

		switch_theme( $theme );
		echo 'Theme switched to ' . esc_html( $theme ) . ".\n";
		return;
	}

	if ( 'switch-locale' === $action ) {
		$locale = isset( $_SERVER['argv'][2] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['argv'][2] ) ) : '';

		if ( ! in_array( $locale, array( 'fa_IR', 'en_US' ), true ) ) {
			throw new RuntimeException( 'Only fa_IR and en_US are supported by the locale fixture.' );
		}

		if ( false === get_option( $locale_snapshot_option, false ) ) {
			add_option( $locale_snapshot_option, get_option( 'WPLANG', '' ), '', false );
		}

		$stored_locale = 'en_US' === $locale ? '' : $locale;
		update_option( 'WPLANG', $stored_locale );
		echo 'Locale switched to ' . esc_html( $locale ) . ".\n";
		return;
	}

	if ( 'setup' !== $action ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Usage: php tests/e2e/fixture.php setup|cleanup\n" );
		exit( 1 );
	}

	$delete_stale_fixtures();
	add_option( $settings_snapshot_option, get_option( DisplaySettings::OPTION_NAME, false ), '', false );

	$run_id = substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 12 );

	$mark_post = static function ( int $post_id ) use ( $meta_key, $run_id ): void {
		update_post_meta( $post_id, $meta_key, $run_id );
	};

	$create_term = static function ( string $name, int $parent_id = 0 ) use ( $meta_key, $run_id ): int {
		$result = wp_insert_term(
			$name,
			'product_cat',
			array( 'parent' => $parent_id )
		);

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( esc_html( $result->get_error_message() ) );
		}

		$term_id = (int) $result['term_id'];
		update_term_meta( $term_id, $meta_key, $run_id );

		return $term_id;
	};

	$create_simple_product = static function (
		string $name,
		array $category_ids,
		string $stock_status = 'instock',
		string $visibility = 'visible'
	) use ( $mark_post ): int {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_regular_price( '29' );
		$product->set_category_ids( $category_ids );
		$product->set_stock_status( $stock_status );
		$product->set_catalog_visibility( $visibility );

		if ( 'onbackorder' === $stock_status ) {
			$product->set_backorders( 'yes' );
		}

		$product_id = (int) $product->save();

		if ( $product_id <= 0 ) {
			throw new RuntimeException( 'A browser QA product could not be created.' );
		}

		$mark_post( $product_id );

		return $product_id;
	};

	try {
		$admin_password      = wp_generate_password( 24, true, true );
		$subscriber_password = wp_generate_password( 24, true, true );
		$admin_username      = 'hamkharid_e2e_admin_' . $run_id;
		$subscriber_username = 'hamkharid_e2e_sub_' . $run_id;
		$admin_id            = wp_insert_user(
			array(
				'user_login' => $admin_username,
				'user_pass'  => $admin_password,
				'user_email' => $admin_username . '@example.test',
				'role'       => 'administrator',
			)
		);
		$subscriber_id       = wp_insert_user(
			array(
				'user_login' => $subscriber_username,
				'user_pass'  => $subscriber_password,
				'user_email' => $subscriber_username . '@example.test',
				'role'       => 'subscriber',
			)
		);

		if ( is_wp_error( $admin_id ) || is_wp_error( $subscriber_id ) ) {
			throw new RuntimeException( 'Browser QA users could not be created.' );
		}

		update_user_meta( (int) $admin_id, $meta_key, $run_id );
		update_user_meta( (int) $subscriber_id, $meta_key, $run_id );

		$source_parent_id = $create_term( 'Hamkharid E2E Source Parent ' . $run_id );
		$source_child_id  = $create_term( 'Hamkharid E2E Source Child ' . $run_id, $source_parent_id );
		$target_parent_id = $create_term( 'Hamkharid E2E Target Parent ' . $run_id );
		$target_child_id  = $create_term( 'Hamkharid E2E Target Child ' . $run_id, $target_parent_id );
		$admin_source_id  = $create_term( 'Hamkharid E2E Admin Source ' . $run_id );

		$current_product_id = $create_simple_product(
			'Hamkharid E2E Current ' . $run_id,
			array( $source_child_id, $target_parent_id )
		);
		$simple_product_id  = $create_simple_product( 'Hamkharid E2E Simple ' . $run_id, array( $target_parent_id ) );
		$child_product_id   = $create_simple_product(
			'Hamkharid E2E Target Child Product ' . $run_id,
			array( $target_child_id )
		);
		$out_of_stock_id    = $create_simple_product(
			'Hamkharid E2E Out Of Stock ' . $run_id,
			array( $target_parent_id ),
			'outofstock'
		);
		$hidden_product_id  = $create_simple_product(
			'Hamkharid E2E Hidden ' . $run_id,
			array( $target_parent_id ),
			'instock',
			'hidden'
		);

		$variable_product = new WC_Product_Variable();
		$variable_product->set_name( 'Hamkharid E2E Variable ' . $run_id );
		$variable_product->set_status( 'publish' );
		$variable_product->set_category_ids( array( $target_parent_id ) );
		$variable_product->set_stock_status( 'instock' );
		$variable_product->set_catalog_visibility( 'visible' );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$variable_product->set_attributes( array( $attribute ) );
		$variable_product_id = (int) $variable_product->save();
		$mark_post( $variable_product_id );

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $variable_product_id );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( '39' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'size' => 'Small' ) );
		$variation_id = (int) $variation->save();
		$mark_post( $variation_id );
		WC_Product_Variable::sync( $variable_product_id );
		wc_delete_product_transients( $variable_product_id );

		$repository = new RuleRepository();
		$rule_id    = $repository->create(
			'Hamkharid E2E Base Rule ' . $run_id,
			true,
			array( $source_child_id ),
			$target_parent_id,
			3,
			Rule::SORT_NEWEST
		);

		if ( is_wp_error( $rule_id ) ) {
			throw new RuntimeException( $rule_id->get_error_message() );
		}

		$mark_post( (int) $rule_id );

		$shortcode_page_id = wp_insert_post(
			array(
				'post_title'   => 'Hamkharid E2E Shortcode Page ' . $run_id,
				'post_content' => '[hamkharid_complements product_id="' . $current_product_id . '"]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			),
			true
		);
		$block_page_id     = wp_insert_post(
			array(
				'post_title'   => 'Hamkharid E2E Block Page ' . $run_id,
				'post_content' => '<!-- wp:hamkharid/complements {"productId":' . $current_product_id . '} /-->',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			),
			true
		);

		if ( is_wp_error( $shortcode_page_id ) || is_wp_error( $block_page_id ) ) {
			throw new RuntimeException( 'Browser QA embed pages could not be created.' );
		}

		$mark_post( (int) $shortcode_page_id );
		$mark_post( (int) $block_page_id );

		$state = array(
			'run_id'         => $run_id,
			'locale'         => get_locale(),
			'theme_template' => get_stylesheet(),
			'admin'          => array(
				'username' => $admin_username,
				'password' => $admin_password,
			),
			'subscriber'     => array(
				'username' => $subscriber_username,
				'password' => $subscriber_password,
			),
			'ids'            => array(
				'source_parent'    => $source_parent_id,
				'source_child'     => $source_child_id,
				'target_parent'    => $target_parent_id,
				'target_child'     => $target_child_id,
				'admin_source'     => $admin_source_id,
				'current_product'  => $current_product_id,
				'simple_product'   => $simple_product_id,
				'child_product'    => $child_product_id,
				'variable_product' => $variable_product_id,
				'out_of_stock'     => $out_of_stock_id,
				'hidden_product'   => $hidden_product_id,
				'base_rule'        => (int) $rule_id,
				'shortcode_page'   => (int) $shortcode_page_id,
				'block_page'       => (int) $block_page_id,
			),
			'names'          => array(
				'simple_product' => get_the_title( $simple_product_id ),
			),
			'urls'           => array(
				'login'            => wp_login_url(),
				'rules'            => admin_url( 'edit.php?post_type=' . RulePostType::POST_TYPE ),
				'new_rule'         => admin_url( 'post-new.php?post_type=' . RulePostType::POST_TYPE ),
				'current_product'  => get_permalink( $current_product_id ),
				'shortcode_page'   => get_permalink( (int) $shortcode_page_id ),
				'block_page'       => get_permalink( (int) $block_page_id ),
				'settings'         => admin_url( 'admin.php?page=hamkharid-display-settings' ),
				'block_editor'     => admin_url( 'post.php?post=' . (int) $block_page_id . '&action=edit' ),
				'variable_product' => get_permalink( $variable_product_id ),
				'cart'             => wc_get_cart_url(),
			),
			'expected'       => array(
				'heading'        => 'fa_IR' === get_locale() ? 'خریدتان را کامل کنید' : 'Complete your purchase',
				'enabled_label'  => 'fa_IR' === get_locale() ? 'فعال' : 'Enabled',
				'disabled_label' => 'fa_IR' === get_locale() ? 'غیرفعال' : 'Disabled',
			),
		);

		$encoded = wp_json_encode( $state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $encoded ) {
			throw new RuntimeException( 'Browser QA fixture state could not be encoded.' );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Machine-readable QA JSON.
		echo $encoded;
	} catch ( Throwable $throwable ) {
		$delete_stale_fixtures();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, esc_html( $throwable->getMessage() ) . "\n" );
		exit( 1 );
	}
} )();
