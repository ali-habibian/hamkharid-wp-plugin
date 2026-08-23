<?php
/**
 * Lightweight WordPress integration test for bundled localization.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

( static function (): void {
	$wordpress_root = getenv( 'HAMKHARID_WP_ROOT' );

	if ( false === $wordpress_root || '' === $wordpress_root ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test failure output.
		fwrite( STDERR, "Set HAMKHARID_WP_ROOT to a WordPress installation before running this test.\n" );
		exit( 1 );
	}

	require rtrim( $wordpress_root, '/\\' ) . '/wp-load.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$plugin_data = get_plugin_data( HAMKHARID_FILE, false, false );

	if ( 'hamkharid' !== $plugin_data['TextDomain'] ) {
		throw new RuntimeException( 'The plugin text domain is not hamkharid.' );
	}

	if ( '/languages' !== $plugin_data['DomainPath'] ) {
		throw new RuntimeException( 'The plugin Domain Path is not /languages.' );
	}

	$po_file  = HAMKHARID_PATH . 'languages/hamkharid-fa_IR.po';
	$mo_file  = HAMKHARID_PATH . 'languages/hamkharid-fa_IR.mo';
	$pot_file = HAMKHARID_PATH . 'languages/hamkharid.pot';

	foreach ( array( $pot_file, $po_file, $mo_file ) as $translation_file ) {
		if ( ! is_readable( $translation_file ) ) {
			throw new RuntimeException( 'A bundled Hamkharid translation file is missing.' );
		}
	}

	unload_textdomain( 'hamkharid', true );

	if ( ! load_textdomain( 'hamkharid', $mo_file, 'fa_IR' ) ) {
		throw new RuntimeException( 'The Persian MO file could not be loaded.' );
	}

	if ( 'قوانین محصولات مکمل' !== __( 'Complement Rules', 'hamkharid' ) ) {
		throw new RuntimeException( 'The Complement Rules translation did not match its expected value.' );
	}

	if ( 'تنظیمات قانون' !== __( 'Rule Settings', 'hamkharid' ) ) {
		throw new RuntimeException( 'The Rule Settings translation did not match its expected value.' );
	}

	if ( 'پرفروش‌ترین' !== __( 'Best Selling', 'hamkharid' ) ) {
		throw new RuntimeException( 'The Best Selling translation did not match its expected value.' );
	}

	if ( 'شاید این محصولات هم برای شما مناسب باشند' !== __( 'You may also like', 'hamkharid' ) ) {
		throw new RuntimeException( 'The frontend heading translation did not match its expected value.' );
	}

	$translated_plugin_data = get_plugin_data( HAMKHARID_FILE, false, true );

	if ( 'مکمل هوشمند دسته‌بندی – هم‌خرید' !== $translated_plugin_data['Name'] ) {
		throw new RuntimeException( 'The plugin name was not localized in WordPress plugin metadata.' );
	}

	if ( 'نمایش محصولات مکمل ووکامرس بر اساس قوانین دسته‌بندی.' !== $translated_plugin_data['Description'] ) {
		throw new RuntimeException( 'The plugin description was not localized in WordPress plugin metadata.' );
	}

	$deleted_category = sprintf(
		/* translators: %d: Deleted WooCommerce product category ID. */
		__( 'Deleted category #%d', 'hamkharid' ),
		42
	);

	if ( 'دسته‌بندی حذف‌شده شماره 42' !== $deleted_category ) {
		throw new RuntimeException( 'A translated placeholder string did not preserve its placeholder.' );
	}

	unload_textdomain( 'hamkharid', true );

	$current_locale      = determine_locale();
	$available_languages = get_available_languages();
	$can_switch_to_fa    = 'fa_IR' === $current_locale || in_array( 'fa_IR', $available_languages, true );
	$switched_locale     = false;

	if ( $can_switch_to_fa && 'fa_IR' !== $current_locale ) {
		$switched_locale = switch_to_locale( 'fa_IR' );

		if ( ! $switched_locale ) {
			throw new RuntimeException( 'WordPress could not switch to the installed fa_IR locale.' );
		}
	}

	if ( $can_switch_to_fa ) {
		load_plugin_textdomain(
			'hamkharid',
			false,
			dirname( plugin_basename( HAMKHARID_FILE ) ) . '/languages'
		);

		if ( 'قوانین محصولات مکمل' !== __( 'Complement Rules', 'hamkharid' ) ) {
			throw new RuntimeException( 'Hamkharid did not follow the active WordPress fa_IR locale.' );
		}
	}

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	unload_textdomain( 'hamkharid', true );
	load_plugin_textdomain(
		'hamkharid',
		false,
		dirname( plugin_basename( HAMKHARID_FILE ) ) . '/languages'
	);

	echo "Localization integration test passed.\n";
} )();
