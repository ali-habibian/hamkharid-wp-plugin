<?php
/**
 * Complement product recommendations.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Recommendations;

use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;
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
	 * Maximum number of products loaded for one candidate page.
	 */
	private const CANDIDATE_PAGE_SIZE = 24;

	/** Maximum candidates inspected for one recommendation result. */
	private const MAX_CANDIDATE_SCAN = 500;

	/** Maximum variation candidates inspected across one recommendation result. */
	private const MAX_VARIATION_SCAN = 500;

	/**
	 * Number of variations inspected during the current recommendation request.
	 *
	 * @var int
	 */
	private $variation_candidates_scanned = 0;

	/**
	 * Whether the variation safety limit excluded further candidates this request.
	 *
	 * @var bool
	 */
	private $variation_scan_limit_reached = false;

	/**
	 * Returns products recommended for the current product and matched rule.
	 *
	 * @param int  $current_product_id Current WooCommerce product post ID.
	 * @param Rule $rule               Matched complement rule.
	 * @return WC_Product[]
	 */
	public function recommend( int $current_product_id, Rule $rule ): array {
		$this->variation_candidates_scanned = 0;
		$this->variation_scan_limit_reached = false;

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
				$products = $this->ordered_eligible_products(
					$base_args,
					$rule->recommendation_count(),
					array(
						'orderby' => 'date',
						'order'   => 'DESC',
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

		return array_slice( $products, 0, $rule->recommendation_count() );
	}

	/**
	 * Returns diagnostics from the most recent recommendation request.
	 *
	 * @return array<string, bool|int>
	 */
	public function diagnostics(): array {
		return array(
			'variation_candidates_scanned' => $this->variation_candidates_scanned,
			'variation_scan_limit_reached' => $this->variation_scan_limit_reached,
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
		$allow_backorders = $this->allow_backorders();

		return array(
			'status'       => 'publish',
			'type'         => array( 'simple', 'variable' ),
			'category'     => array( $target_category_slug ),
			'exclude'      => array( $current_product_id ),
			'visibility'   => 'catalog',
			'stock_status' => $allow_backorders ? array( 'instock', 'onbackorder' ) : array( 'instock' ),
			'return'       => 'objects',
		);
	}

	/**
	 * Reads the versioned site option used by the settings screen.
	 *
	 * Until that screen exists, missing or malformed settings keep the safe default.
	 */
	private function allow_backorders(): bool {
		$settings = DisplaySettings::get();

		return ! empty( $settings['allow_backorders'] );
	}

	/**
	 * Finds up to the requested number of eligible products in the requested order.
	 *
	 * Each query is capped at one page. Pages are fetched only until the requested
	 * number is reached or WooCommerce reports that the candidate set is exhausted.
	 *
	 * @param array<string, mixed> $base_args Shared product query arguments.
	 * @param int                  $limit     Maximum number of products.
	 * @param array<string, mixed> $ordering  WooCommerce query ordering arguments.
	 * @return WC_Product[]
	 */
	private function ordered_eligible_products( array $base_args, int $limit, array $ordering ): array {
		return $this->eligible_products_from_pages( $base_args, $ordering, self::MAX_CANDIDATE_SCAN, 0, $limit );
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
		$args     = array(
			'orderby' => $ordering['orderby'],
			'order'   => $ordering['order'],
		);

		if ( ! empty( $ordering['meta_key'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Value is supplied by WooCommerce's own catalog ordering API.
			$args['meta_key'] = $ordering['meta_key'];
		}

		try {
			return $this->ordered_eligible_products( $base_args, $limit, $args );
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
		$ordering = array(
			'orderby' => 'ID',
			'order'   => 'ASC',
		);
		$total    = $this->query_total( array_merge( $base_args, $ordering ) );

		if ( 0 === $total ) {
			return array();
		}

		$start           = wp_rand( 0, $total - 1 );
		$scanned_forward = 0;
		$candidates      = $this->eligible_products_from_pages( $base_args, $ordering, $total, $start, $limit, self::MAX_CANDIDATE_SCAN, $scanned_forward );

		if ( count( $candidates ) < $limit && $start > 0 ) {
			$scanned_wrap = 0;
			$candidates   = array_merge(
				$candidates,
				$this->eligible_products_from_pages( $base_args, $ordering, $start, 0, $limit - count( $candidates ), self::MAX_CANDIDATE_SCAN - $scanned_forward, $scanned_wrap )
			);
		}

		shuffle( $candidates );

		return array_slice( $candidates, 0, $limit );
	}

	/**
	 * Returns eligible products from a bounded page sequence.
	 *
	 * @param array<string, mixed> $base_args Shared query arguments.
	 * @param array<string, mixed> $ordering  Query ordering arguments.
	 * @param int                  $total     Number of matching candidates.
	 * @param int                  $offset    First candidate offset.
	 * @param int                  $limit     Number of eligible products wanted.
	 * @param int                  $candidate_budget Maximum candidates to inspect in this segment.
	 * @param int                  $scanned   Number of candidates inspected in this segment.
	 * @return WC_Product[]
	 */
	private function eligible_products_from_pages( array $base_args, array $ordering, int $total, int $offset, int $limit, int $candidate_budget = self::MAX_CANDIDATE_SCAN, ?int &$scanned = null ): array {
		$products = array();
		$end      = min( $total, $offset + max( 0, $candidate_budget ) );
		$count    = 0;
		$scanned  = 0;

		while ( $offset < $end && $count < $limit ) {
			$page_size = min( self::CANDIDATE_PAGE_SIZE, $end - $offset );
			$page      = $this->query_products(
				array_merge(
					$base_args,
					$ordering,
					array(
						'limit'  => $page_size,
						'offset' => $offset,
					)
				)
			);

			if ( array() === $page ) {
				break;
			}
			$scanned += count( $page );

			foreach ( $page as $product ) {
				if ( $this->is_eligible_product( $product ) ) {
					$products[] = $product;
					++$count;
				}
			}

			$offset += $page_size;
		}

		return $products;
	}

	/**
	 * Counts matching products without loading the entire candidate set.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 */
	private function query_total( array $args ): int {
		$count = $this->query(
			array_merge(
				$args,
				array(
					'limit'    => 1,
					'paginate' => true,
					'return'   => 'ids',
				)
			)
		);

		return is_object( $count ) && isset( $count->total ) ? max( 0, (int) $count->total ) : 0;
	}

	/**
	 * Checks product and, for variable products, variation eligibility.
	 *
	 * @param mixed $product Candidate product.
	 * @return bool
	 */
	private function is_eligible_product( $product ): bool {
		if ( ! $product instanceof WC_Product || ! in_array( $product->get_type(), array( 'simple', 'variable' ), true ) ) {
			return false;
		}

		if ( ! $product->is_purchasable() || ! $this->stock_is_allowed( $product ) ) {
			return false;
		}

		if ( 'variable' !== $product->get_type() ) {
			return true;
		}

		return $this->has_eligible_variation( $product->get_id() );
	}

	/**
	 * Reports whether a product is in stock under the site backorder setting.
	 *
	 * @param WC_Product $product Product or variation to check.
	 */
	private function stock_is_allowed( WC_Product $product ): bool {
		if ( ! $product->is_in_stock() ) {
			return false;
		}

		if ( 'onbackorder' === $product->get_stock_status() ) {
			return $this->allow_backorders() && $product->backorders_allowed();
		}

		return true;
	}

	/**
	 * Checks variation candidates in pages within the request-wide scan budget.
	 *
	 * @param int $product_id Variable product ID.
	 */
	private function has_eligible_variation( int $product_id ): bool {
		if ( $this->variation_scan_limit_reached ) {
			return false;
		}

		$stock_status = $this->allow_backorders() ? array( 'instock', 'onbackorder' ) : array( 'instock' );
		$args         = array(
			'parent'       => $product_id,
			'type'         => 'variation',
			'status'       => 'publish',
			'stock_status' => $stock_status,
			'limit'        => self::CANDIDATE_PAGE_SIZE,
			'orderby'      => 'ID',
			'order'        => 'ASC',
			'return'       => 'objects',
		);
		$offset       = 0;

		while ( $this->variation_candidates_scanned < self::MAX_VARIATION_SCAN ) {
			$remaining  = self::MAX_VARIATION_SCAN - $this->variation_candidates_scanned;
			$page_size  = min( self::CANDIDATE_PAGE_SIZE, $remaining );
			$variations = $this->query_products(
				array_merge(
					$args,
					array(
						'offset' => $offset,
						'limit'  => $page_size,
					)
				)
			);

			if ( array() === $variations ) {
				return false;
			}

			$scanned                             = count( $variations );
			$this->variation_candidates_scanned += $scanned;
			$offset                             += $scanned;

			foreach ( $variations as $variation ) {
				if ( 'variation' === $variation->get_type() && $variation->is_purchasable() && $this->stock_is_allowed( $variation ) ) {
					return true;
				}
			}

			if ( $scanned < $page_size ) {
				return false;
			}
		}

		$remaining_variations = $this->query(
			array_merge(
				$args,
				array(
					'offset' => $offset,
					'limit'  => 1,
					'return' => 'ids',
				)
			)
		);

		$this->variation_scan_limit_reached = $this->variation_scan_limit_reached || ( is_array( $remaining_variations ) && array() !== $remaining_variations );

		return false;
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
