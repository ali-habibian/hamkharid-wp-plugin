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
	 * @param int    $priority              Priority within a specificity tier.
	 * @return int|WP_Error
	 */
	public function create(
		string $name,
		bool $enabled,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count = Rule::DEFAULT_RECOMMENDATION_COUNT,
		string $sort_strategy = Rule::SORT_RANDOM,
		int $priority = Rule::DEFAULT_PRIORITY
	) {
		$values = $this->validate_values(
			$name,
			$source_category_ids,
			$target_category_id,
			$recommendation_count,
			$sort_strategy,
			$priority
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
	 * @param int    $priority               Priority within a specificity tier.
	 * @return int|WP_Error
	 */
	public function update(
		int $rule_id,
		string $name,
		bool $enabled,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count = Rule::DEFAULT_RECOMMENDATION_COUNT,
		string $sort_strategy = Rule::SORT_RANDOM,
		int $priority = Rule::DEFAULT_PRIORITY
	) {
		if ( ! $this->is_rule_post( $rule_id ) ) {
			return new WP_Error( 'hamkharid_rule_not_found', __( 'The complement rule does not exist.', 'hamkharid' ) );
		}

		$values = $this->validate_values(
			$name,
			$source_category_ids,
			$target_category_id,
			$recommendation_count,
			$sort_strategy,
			$priority
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
			(string) get_post_meta( $post->ID, RulePostType::META_SORT_STRATEGY, true ),
			Rule::sanitize_priority( get_post_meta( $post->ID, RulePostType::META_PRIORITY, true ) )
		);
	}

	/**
	 * Finds a page of enabled rules whose source includes any given category.
	 *
	 * Callers control category specificity by querying one priority tier at a time.
	 * Rules within the same tier use descending priority and ascending post ID.
	 *
	 * @param int[] $source_category_ids Product category IDs at the same specificity tier.
	 * @param int   $page                 One-based page number.
	 * @param int   $per_page             Page size, capped at 100.
	 * @return Rule[]
	 */
	public function find_enabled_matching_source_categories( array $source_category_ids, int $page = 1, int $per_page = 50 ): array {
		$source_category_ids = array_values( array_unique( array_filter( array_map( 'absint', $source_category_ids ) ) ) );

		if ( array() === $source_category_ids ) {
			return array();
		}

		$page     = max( 1, $page );
		$per_page = min( 100, max( 1, $per_page ) );
		add_filter( 'posts_clauses', array( $this, 'order_priority_query' ), 10, 2 );

		try {
			$query = new \WP_Query(
				array(
					'post_type'                => RulePostType::POST_TYPE,
					'post_status'              => 'publish',
					'fields'                   => 'ids',
					'posts_per_page'           => $per_page,
					'paged'                    => $page,
					'no_found_rows'            => true,
					'suppress_filters'         => false,
					'hamkharid_priority_order' => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Rule source categories are stored as repeated post meta.
					'meta_query'               => array(
						array(
							'key'     => RulePostType::META_SOURCE_CATEGORY,
							'value'   => $source_category_ids,
							'compare' => 'IN',
						),
					),
				)
			);
		} finally {
			remove_filter( 'posts_clauses', array( $this, 'order_priority_query' ), 10 );
		}

		$rule_ids = $query->posts;

		$rules = array();

		foreach ( $rule_ids as $rule_id ) {
			$rule = $this->find( (int) $rule_id );

			if ( null !== $rule ) {
				$rules[] = $rule;
			}
		}

		return $rules;
	}

	/**
	 * Finds the first matching rule, retained for existing integrations.
	 *
	 * @param int[] $source_category_ids Product category IDs at the same specificity tier.
	 */
	public function find_first_enabled_matching_source_categories( array $source_category_ids ): ?Rule {
		$rules = $this->find_enabled_matching_source_categories( $source_category_ids, 1, 1 );

		return isset( $rules[0] ) ? $rules[0] : null;
	}

	/**
	 * Orders only the paged Hamkharid rule query by priority and ID.
	 *
	 * Missing priority metadata is treated as zero. The correlated subquery
	 * avoids duplicate rows if malformed legacy metadata contains duplicate keys.
	 *
	 * @param array<string, string> $clauses SQL clauses.
	 * @param \WP_Query             $query   Query being filtered.
	 * @return array<string, string>
	 */
	public function order_priority_query( array $clauses, \WP_Query $query ): array {
		if ( ! $query->get( 'hamkharid_priority_order' ) ) {
			return $clauses;
		}

		global $wpdb;

		$priority_key       = esc_sql( RulePostType::META_PRIORITY );
		$clauses['orderby'] = "COALESCE((SELECT MAX(LEAST(100, GREATEST(0, CAST(priority_meta.meta_value AS SIGNED)))) FROM {$wpdb->postmeta} AS priority_meta WHERE priority_meta.post_id = {$wpdb->posts}.ID AND priority_meta.meta_key = '{$priority_key}'), 0) DESC, {$wpdb->posts}.ID ASC";

		return $clauses;
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
	 * @param int    $priority              Priority within a specificity tier.
	 * @return array<string, int|int[]|string>|WP_Error
	 */
	private function validate_values(
		string $name,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count,
		string $sort_strategy,
		int $priority
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

		if ( $recommendation_count < Rule::MIN_RECOMMENDATION_COUNT || $recommendation_count > Rule::MAX_RECOMMENDATION_COUNT ) {
			return new WP_Error( 'hamkharid_recommendation_count_invalid', __( 'Choose between one and six recommended products.', 'hamkharid' ) );
		}

		if ( $priority < Rule::MIN_PRIORITY || $priority > Rule::MAX_PRIORITY ) {
			return new WP_Error( 'hamkharid_priority_invalid', __( 'Priority must be between zero and one hundred.', 'hamkharid' ) );
		}

		if ( ! in_array( $sort_strategy, Rule::sort_strategies(), true ) ) {
			return new WP_Error( 'hamkharid_sort_strategy_invalid', __( 'Choose a supported sorting method.', 'hamkharid' ) );
		}

		return array(
			'name'                 => $name,
			'source_category_ids'  => $source_category_ids,
			'target_category_id'   => $target_category_id,
			'recommendation_count' => $recommendation_count,
			'sort_strategy'        => $sort_strategy,
			'priority'             => $priority,
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
		update_post_meta( $rule_id, RulePostType::META_PRIORITY, $values['priority'] );
	}
}
