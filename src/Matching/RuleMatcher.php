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
	 * Maximum candidates inspected within one specificity tier.
	 */
	private const MAX_CANDIDATES_PER_TIER = 500;

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
		$result = $this->match_with_diagnostics( $product_id );

		return $result['rule'];
	}

	/**
	 * Resolves a rule and describes why no rule could be selected.
	 *
	 * @param int $product_id WooCommerce product post ID.
	 * @return array{rule:?Rule,reason:string,distance:?int}
	 */
	public function match_with_diagnostics( int $product_id ): array {
		if ( 'product' !== get_post_type( $product_id ) ) {
			return array(
				'rule'     => null,
				'reason'   => 'invalid_product',
				'distance' => null,
			);
		}

		$category_ids = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $category_ids ) || array() === $category_ids ) {
			return array(
				'rule'     => null,
				'reason'   => 'no_match',
				'distance' => null,
			);
		}

		$invalid_target_seen = false;
		$distance            = 0;

		foreach ( $this->category_ids_by_specificity( $category_ids ) as $specificity_category_ids ) {
			$page = 1;
			$seen = 0;

			while ( $seen < self::MAX_CANDIDATES_PER_TIER ) {
				$page_size = min( 50, self::MAX_CANDIDATES_PER_TIER - $seen );
				$rules     = $this->repository->find_enabled_matching_source_categories( $specificity_category_ids, $page, $page_size );

				if ( array() === $rules ) {
					break;
				}

				foreach ( $rules as $rule ) {
					if ( term_exists( $rule->target_category_id(), 'product_cat' ) ) {
						return array(
							'rule'     => $rule,
							'reason'   => 'matched',
							'distance' => $distance,
						);
					}

					$invalid_target_seen = true;
				}

				$seen += count( $rules );

				if ( count( $rules ) < $page_size ) {
					break;
				}

				++$page;
			}

			if ( $seen >= self::MAX_CANDIDATES_PER_TIER && count( $rules ) === $page_size ) {
				$remaining = $this->repository->find_enabled_matching_source_categories( $specificity_category_ids, $page, 1 );

				if ( array() !== $remaining ) {
					return array(
						'rule'     => null,
						'reason'   => 'candidate_limit_reached',
						'distance' => $distance,
					);
				}
			}

			++$distance;
		}

		return array(
			'rule'     => null,
			'reason'   => $invalid_target_seen ? 'invalid_target' : 'no_match',
			'distance' => null,
		);
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
