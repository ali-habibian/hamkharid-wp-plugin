<?php
/**
 * Product complement rendering and placement adapters.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Frontend;

use Hamkharid\SmartCategoryComplement\Admin\DisplaySettings;
use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use WC_Product;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Renders complement recommendations through automatic, shortcode and block adapters.
 */
final class ProductRecommendations {

	private const BLOCK_NAME = 'hamkharid/complements';

	/** Rule matcher.
	 *
	 * @var RuleMatcher
	 */
	private $matcher;

	/** Product recommendation resolver.
	 *
	 * @var RecommendationEngine
	 */
	private $recommendation_engine;

	/** Product IDs already rendered this request.
	 *
	 * @var array<int, bool>
	 */
	private $rendered_product_ids = array();

	/**
	 * Creates the shared renderer.
	 *
	 * @param RuleMatcher          $matcher               Rule matcher.
	 * @param RecommendationEngine $recommendation_engine  Recommendation engine.
	 */
	public function __construct( RuleMatcher $matcher, RecommendationEngine $recommendation_engine ) {
		$this->matcher               = $matcher;
		$this->recommendation_engine = $recommendation_engine;
	}

	/** Registers all placement adapters and the shared block editor assets. */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'init', array( $this, 'register_dynamic_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_product_search_route' ) );
		add_shortcode( 'hamkharid_complements', array( $this, 'render_shortcode' ) );

		$settings = DisplaySettings::get();

		if ( 'automatic' !== $settings['mode'] ) {
			return;
		}

		if ( 'after_add_to_cart' === $settings['placement'] ) {
			add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render' ), 20 );
			return;
		}

		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render' ), 18 );
		add_action( 'woocommerce_after_main_content', array( $this, 'render' ), 5 );
	}

	/** Enqueues the stylesheet on product pages and when an embed renders elsewhere. */
	public function enqueue_styles(): void {
		if ( ! is_admin() && ( is_product() || $this->current_post_contains_embed() ) ) {
			$this->enqueue_stylesheet();
		}
	}

	/** Renders one automatic placement for the current product. */
	public function render(): void {
		echo $this->render_for_product( $this->current_product_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes all dynamic fields.
	}

	/**
	 * Shortcode adapter; `product_id` allows use on ordinary pages, widgets and builders.
	 *
	 * @param array<string, string> $attributes Shortcode attributes.
	 */
	public function render_shortcode( array $attributes = array() ): string {
		$attributes = shortcode_atts( array( 'product_id' => '' ), $attributes, 'hamkharid_complements' );
		$product_id = absint( $attributes['product_id'] );

		return $this->render_for_product( $product_id > 0 ? $product_id : $this->current_product_id() );
	}

	/**
	 * Dynamic block adapter. Context supplies the product in single-product templates.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Saved inner content.
	 * @param mixed                $block      Parsed block.
	 */
	public function render_block( array $attributes, string $content = '', $block = null ): string {
		$product_id = absint( $attributes['productId'] ?? 0 );

		if ( 0 === $product_id && is_object( $block ) && isset( $block->context['postId'] ) ) {
			$context_product_id = absint( $block->context['postId'] );
			$product_id         = 'product' === get_post_type( $context_product_id ) ? $context_product_id : 0;
		}

		if ( 0 === $product_id ) {
			$product_id = $this->current_product_id();
		}

		return $this->render_for_product( $product_id );
	}

	/** Registers block metadata, server render callback and editor product search. */
	public function register_dynamic_block(): void {
		wp_register_script(
			'hamkharid-complements-block',
			plugins_url( 'assets/js/complements-block.js', HAMKHARID_FILE ),
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-api-fetch', 'wp-server-side-render' ),
			HAMKHARID_VERSION,
			true
		);
		wp_set_script_translations( 'hamkharid-complements-block', 'hamkharid', HAMKHARID_PATH . 'languages' );

		register_block_type(
			self::BLOCK_NAME,
			array(
				'api_version'     => 2,
				'attributes'      => array(
					'productId' => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'editor_script'   => 'hamkharid-complements-block',
				'render_callback' => array( $this, 'render_block' ),
				'supports'        => array( 'align' => array( 'wide', 'full' ) ),
			)
		);
	}

	/** Registers a bounded, capability-protected product search for the editor. */
	public function register_product_search_route(): void {
		register_rest_route(
			'hamkharid/v1',
			'/products',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_products' ),
				'permission_callback' => static function (): bool {
					// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce for product managers.
					return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_products' );
				},
				'args'                => array(
					'search'     => array(
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
					'product_id' => array(
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);
	}

	/**
	 * Returns a limited list of published products for the authorized block editor.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<int, array{id:int,label:string}>
	 */
	public function search_products( WP_REST_Request $request ): array {
		$product_id = absint( $request->get_param( 'product_id' ) );
		$products   = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 20,
				'search'  => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				'orderby' => 'title',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);

		if ( $product_id > 0 ) {
			$selected_product = wc_get_product( $product_id );

			if ( $selected_product instanceof WC_Product && 'publish' === $selected_product->get_status() ) {
				$products[] = $selected_product;
			}
		}

		$products = array_values(
			array_reduce(
				$products,
				static function ( array $unique, $product ): array {
					if ( $product instanceof WC_Product ) {
						$unique[ $product->get_id() ] = $product;
					}

					return $unique;
				},
				array()
			)
		);

		return array_map(
			static function ( WC_Product $product ): array {
				return array(
					'id'    => $product->get_id(),
					'label' => sprintf( '%s (#%d)', $product->get_name(), $product->get_id() ),
				);
			},
			array_values(
				array_filter(
					$products,
					static function ( $product ): bool {
						return $product instanceof WC_Product;
					}
				)
			)
		);
	}

	/**
	 * Runs the matching engine and returns accessible WooCommerce loop markup.
	 *
	 * @param int $product_id Current product ID.
	 */
	private function render_for_product( int $product_id ): string {
		if ( $product_id <= 0 || isset( $this->rendered_product_ids[ $product_id ] ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$rule = $this->matcher->match( $product_id );

		if ( null === $rule ) {
			return '';
		}

		$products = $this->recommendation_engine->recommend( $product_id, $rule );

		if ( array() === $products ) {
			return '';
		}

		$this->rendered_product_ids[ $product_id ] = true;
		$this->enqueue_stylesheet();
		ob_start();
		$this->render_product_loop( $product_id, $products );

		return (string) ob_get_clean();
	}

	/** Returns the product ID from WooCommerce's current context, if any. */
	private function current_product_id(): int {
		global $product;

		if ( $product instanceof WC_Product ) {
			return $product->get_id();
		}

		$product_id = get_queried_object_id();

		return 'product' === get_post_type( $product_id ) ? (int) $product_id : 0;
	}

	/** Enqueues the namespace-scoped frontend stylesheet. */
	private function enqueue_stylesheet(): void {
		wp_enqueue_style(
			'hamkharid-complements',
			plugins_url( 'assets/css/frontend.css', HAMKHARID_FILE ),
			array(),
			HAMKHARID_VERSION
		);
	}

	/** Detects embeds saved in the current post so their style can load before the body. */
	private function current_post_contains_embed(): bool {
		$post = get_queried_object();

		if ( $post instanceof \WP_Post ) {
			if ( has_shortcode( $post->post_content, 'hamkharid_complements' ) || has_block( self::BLOCK_NAME, $post ) ) {
				return true;
			}

			foreach ( get_post_meta( $post->ID ) as $meta_key => $meta_values ) {
				if ( ! preg_match( '/builder|content|elementor|wpb|brizy|fl_builder/i', (string) $meta_key ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {
					if ( $this->contains_embed_marker( $meta_value ) ) {
						return true;
					}
				}
			}
		}

		$sidebars = wp_get_sidebars_widgets();

		foreach ( $sidebars as $sidebar_id => $widget_ids ) {
			if ( 'wp_inactive_widgets' === $sidebar_id || ! is_array( $widget_ids ) ) {
				continue;
			}

			foreach ( $widget_ids as $widget_id ) {
				if ( ! preg_match( '/^(text|custom_html|block)-(\d+)$/', (string) $widget_id, $matches ) ) {
					continue;
				}

				$instances = get_option( 'widget_' . $matches[1], array() );
				$instance  = is_array( $instances ) ? ( $instances[ (int) $matches[2] ] ?? array() ) : array();

				if ( $this->contains_embed_marker( $instance ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/** Detects an embed marker in saved builder or widget data.
	 *
	 * @param mixed $value Candidate widget or builder data.
	 */
	private function contains_embed_marker( $value ): bool {
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		return false !== strpos( $value, '[hamkharid_complements' ) || false !== strpos( $value, self::BLOCK_NAME );
	}

	/** Outputs recommendations through WooCommerce's native product loop template.
	 *
	 * @param int          $current_product_id Current product ID.
	 * @param WC_Product[] $products           Recommended products.
	 */
	private function render_product_loop( int $current_product_id, array $products ): void {
		$previous_post          = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$had_previous_post      = isset( $GLOBALS['post'] );
		$previous_product       = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		$had_previous_product   = isset( $GLOBALS['product'] );
		$previous_loop_name     = wc_get_loop_prop( 'name' );
		$previous_loop_columns  = wc_get_loop_prop( 'columns' );
		$heading_id             = 'hamkharid-complements-title-' . $current_product_id;
		$recommendation_columns = min( 4, max( 1, count( $products ) ) );

		wc_set_loop_prop( 'name', 'hamkharid_complements' );
		wc_set_loop_prop( 'columns', $recommendation_columns );

		try {
			printf(
				'<section class="hamkharid-complements related products" aria-labelledby="%1$s"><h2 id="%1$s">%2$s</h2>',
				esc_attr( $heading_id ),
				esc_html( $this->heading() )
			);

			woocommerce_product_loop_start();

			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce loop templates require the canonical post/product globals.
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
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		}
	}

	/** Resolves locale-specific copy and administrator overrides. */
	private function heading(): string {
		$settings = DisplaySettings::get();
		$locale   = get_locale();
		$is_fa    = 1 === preg_match( '/^fa(?:_|$)/', $locale );
		$override = $is_fa ? $settings['title_fa'] : $settings['title_en'];

		if ( '' !== $override ) {
			return $override;
		}

		return __( 'Complete your purchase', 'hamkharid' );
	}
}
