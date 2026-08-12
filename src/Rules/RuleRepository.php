<?php
/**
 * Complement rule persistence.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Rules;

use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Persists and retrieves rules through WordPress post APIs.
 */
final class RuleRepository {

	/**
	 * Creates a complement rule.
	 *
	 * @param string $name                  Rule name.
	 * @param bool   $enabled               Whether the rule is enabled.
	 * @param int[]  $source_category_ids   Source product category IDs.
	 * @param int    $target_category_id    Target product category ID.
	 * @param int    $recommendation_count  Number of recommendations.
	 * @param string $sort_strategy         Recommendation sort strategy.
	 * @return int|WP_Error
	 */
	public function create(
		string $name,
		bool $enabled,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count = Rule::DEFAULT_RECOMMENDATION_COUNT,
		string $sort_strategy = Rule::SORT_RANDOM
	) {
		$values = $this->validate_values(
			$name,
			$source_category_ids,
			$target_category_id,
			$recommendation_count,
			$sort_strategy
		);

		if ( is_wp_error( $values ) ) {
			return $values;
		}

		$rule_id = wp_insert_post(
			array(
				'post_type'   => RulePostType::POST_TYPE,
				'post_title'  => $values['name'],
				'post_status' => $enabled ? 'publish' : 'draft',
			),
			true
		);

		if ( is_wp_error( $rule_id ) ) {
			return $rule_id;
		}

		$this->store_meta( $rule_id, $values );

		return $rule_id;
	}

	/**
	 * Updates an existing complement rule.
	 *
	 * @param int    $rule_id                Rule post ID.
	 * @param string $name                   Rule name.
	 * @param bool   $enabled                Whether the rule is enabled.
	 * @param int[]  $source_category_ids    Source product category IDs.
	 * @param int    $target_category_id     Target product category ID.
	 * @param int    $recommendation_count   Number of recommendations.
	 * @param string $sort_strategy          Recommendation sort strategy.
	 * @return int|WP_Error
	 */
	public function update(
		int $rule_id,
		string $name,
		bool $enabled,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count = Rule::DEFAULT_RECOMMENDATION_COUNT,
		string $sort_strategy = Rule::SORT_RANDOM
	) {
		if ( ! $this->is_rule_post( $rule_id ) ) {
			return new WP_Error( 'hamkharid_rule_not_found', __( 'The complement rule does not exist.', 'hamkharid' ) );
		}

		$values = $this->validate_values(
			$name,
			$source_category_ids,
			$target_category_id,
			$recommendation_count,
			$sort_strategy
		);

		if ( is_wp_error( $values ) ) {
			return $values;
		}

		$result = wp_update_post(
			array(
				'ID'          => $rule_id,
				'post_title'  => $values['name'],
				'post_status' => $enabled ? 'publish' : 'draft',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->store_meta( $rule_id, $values );

		return $rule_id;
	}

	/**
	 * Finds a complement rule by post ID.
	 *
	 * @param int $rule_id Rule post ID.
	 */
	public function find( int $rule_id ): ?Rule {
		$post = get_post( $rule_id );

		if ( ! $post instanceof WP_Post || RulePostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return new Rule(
			$post->ID,
			$post->post_title,
			'publish' === $post->post_status,
			get_post_meta( $post->ID, RulePostType::META_SOURCE_CATEGORY, false ),
			(int) get_post_meta( $post->ID, RulePostType::META_TARGET_CATEGORY, true ),
			(int) get_post_meta( $post->ID, RulePostType::META_RECOMMENDATION_COUNT, true ),
			(string) get_post_meta( $post->ID, RulePostType::META_SORT_STRATEGY, true )
		);
	}

	/**
	 * Deletes or trashes a complement rule.
	 *
	 * @param int  $rule_id Rule post ID.
	 * @param bool $force   Whether to bypass the Trash.
	 * @return WP_Post|false|null
	 */
	public function delete( int $rule_id, bool $force = false ) {
		if ( ! $this->is_rule_post( $rule_id ) ) {
			return null;
		}

		return wp_delete_post( $rule_id, $force );
	}

	/**
	 * Reports whether a post ID belongs to the rule post type.
	 *
	 * @param int $rule_id Post ID.
	 */
	private function is_rule_post( int $rule_id ): bool {
		return RulePostType::POST_TYPE === get_post_type( $rule_id );
	}

	/**
	 * Validates and normalizes values before persistence.
	 *
	 * @param string $name                  Rule name.
	 * @param int[]  $source_category_ids   Source category IDs.
	 * @param int    $target_category_id    Target category ID.
	 * @param int    $recommendation_count  Recommendation count.
	 * @param string $sort_strategy         Sort strategy.
	 * @return array<string, int|int[]|string>|WP_Error
	 */
	private function validate_values(
		string $name,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count,
		string $sort_strategy
	) {
		$name = sanitize_text_field( $name );

		if ( '' === $name ) {
			return new WP_Error( 'hamkharid_rule_name_required', __( 'A rule name is required.', 'hamkharid' ) );
		}

		$source_category_ids = $this->existing_category_ids( $source_category_ids );

		if ( array() === $source_category_ids ) {
			return new WP_Error( 'hamkharid_source_category_required', __( 'At least one valid source category is required.', 'hamkharid' ) );
		}

		if ( ! $this->category_exists( $target_category_id ) ) {
			return new WP_Error( 'hamkharid_target_category_required', __( 'A valid target category is required.', 'hamkharid' ) );
		}

		return array(
			'name'                 => $name,
			'source_category_ids'  => $source_category_ids,
			'target_category_id'   => $target_category_id,
			'recommendation_count' => Rule::normalize_recommendation_count( $recommendation_count ),
			'sort_strategy'        => Rule::normalize_sort_strategy( $sort_strategy ),
		);
	}

	/**
	 * Keeps unique category IDs that currently exist.
	 *
	 * @param int[] $category_ids Category IDs.
	 * @return int[]
	 */
	private function existing_category_ids( array $category_ids ): array {
		$category_ids = array_map( 'absint', $category_ids );
		$category_ids = array_values( array_unique( array_filter( $category_ids ) ) );

		return array_values( array_filter( $category_ids, array( $this, 'category_exists' ) ) );
	}

	/**
	 * Reports whether a product category exists.
	 *
	 * @param int $category_id Product category ID.
	 */
	private function category_exists( int $category_id ): bool {
		return $category_id > 0 && null !== term_exists( $category_id, 'product_cat' );
	}

	/**
	 * Replaces the stored metadata for a rule.
	 *
	 * @param int                             $rule_id Rule post ID.
	 * @param array<string, int|int[]|string> $values  Validated rule values.
	 */
	private function store_meta( int $rule_id, array $values ): void {
		delete_post_meta( $rule_id, RulePostType::META_SOURCE_CATEGORY );

		foreach ( $values['source_category_ids'] as $source_category_id ) {
			add_post_meta( $rule_id, RulePostType::META_SOURCE_CATEGORY, $source_category_id, false );
		}

		update_post_meta( $rule_id, RulePostType::META_TARGET_CATEGORY, $values['target_category_id'] );
		update_post_meta( $rule_id, RulePostType::META_RECOMMENDATION_COUNT, $values['recommendation_count'] );
		update_post_meta( $rule_id, RulePostType::META_SORT_STRATEGY, $values['sort_strategy'] );
	}
}
