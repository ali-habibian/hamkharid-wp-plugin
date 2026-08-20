<?php
/**
 * Complement rule matching.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Matching;

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the highest-priority enabled complement rule that applies to a product.
 */
final class RuleMatcher {

	/**
	 * Rule persistence gateway.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Creates the rule matcher.
	 *
	 * @param RuleRepository $repository Rule persistence gateway.
	 */
	public function __construct( RuleRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Returns the highest-priority enabled rule matching a product's categories.
	 *
	 * Direct product categories win over ancestors. Among ancestors, the nearest
	 * level wins. Rules at the same specificity use ascending post ID as the
	 * deterministic tie-breaker.
	 *
	 * @param int $product_id WooCommerce product post ID.
	 */
	public function match( int $product_id ): ?Rule {
		if ( 'product' !== get_post_type( $product_id ) ) {
			return null;
		}

		$category_ids = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $category_ids ) || array() === $category_ids ) {
			return null;
		}

		foreach ( $this->category_ids_by_specificity( $category_ids ) as $priority_category_ids ) {
			$rule = $this->repository->find_first_enabled_matching_source_categories( $priority_category_ids );

			if ( null === $rule ) {
				continue;
			}

			if ( ! term_exists( $rule->target_category_id(), 'product_cat' ) ) {
				return null;
			}

			return $rule;
		}

		return null;
	}

	/**
	 * Groups assigned categories and ancestors by specificity.
	 *
	 * The first group contains categories assigned directly to the product. Each
	 * following group contains ancestors one level farther away. A category that
	 * is reachable at multiple distances is kept only at its nearest distance.
	 *
	 * @param int[] $category_ids Assigned product category IDs.
	 * @return array<int, int[]>
	 */
	private function category_ids_by_specificity( array $category_ids ): array {
		$category_distances = array();

		foreach ( $category_ids as $category_id ) {
			$category_id = absint( $category_id );

			if ( 0 === $category_id ) {
				continue;
			}

			$this->store_nearest_distance( $category_distances, $category_id, 0 );

			foreach ( get_ancestors( $category_id, 'product_cat', 'taxonomy' ) as $ancestor_index => $ancestor_id ) {
				$this->store_nearest_distance( $category_distances, absint( $ancestor_id ), $ancestor_index + 1 );
			}
		}

		$grouped_category_ids = array();

		foreach ( $category_distances as $category_id => $distance ) {
			$grouped_category_ids[ $distance ][] = (int) $category_id;
		}

		ksort( $grouped_category_ids, SORT_NUMERIC );

		foreach ( $grouped_category_ids as &$group ) {
			sort( $group, SORT_NUMERIC );
		}
		unset( $group );

		return array_values( $grouped_category_ids );
	}

	/**
	 * Stores the nearest known hierarchy distance for a category.
	 *
	 * @param array<int, int> $category_distances Category IDs keyed to distance.
	 * @param int             $category_id        Product category ID.
	 * @param int             $distance           Distance from an assigned category.
	 */
	private function store_nearest_distance( array &$category_distances, int $category_id, int $distance ): void {
		if ( 0 === $category_id ) {
			return;
		}

		if ( ! isset( $category_distances[ $category_id ] ) || $distance < $category_distances[ $category_id ] ) {
			$category_distances[ $category_id ] = $distance;
		}
	}
}
