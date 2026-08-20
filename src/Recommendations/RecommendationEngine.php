<?php
/**
 * Complement product recommendations.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Recommendations;

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use WC_Product;
use WC_Product_Query;
use WC_Query;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves WooCommerce products recommended by a matched complement rule.
 */
final class RecommendationEngine {

	/**
	 * Minimum number of products considered by the random strategy.
	 */
	private const RANDOM_MINIMUM_CANDIDATE_POOL = 24;

	/**
	 * Candidate pool multiplier for the random strategy.
	 */
	private const RANDOM_CANDIDATE_MULTIPLIER = 4;

	/**
	 * Returns products recommended for the current product and matched rule.
	 *
	 * @param int  $current_product_id Current WooCommerce product post ID.
	 * @param Rule $rule               Matched complement rule.
	 * @return WC_Product[]
	 */
	public function recommend( int $current_product_id, Rule $rule ): array {
		if ( 'product' !== get_post_type( $current_product_id ) || ! $rule->is_enabled() ) {
			return array();
		}

		$target_category = get_term( $rule->target_category_id(), 'product_cat' );

		if ( ! $target_category instanceof WP_Term ) {
			return array();
		}

		$base_args = $this->base_query_args( $current_product_id, $target_category->slug );

		switch ( $rule->sort_strategy() ) {
			case Rule::SORT_NEWEST:
				$products = $this->query_products(
					array_merge(
						$base_args,
						array(
							'limit'   => $rule->recommendation_count(),
							'orderby' => 'date',
							'order'   => 'DESC',
						)
					)
				);
				break;

			case Rule::SORT_BEST_SELLING:
				$products = $this->best_selling_products( $base_args, $rule->recommendation_count() );
				break;

			case Rule::SORT_RANDOM:
			default:
				$products = $this->random_products( $base_args, $rule->recommendation_count() );
				break;
		}

		return array_values(
			array_filter(
				$products,
				static function ( $product ): bool {
					return $product instanceof WC_Product && $product->is_in_stock();
				}
			)
		);
	}

	/**
	 * Builds query arguments shared by every sort strategy.
	 *
	 * WooCommerce's category query uses the hierarchical product_cat taxonomy and
	 * therefore includes products assigned to descendant categories.
	 *
	 * @param int    $current_product_id Current product post ID.
	 * @param string $target_category_slug Target product category slug.
	 * @return array<string, mixed>
	 */
	private function base_query_args( int $current_product_id, string $target_category_slug ): array {
		return array(
			'status'       => 'publish',
			'category'     => array( $target_category_slug ),
			'exclude'      => array( $current_product_id ),
			'visibility'   => 'catalog',
			'stock_status' => array( 'instock', 'onbackorder' ),
			'return'       => 'objects',
		);
	}

	/**
	 * Queries products ordered by WooCommerce's native popularity metric.
	 *
	 * WooCommerce implements catalog popularity ordering through its product
	 * lookup table. The temporary ordering filter is always removed immediately
	 * after this isolated query.
	 *
	 * @param array<string, mixed> $base_args Shared product query arguments.
	 * @param int                  $limit     Maximum number of products.
	 * @return WC_Product[]
	 */
	private function best_selling_products( array $base_args, int $limit ): array {
		$woocommerce = \WC();

		if ( ! isset( $woocommerce->query ) || ! $woocommerce->query instanceof WC_Query ) {
			return array();
		}

		$ordering = $woocommerce->query->get_catalog_ordering_args( 'popularity', 'DESC' );
		$args     = array_merge(
			$base_args,
			array(
				'limit'   => $limit,
				'orderby' => $ordering['orderby'],
				'order'   => $ordering['order'],
			)
		);

		if ( ! empty( $ordering['meta_key'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Value is supplied by WooCommerce's own catalog ordering API.
			$args['meta_key'] = $ordering['meta_key'];
		}

		try {
			return $this->query_products( $args );
		} finally {
			remove_filter( 'posts_clauses', array( $woocommerce->query, 'order_by_popularity_post_clauses' ) );
		}
	}

	/**
	 * Selects a small random window without database-level ORDER BY RAND().
	 *
	 * A paginated count query determines the eligible population. A circular
	 * window starting at a random offset is then fetched in one or two bounded
	 * queries and shuffled in PHP. This keeps random selection bounded while
	 * giving every eligible product an equal chance to enter the candidate pool.
	 *
	 * @param array<string, mixed> $base_args Shared product query arguments.
	 * @param int                  $limit     Maximum number of products.
	 * @return WC_Product[]
	 */
	private function random_products( array $base_args, int $limit ): array {
		$count_result = $this->query(
			array_merge(
				$base_args,
				array(
					'limit'    => 1,
					'paginate' => true,
					'return'   => 'ids',
					'orderby'  => 'ID',
					'order'    => 'ASC',
				)
			)
		);

		if ( ! is_object( $count_result ) || ! isset( $count_result->total ) ) {
			return array();
		}

		$total = max( 0, (int) $count_result->total );

		if ( 0 === $total ) {
			return array();
		}

		$pool_size = min(
			$total,
			max( self::RANDOM_MINIMUM_CANDIDATE_POOL, $limit * self::RANDOM_CANDIDATE_MULTIPLIER )
		);
		$start      = wp_rand( 0, $total - 1 );
		$first_size = min( $pool_size, $total - $start );

		$candidates = $this->query_products(
			array_merge(
				$base_args,
				array(
					'limit'   => $first_size,
					'offset'  => $start,
					'orderby' => 'ID',
					'order'   => 'ASC',
				)
			)
		);

		$remaining = $pool_size - count( $candidates );

		if ( $remaining > 0 ) {
			$candidates = array_merge(
				$candidates,
				$this->query_products(
					array_merge(
						$base_args,
						array(
							'limit'   => $remaining,
							'offset'  => 0,
							'orderby' => 'ID',
							'order'   => 'ASC',
						)
					)
				)
			);
		}

		shuffle( $candidates );

		return array_slice( $candidates, 0, $limit );
	}

	/**
	 * Executes a WooCommerce product query and returns product objects only.
	 *
	 * @param array<string, mixed> $args Product query arguments.
	 * @return WC_Product[]
	 */
	private function query_products( array $args ): array {
		$products = $this->query( $args );

		if ( ! is_array( $products ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$products,
				static function ( $product ): bool {
					return $product instanceof WC_Product;
				}
			)
		);
	}

	/**
	 * Executes a WC_Product_Query.
	 *
	 * @param array<string, mixed> $args Product query arguments.
	 * @return array|object
	 */
	private function query( array $args ) {
		$query = new WC_Product_Query( $args );

		return $query->get_products();
	}
}
