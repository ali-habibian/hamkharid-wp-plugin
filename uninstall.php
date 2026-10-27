<?php
/**
 * Uninstall cleanup for Smart Category Complement – Hamkharid.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$hamkharid_settings_option = 'hamkharid_display_settings_v1';
$hamkharid_settings        = get_option( $hamkharid_settings_option, array() );

if ( ! is_array( $hamkharid_settings ) || empty( $hamkharid_settings['clear_data_on_uninstall'] ) ) {
	return;
}

do {
	$hamkharid_deleted_count = 0;
	$hamkharid_rule_ids      = get_posts(
		array(
			'post_type'              => 'hamkharid_rule',
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'posts_per_page'         => 100,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $hamkharid_rule_ids as $hamkharid_rule_id ) {
		if ( wp_delete_post( (int) $hamkharid_rule_id, true ) instanceof WP_Post ) {
			++$hamkharid_deleted_count;
		}
	}
} while ( ! empty( $hamkharid_rule_ids ) && $hamkharid_deleted_count > 0 );

delete_option( $hamkharid_settings_option );
