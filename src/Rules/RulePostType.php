<?php
/**
 * Complement rule post type and metadata schema.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the private storage primitives used by complement rules.
 */
final class RulePostType {

	public const POST_TYPE                 = 'hamkharid_rule';
	public const META_SOURCE_CATEGORY      = '_hamkharid_source_category';
	public const META_TARGET_CATEGORY      = '_hamkharid_target_category';
	public const META_RECOMMENDATION_COUNT = '_hamkharid_recommendation_count';
	public const META_SORT_STRATEGY        = '_hamkharid_sort_strategy';

	/**
	 * Registers WordPress hooks for the rule schema.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ), 5 );
	}

	/**
	 * Registers the private post type and its metadata.
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Complement Rules', 'hamkharid' ),
					'singular_name'      => __( 'Complement Rule', 'hamkharid' ),
					'menu_name'          => __( 'Complement Rules', 'hamkharid' ),
					'all_items'          => __( 'Complement Rules', 'hamkharid' ),
					'add_new'            => __( 'Add New', 'hamkharid' ),
					'add_new_item'       => __( 'Add New Complement Rule', 'hamkharid' ),
					'edit_item'          => __( 'Edit Complement Rule', 'hamkharid' ),
					'new_item'           => __( 'New Complement Rule', 'hamkharid' ),
					'search_items'       => __( 'Search Complement Rules', 'hamkharid' ),
					'not_found'          => __( 'No complement rules found.', 'hamkharid' ),
					'not_found_in_trash' => __( 'No complement rules found in Trash.', 'hamkharid' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => 'woocommerce',
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
				'delete_with_user'    => false,
				'capabilities'        => $this->capabilities(),
				'map_meta_cap'        => false,
			)
		);

		$this->register_meta();
	}

	/**
	 * Returns the capabilities required to manage rules.
	 *
	 * @return array<string, string>
	 */
	private function capabilities(): array {
		return array(
			'edit_post'              => 'manage_woocommerce',
			'read_post'              => 'manage_woocommerce',
			'delete_post'            => 'manage_woocommerce',
			'edit_posts'             => 'manage_woocommerce',
			'edit_others_posts'      => 'manage_woocommerce',
			'publish_posts'          => 'manage_woocommerce',
			'read_private_posts'     => 'manage_woocommerce',
			'delete_posts'           => 'manage_woocommerce',
			'delete_private_posts'   => 'manage_woocommerce',
			'delete_published_posts' => 'manage_woocommerce',
			'delete_others_posts'    => 'manage_woocommerce',
			'edit_private_posts'     => 'manage_woocommerce',
			'edit_published_posts'   => 'manage_woocommerce',
			'create_posts'           => 'manage_woocommerce',
		);
	}

	/**
	 * Registers the rule metadata schema.
	 */
	private function register_meta(): void {
		$integer_meta_args = array(
			'object_subtype'    => self::POST_TYPE,
			'type'              => 'integer',
			'description'       => '',
			'single'            => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => array( $this, 'can_manage_rules' ),
			'show_in_rest'      => false,
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_SOURCE_CATEGORY,
			array_merge(
				$integer_meta_args,
				array(
					'description' => __( 'Source product category ID.', 'hamkharid' ),
					'single'      => false,
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_TARGET_CATEGORY,
			array_merge(
				$integer_meta_args,
				array(
					'description' => __( 'Target product category ID.', 'hamkharid' ),
					'default'     => 0,
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_RECOMMENDATION_COUNT,
			array_merge(
				$integer_meta_args,
				array(
					'description' => __( 'Number of products to recommend.', 'hamkharid' ),
					'default'     => Rule::DEFAULT_RECOMMENDATION_COUNT,
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_SORT_STRATEGY,
			array(
				'object_subtype'    => self::POST_TYPE,
				'type'              => 'string',
				'description'       => __( 'Recommendation sort strategy.', 'hamkharid' ),
				'single'            => true,
				'default'           => Rule::SORT_RANDOM,
				'sanitize_callback' => array( Rule::class, 'sanitize_sort_strategy' ),
				'auth_callback'     => array( $this, 'can_manage_rules' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Reports whether the current user may edit rule metadata.
	 */
	public function can_manage_rules(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
