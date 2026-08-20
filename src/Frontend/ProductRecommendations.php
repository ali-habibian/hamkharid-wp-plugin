<?php
/**
 * Single-product complement rendering.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Frontend;

use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Renders category-based complement recommendations on single product pages.
 */
final class ProductRecommendations {

	/**
	 * Rule matcher.
	 *
	 * @var RuleMatcher
	 */
	private $matcher;

	/**
	 * Recommendation engine.
	 *
	 * @var RecommendationEngine
	 */
	private $recommendation_engine;

	/**
	 * Whether this request has already attempted frontend rendering.
	 *
	 * @var bool
	 */
	private $render_attempted = false;

	/**
	 * Creates the frontend renderer.
	 *
	 * @param RuleMatcher          $matcher               Rule matcher.
	 * @param RecommendationEngine $recommendation_engine Recommendation engine.
	 */
	public function __construct( RuleMatcher $matcher, RecommendationEngine $recommendation_engine ) {
		$this->matcher               = $matcher;
		$this->recommendation_engine = $recommendation_engine;
	}

	/**
	 * Registers classic rendering plus a block-compatible fallback hook.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render' ), 18 );
		add_action( 'woocommerce_after_main_content', array( $this, 'render' ), 5 );
	}

	/**
	 * Renders complement products once on a matching single product page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( $this->render_attempted || ! is_product() ) {
			return;
		}

		$this->render_attempted = true;

		$current_product = $this->current_product();

		if ( ! $current_product instanceof WC_Product ) {
			return;
		}

		$rule = $this->matcher->match( $current_product->get_id() );

		if ( null === $rule ) {
			return;
		}

		$products = $this->recommendation_engine->recommend( $current_product->get_id(), $rule );

		if ( array() === $products ) {
			return;
		}

		$this->render_product_loop( $current_product->get_id(), $products );
	}

	/**
	 * Returns the WooCommerce product represented by the current single page.
	 */
	private function current_product(): ?WC_Product {
		global $product;

		if ( $product instanceof WC_Product ) {
			return $product;
		}

		$product_id = get_queried_object_id();

		if ( $product_id <= 0 ) {
			return null;
		}

		$resolved_product = wc_get_product( $product_id );

		return $resolved_product instanceof WC_Product ? $resolved_product : null;
	}

	/**
	 * Outputs recommendations using WooCommerce's standard product loop template.
	 *
	 * WooCommerce's native loop callbacks are left intact so each product type
	 * receives the standard catalog Add-to-Cart behavior.
	 *
	 * @param int          $current_product_id Current product ID.
	 * @param WC_Product[] $products           Recommended products.
	 * @return void
	 */
	private function render_product_loop( int $current_product_id, array $products ): void {
		$previous_post           = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$had_previous_post       = isset( $GLOBALS['post'] );
		$previous_product        = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		$had_previous_product    = isset( $GLOBALS['product'] );
		$previous_loop_name      = wc_get_loop_prop( 'name' );
		$previous_loop_columns   = wc_get_loop_prop( 'columns' );
		$heading_id              = 'hamkharid-complements-title-' . $current_product_id;
		$recommendation_columns  = min( 4, max( 1, count( $products ) ) );

		wc_set_loop_prop( 'name', 'hamkharid_complements' );
		wc_set_loop_prop( 'columns', $recommendation_columns );

		try {
			printf(
				'<section class="hamkharid-complements related products" aria-labelledby="%1$s"><h2 id="%1$s">%2$s</h2>',
				esc_attr( $heading_id ),
				esc_html__( 'You may also like', 'hamkharid' )
			);

			woocommerce_product_loop_start();

			foreach ( $products as $recommended_product ) {
				if ( ! $recommended_product instanceof WC_Product ) {
					continue;
				}

				$recommended_post = get_post( $recommended_product->get_id() );

				if ( ! $recommended_post instanceof \WP_Post ) {
					continue;
				}

				$GLOBALS['post']    = $recommended_post;
				$GLOBALS['product'] = $recommended_product;
				setup_postdata( $recommended_post );
				$GLOBALS['product'] = $recommended_product;

				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();
			echo '</section>';
		} finally {
			wc_set_loop_prop( 'name', $previous_loop_name );
			wc_set_loop_prop( 'columns', $previous_loop_columns );

			if ( $had_previous_post && $previous_post instanceof \WP_Post ) {
				$GLOBALS['post'] = $previous_post;
				setup_postdata( $previous_post );
			} elseif ( $had_previous_post ) {
				$GLOBALS['post'] = $previous_post;
			} else {
				unset( $GLOBALS['post'] );
			}

			if ( $had_previous_product ) {
				$GLOBALS['product'] = $previous_product;
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}
}
