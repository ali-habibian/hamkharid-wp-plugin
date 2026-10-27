<?php
/**
 * Verifies the default-preserve and opt-in Hamkharid uninstall lifecycle.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;

( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to the local WordPress QA installation before running this test.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';

	if ( ! class_exists( 'WC_Product_Simple' ) || ! post_type_exists( RulePostType::POST_TYPE ) ) {
		throw new RuntimeException( 'WooCommerce and the Hamkharid rule post type must be active.' );
	}

	$existing_rule_ids = get_posts(
		array(
			'post_type'      => RulePostType::POST_TYPE,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	if ( array() !== $existing_rule_ids ) {
		throw new RuntimeException( 'Uninstall lifecycle test stopped to protect existing rules; remove QA fixtures and retry.' );
	}

	$original_settings = get_option( DisplaySettings::OPTION_NAME, false );
	$created_terms     = array();
	$created_product   = 0;
	$created_rule      = 0;
	$created_order     = 0;
	$rule_repository   = new RuleRepository();
	$target            = null;

	try {
		$source_result = wp_insert_term( 'Hamkharid Uninstall QA Source ' . wp_generate_uuid4(), 'product_cat' );
		$target_result = wp_insert_term( 'Hamkharid Uninstall QA Target ' . wp_generate_uuid4(), 'product_cat' );

		foreach ( array( $source_result, $target_result ) as $term_result ) {
			if ( is_wp_error( $term_result ) ) {
				throw new RuntimeException( $term_result->get_error_message() );
			}

			$created_terms[] = (int) $term_result['term_id'];
		}

		$target = new \WC_Product_Simple();
		$target->set_name( 'Hamkharid Uninstall QA Product ' . wp_generate_uuid4() );
		$target->set_status( 'publish' );
		$target->set_regular_price( '10' );
		$target->set_category_ids( array( $created_terms[1] ) );
		$target->set_stock_status( 'instock' );
		$created_product = (int) $target->save();
		$order           = wc_create_order();

		if ( is_wp_error( $order ) ) {
			throw new RuntimeException( $order->get_error_message() );
		}

		$created_order = (int) $order->get_id();

		$created_rule = $rule_repository->create(
			'Hamkharid Uninstall QA Rule ' . wp_generate_uuid4(),
			false,
			array( $created_terms[0] ),
			$created_terms[1]
		);

		if ( is_wp_error( $created_rule ) ) {
			throw new RuntimeException( $created_rule->get_error_message() );
		}

		$settings = array(
			'schema_version'          => DisplaySettings::SCHEMA_VERSION,
			'mode'                    => 'manual',
			'placement'               => 'after_summary',
			'title_fa'                => '',
			'title_en'                => '',
			'allow_backorders'        => false,
			'clear_data_on_uninstall' => false,
		);
		update_option( DisplaySettings::OPTION_NAME, $settings, false );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core requires this uninstall sentinel.
		define( 'WP_UNINSTALL_PLUGIN', 'hamkharid/hamkharid.php' );

		include dirname( __DIR__, 1 ) . '/uninstall.php';

		$preserved_rule     = get_post( (int) $created_rule );
		$preserved_settings = get_option( DisplaySettings::OPTION_NAME );

		if ( ! $preserved_rule instanceof \WP_Post || $settings !== $preserved_settings ) {
			throw new RuntimeException( 'Uninstall removed rules or settings while opt-in cleanup was disabled.' );
		}

		$settings['clear_data_on_uninstall'] = true;
		update_option( DisplaySettings::OPTION_NAME, $settings, false );
		include dirname( __DIR__, 1 ) . '/uninstall.php';

		if ( null !== get_post( (int) $created_rule ) || false !== get_option( DisplaySettings::OPTION_NAME, false ) ) {
			throw new RuntimeException( 'Opt-in uninstall cleanup did not remove the test rule and settings.' );
		}

		$preserved_product = get_post( $created_product );
		$preserved_order   = wc_get_order( $created_order );

		if ( ! $preserved_product instanceof \WP_Post || ! term_exists( $created_terms[0], 'product_cat' ) || ! term_exists( $created_terms[1], 'product_cat' ) || ! $preserved_order instanceof \WC_Order ) {
			throw new RuntimeException( 'Opt-in uninstall cleanup changed a product, category or order.' );
		}

		echo "Uninstall lifecycle integration test passed.\n";
	} finally {
		if ( $created_rule > 0 && get_post( (int) $created_rule ) instanceof \WP_Post ) {
			wp_delete_post( (int) $created_rule, true );
		}

		if ( $created_product > 0 && get_post( $created_product ) instanceof \WP_Post ) {
			wp_delete_post( $created_product, true );
		}

		if ( $created_order > 0 ) {
			$order = wc_get_order( $created_order );

			if ( $order instanceof \WC_Order ) {
				$order->delete( true );
			}
		}

		foreach ( array_reverse( $created_terms ) as $term_id ) {
			if ( term_exists( $term_id, 'product_cat' ) ) {
				wp_delete_term( $term_id, 'product_cat' );
			}
		}

		if ( false === $original_settings ) {
			delete_option( DisplaySettings::OPTION_NAME );
		} else {
			update_option( DisplaySettings::OPTION_NAME, $original_settings, false );
		}
	}
} )();
