<?php
/**
 * Display and lifecycle settings for complement recommendations.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Admin;

use Hamkharid\SmartCategoryComplement\Matching\RuleMatcher;
use Hamkharid\SmartCategoryComplement\Recommendations\RecommendationEngine;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and validates versioned display settings.
 */
final class DisplaySettings {

	public const OPTION_NAME          = 'hamkharid_display_settings_v1';
	public const SCHEMA_VERSION       = 1;
	public const SAVE_ACTION          = 'hamkharid_save_display_settings';
	public const NONCE_ACTION         = 'hamkharid_save_display_settings';
	public const PREVIEW_NONCE_ACTION = 'hamkharid_preview_complements';

	/**
	 * Registers the settings page and its save action.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
	}

	/**
	 * Returns sanitized settings. An absent option preserves the old automatic placement.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored   = get_option( self::OPTION_NAME, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = self::defaults( 'automatic' );

		return array(
			'schema_version'          => self::SCHEMA_VERSION,
			'mode'                    => in_array( $stored['mode'] ?? '', array( 'manual', 'automatic' ), true ) ? $stored['mode'] : $defaults['mode'],
			'placement'               => in_array( $stored['placement'] ?? '', array( 'after_summary', 'after_add_to_cart' ), true ) ? $stored['placement'] : $defaults['placement'],
			'title_fa'                => self::sanitize_title( $stored['title_fa'] ?? '' ),
			'title_en'                => self::sanitize_title( $stored['title_en'] ?? '' ),
			'allow_backorders'        => ! empty( $stored['allow_backorders'] ),
			'clear_data_on_uninstall' => ! empty( $stored['clear_data_on_uninstall'] ),
		);
	}

	/**
	 * Initializes defaults on plugin activation without replacing saved preferences.
	 * Existing rule data indicates an upgrade from the automatic-hook behavior.
	 *
	 * @return void
	 */
	public static function initialize_on_activation(): void {
		if ( false !== get_option( self::OPTION_NAME, false ) ) {
			return;
		}

		$existing_rule_ids = get_posts(
			array(
				'post_type'      => 'hamkharid_rule',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		$mode              = array() === $existing_rule_ids ? 'manual' : 'automatic';

		add_option( self::OPTION_NAME, self::defaults( $mode ), '', false );
	}

	/**
	 * Adds a WooCommerce submenu entry for display settings.
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Hamkharid Display Settings', 'hamkharid' ),
			__( 'Complement Display', 'hamkharid' ),
			'manage_woocommerce',
			'hamkharid-display-settings',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the settings form.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage complement display settings.', 'hamkharid' ), '', array( 'response' => 403 ) );
		}

		$settings = self::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Complement Display Settings', 'hamkharid' ); ?></h1>
			<p><?php esc_html_e( 'Place recommendations manually with the dynamic block or shortcode, or choose one automatic product-page location.', 'hamkharid' ); ?></p>
			<p><code>[hamkharid_complements]</code></p>
			<?php $this->render_preview(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, '_hamkharid_display_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="hamkharid-display-mode"><?php esc_html_e( 'Display Mode', 'hamkharid' ); ?></label></th>
							<td>
								<select id="hamkharid-display-mode" name="hamkharid_display_settings[mode]">
									<option value="manual" <?php selected( $settings['mode'], 'manual' ); ?>><?php esc_html_e( 'Manual: use a block or shortcode', 'hamkharid' ); ?></option>
									<option value="automatic" <?php selected( $settings['mode'], 'automatic' ); ?>><?php esc_html_e( 'Automatic: use one product-page location', 'hamkharid' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Manual mode disables automatic placement. Enabling a rule does not insert a block into a page.', 'hamkharid' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="hamkharid-display-placement"><?php esc_html_e( 'Automatic Location', 'hamkharid' ); ?></label></th>
							<td><select id="hamkharid-display-placement" name="hamkharid_display_settings[placement]">
								<option value="after_summary" <?php selected( $settings['placement'], 'after_summary' ); ?>><?php esc_html_e( 'After product summary', 'hamkharid' ); ?></option>
								<option value="after_add_to_cart" <?php selected( $settings['placement'], 'after_add_to_cart' ); ?>><?php esc_html_e( 'After Add to Cart form', 'hamkharid' ); ?></option>
							</select></td>
						</tr>
						<tr>
							<th scope="row"><label for="hamkharid-title-fa"><?php esc_html_e( 'Persian Heading', 'hamkharid' ); ?></label></th>
							<td><input class="regular-text" id="hamkharid-title-fa" name="hamkharid_display_settings[title_fa]" type="text" value="<?php echo esc_attr( $settings['title_fa'] ); ?>" /><p class="description"><?php esc_html_e( 'Leave blank to use the translated default heading.', 'hamkharid' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><label for="hamkharid-title-en"><?php esc_html_e( 'English Heading', 'hamkharid' ); ?></label></th>
							<td><input class="regular-text" id="hamkharid-title-en" name="hamkharid_display_settings[title_en]" type="text" value="<?php echo esc_attr( $settings['title_en'] ); ?>" /><p class="description"><?php esc_html_e( 'Leave blank to use the translated default heading.', 'hamkharid' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Backorders', 'hamkharid' ); ?></th>
							<td><label for="hamkharid-allow-backorders"><input id="hamkharid-allow-backorders" name="hamkharid_display_settings[allow_backorders]" type="checkbox" value="1" <?php checked( $settings['allow_backorders'] ); ?> /> <?php esc_html_e( 'Include products that WooCommerce allows customers to backorder.', 'hamkharid' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Uninstall', 'hamkharid' ); ?></th>
							<td><label for="hamkharid-clear-data"><input id="hamkharid-clear-data" name="hamkharid_display_settings[clear_data_on_uninstall]" type="checkbox" value="1" <?php checked( $settings['clear_data_on_uninstall'] ); ?> /> <?php esc_html_e( 'Delete Hamkharid rules and settings when the plugin is deleted.', 'hamkharid' ); ?></label><p class="description"><?php esc_html_e( 'By default, your rules and settings are kept when the plugin is deleted. Orders and products are never removed.', 'hamkharid' ); ?></p></td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Enqueues the bounded product selector only on this settings screen.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_preview_assets( string $hook_suffix ): void {
		if ( 'woocommerce_page_hamkharid-display-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'hamkharid-admin-preview',
			plugins_url( 'assets/js/admin-preview.js', HAMKHARID_FILE ),
			array( 'wp-i18n' ),
			HAMKHARID_VERSION,
			true
		);
		wp_set_script_translations( 'hamkharid-admin-preview', 'hamkharid', HAMKHARID_PATH . 'languages' );
		wp_localize_script(
			'hamkharid-admin-preview',
			'hamkharidPreview',
			array(
				'restUrl' => rest_url( 'hamkharid/v1/products' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/** Renders a read-only product preview using the storefront matcher and engine. */
	private function render_preview(): void {
		$product_id = isset( $_GET['hamkharid_preview_product_id'] ) ? absint( wp_unslash( $_GET['hamkharid_preview_product_id'] ) ) : 0;
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		?>
		<section class="hamkharid-preview">
			<h2><?php esc_html_e( 'Preview recommendations', 'hamkharid' ); ?></h2>
			<p><?php esc_html_e( 'Choose a product to see the winning rule and the products the storefront engine would return. This preview does not change a rule or the cart.', 'hamkharid' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="hamkharid-display-settings" />
				<?php wp_nonce_field( self::PREVIEW_NONCE_ACTION ); ?>
				<label for="hamkharid-preview-product-search"><?php esc_html_e( 'Search products', 'hamkharid' ); ?></label>
				<input type="search" id="hamkharid-preview-product-search" class="regular-text" autocomplete="off" aria-controls="hamkharid-preview-product-results" />
				<input type="hidden" name="hamkharid_preview_product_id" id="hamkharid-preview-product-id" value="<?php echo esc_attr( (string) $product_id ); ?>" />
				<div id="hamkharid-preview-product-results" class="hamkharid-preview-results" role="list" aria-live="polite"></div>
				<?php submit_button( __( 'Preview', 'hamkharid' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php
			if ( $product_id > 0 ) {
				if ( ! wp_verify_nonce( $nonce, self::PREVIEW_NONCE_ACTION ) ) {
					printf( '<div class="notice notice-error inline"><p>%s</p></div>', esc_html__( 'The preview request expired. Search for the product again.', 'hamkharid' ) );
				} else {
					$this->render_preview_result( $product_id );
				}
			}
			?>
		</section>
		<?php
	}

	/** Renders the matching rule and eligible products without mutating the cart.
	 *
	 * @param int $product_id Product ID selected for preview.
	 */
	private function render_preview_result( int $product_id ): void {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() ) {
			printf( '<div class="notice notice-warning inline" role="status"><p>%s</p></div>', esc_html__( 'Choose a published WooCommerce product.', 'hamkharid' ) );
			return;
		}

		$matcher = new RuleMatcher( new RuleRepository() );
		$result  = $matcher->match_with_diagnostics( $product_id );
		$rule    = $result['rule'];

		if ( null === $rule ) {
			$messages = array(
				'invalid_product'         => __( 'The selected product is invalid.', 'hamkharid' ),
				'no_match'                => __( 'No enabled rule matches this product.', 'hamkharid' ),
				'invalid_target'          => __( 'Matching rules point to deleted categories. Repair a rule target.', 'hamkharid' ),
				'candidate_limit_reached' => __( 'The rule search reached its safety limit. Review the matching rules for deleted targets.', 'hamkharid' ),
			);

			printf(
				'<div class="notice notice-warning inline" role="status"><p>%s</p></div>',
				esc_html( $messages[ $result['reason'] ] ?? __( 'No rule could be selected.', 'hamkharid' ) )
			);
			return;
		}

		$recommendations = ( new RecommendationEngine() )->recommend( $product_id, $rule );

		if ( array() === $recommendations ) {
			printf( '<div class="notice notice-info inline" role="status"><p>%s</p></div>', esc_html__( 'A rule matched, but no eligible products were found in its target category.', 'hamkharid' ) );
			return;
		}

		printf(
			'<div class="notice notice-success inline" role="status"><p>%1$s <strong>%2$s</strong> — %3$s %4$d. %5$s</p></div>',
			esc_html__( 'Matched rule:', 'hamkharid' ),
			esc_html( $rule->name() ),
			esc_html__( 'Category distance:', 'hamkharid' ),
			(int) $result['distance'],
			esc_html__( 'Recommended products:', 'hamkharid' )
		);
		echo '<ul class="hamkharid-preview-products">';

		foreach ( $recommendations as $recommendation ) {
			printf(
				'<li><a href="%1$s">%2$s</a> <span>%3$s</span></li>',
				esc_url( get_edit_post_link( $recommendation->get_id(), 'raw' ) ),
				esc_html( $recommendation->get_name() ),
				wp_kses_post( wc_price( (float) $recommendation->get_price() ) )
			);
		}

		echo '</ul>';
	}

	/**
	 * Validates and stores submitted settings.
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage complement display settings.', 'hamkharid' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE_ACTION, '_hamkharid_display_nonce' );
		$input    = isset( $_POST['hamkharid_display_settings'] ) && is_array( $_POST['hamkharid_display_settings'] )
			? map_deep( wp_unslash( $_POST['hamkharid_display_settings'] ), 'sanitize_text_field' )
			: array();
		$previous = self::get();

		$settings = array(
			'schema_version'          => self::SCHEMA_VERSION,
			'mode'                    => in_array( $input['mode'] ?? '', array( 'manual', 'automatic' ), true ) ? $input['mode'] : $previous['mode'],
			'placement'               => in_array( $input['placement'] ?? '', array( 'after_summary', 'after_add_to_cart' ), true ) ? $input['placement'] : $previous['placement'],
			'title_fa'                => self::sanitize_title( $input['title_fa'] ?? $previous['title_fa'] ),
			'title_en'                => self::sanitize_title( $input['title_en'] ?? $previous['title_en'] ),
			'allow_backorders'        => ! empty( $input['allow_backorders'] ),
			'clear_data_on_uninstall' => ! empty( $input['clear_data_on_uninstall'] ),
		);

		update_option( self::OPTION_NAME, $settings, false );
		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=hamkharid-display-settings' ) ) );
		exit;
	}

	/**
	 * Provides schema defaults.
	 *
	 * @param string $mode Initial display mode.
	 * @return array<string, mixed>
	 */
	private static function defaults( string $mode ): array {
		return array(
			'schema_version'          => self::SCHEMA_VERSION,
			'mode'                    => $mode,
			'placement'               => 'after_summary',
			'title_fa'                => '',
			'title_en'                => '',
			'allow_backorders'        => false,
			'clear_data_on_uninstall' => false,
		);
	}

	/**
	 * Sanitizes an optional heading override.
	 *
	 * @param mixed $title Submitted value.
	 */
	private static function sanitize_title( $title ): string {
		if ( ! is_scalar( $title ) ) {
			return '';
		}

		return sanitize_text_field( (string) $title );
	}
}
