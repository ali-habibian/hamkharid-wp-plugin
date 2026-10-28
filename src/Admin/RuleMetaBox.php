<?php
/**
 * Complement rule edit form.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Admin;

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;
use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the native WordPress fields used to edit a rule.
 */
final class RuleMetaBox {

	/**
	 * Rule persistence gateway.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Creates the rule form renderer.
	 *
	 * @param RuleRepository $repository Rule persistence gateway.
	 */
	public function __construct( RuleRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Registers the rule settings meta box.
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes_' . RulePostType::POST_TYPE, array( $this, 'add_meta_box' ) );
	}

	/**
	 * Registers the rule settings meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'hamkharid-rule-settings',
			__( 'Rule Settings', 'hamkharid' ),
			array( $this, 'render' ),
			RulePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the rule settings fields.
	 *
	 * @param WP_Post $post Rule post being edited.
	 */
	public function render( WP_Post $post ): void {
		$rule = $this->repository->find( $post->ID );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query string selects a read-only validation message.
		$error_code  = isset( $_GET['hamkharid_rule_error'] ) && is_scalar( $_GET['hamkharid_rule_error'] ) ? sanitize_key( wp_unslash( $_GET['hamkharid_rule_error'] ) ) : '';
		$form_values = get_user_meta( get_current_user_id(), RuleAdmin::form_values_meta_key( $post->ID ), true );
		$form_values = is_array( $form_values ) ? $form_values : array();
		$categories  = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$is_new               = 'auto-draft' === $post->post_status;
		$is_enabled           = $is_new || ( null !== $rule && $rule->is_enabled() );
		$source_category_ids  = null !== $rule ? $rule->source_category_ids() : array();
		$target_category_id   = null !== $rule ? $rule->target_category_id() : 0;
		$recommendation_count = null !== $rule ? $rule->recommendation_count() : Rule::DEFAULT_RECOMMENDATION_COUNT;
		$sort_strategy        = null !== $rule ? $rule->sort_strategy() : Rule::SORT_RANDOM;
		$priority             = null !== $rule ? $rule->priority() : Rule::DEFAULT_PRIORITY;
		$is_enabled           = isset( $form_values['enabled'] ) ? (bool) $form_values['enabled'] : $is_enabled;
		$source_category_ids  = isset( $form_values['source_category_ids'] ) && is_array( $form_values['source_category_ids'] )
			? array_map( 'absint', $form_values['source_category_ids'] )
			: $source_category_ids;
		$target_category_id   = array_key_exists( 'target_category_id', $form_values )
			? ( is_numeric( $form_values['target_category_id'] ) ? absint( $form_values['target_category_id'] ) : 0 )
			: $target_category_id;
		$recommendation_count = isset( $form_values['recommendation_count'] )
			? sanitize_text_field( (string) $form_values['recommendation_count'] )
			: $recommendation_count;
		$sort_strategy        = isset( $form_values['sort_strategy'] )
			? sanitize_key( (string) $form_values['sort_strategy'] )
			: $sort_strategy;
		$priority             = isset( $form_values['priority'] )
			? sanitize_text_field( (string) $form_values['priority'] )
			: $priority;

		$recommendation_count_display = isset( $form_values['recommendation_count'] )
			? (string) $recommendation_count
			: RuleAdmin::format_human_number( (int) $recommendation_count );
		$priority_display             = isset( $form_values['priority'] )
			? (string) $priority
			: RuleAdmin::format_human_number( (int) $priority );

		wp_nonce_field( RuleAdmin::NONCE_ACTION, RuleAdmin::NONCE_NAME );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'hamkharid' ); ?></th>
					<td>
						<label for="hamkharid-enabled">
							<input type="checkbox" id="hamkharid-enabled" name="hamkharid_enabled" value="1" <?php checked( $is_enabled ); ?> />
							<?php esc_html_e( 'Use this rule on matching product pages.', 'hamkharid' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-source-categories"><?php esc_html_e( 'Source Categories', 'hamkharid' ); ?></label></th>
					<td>
						<select id="hamkharid-source-categories" name="hamkharid_source_category_ids[]" class="widefat" multiple size="8" required>
							<?php $this->render_missing_category_options( $source_category_ids, $categories ); ?>
							<?php $this->render_category_options( $categories, $source_category_ids ); ?>
						</select>
						<?php $this->render_field_error( $error_code, 'hamkharid_source_category_required' ); ?>
						<p class="description"><?php esc_html_e( 'The rule applies when a product belongs to any selected category or its descendants.', 'hamkharid' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-target-category"><?php esc_html_e( 'Target Category', 'hamkharid' ); ?></label></th>
					<td>
						<select id="hamkharid-target-category" name="hamkharid_target_category_id" class="widefat" required>
							<option value="" <?php selected( 0, (int) $target_category_id ); ?>><?php esc_html_e( 'Select a category', 'hamkharid' ); ?></option>
							<?php $this->render_missing_category_options( array( $target_category_id ), $categories ); ?>
							<?php $this->render_category_options( $categories, array( $target_category_id ) ); ?>
						</select>
						<?php $this->render_field_error( $error_code, 'hamkharid_target_category_required' ); ?>
						<p class="description"><?php esc_html_e( 'Recommended products will be selected from this category and its descendants.', 'hamkharid' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-recommendation-count"><?php esc_html_e( 'Recommendation Count', 'hamkharid' ); ?></label></th>
					<td><input type="text" inputmode="numeric" pattern="[0-9۰-۹٠-٩]+" id="hamkharid-recommendation-count" name="hamkharid_recommendation_count" value="<?php echo esc_attr( $recommendation_count_display ); ?>" class="small-text" required /><?php $this->render_field_error( $error_code, 'hamkharid_recommendation_count_invalid' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-sort-strategy"><?php esc_html_e( 'Sort Strategy', 'hamkharid' ); ?></label></th>
					<td>
						<select id="hamkharid-sort-strategy" name="hamkharid_sort_strategy">
							<?php if ( ! in_array( $sort_strategy, Rule::sort_strategies(), true ) ) : ?>
								<?php // translators: %s is the unsupported value submitted for the sorting strategy. ?>
								<option value="<?php echo esc_attr( $sort_strategy ); ?>" selected><?php printf( esc_html__( 'Invalid submitted value: %s', 'hamkharid' ), esc_html( $sort_strategy ) ); ?></option>
							<?php endif; ?>
							<?php foreach ( $this->sort_labels() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sort_strategy, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php $this->render_field_error( $error_code, 'hamkharid_sort_strategy_invalid' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-priority"><?php esc_html_e( 'Priority', 'hamkharid' ); ?></label></th>
					<td>
						<input type="text" inputmode="numeric" pattern="-?[0-9۰-۹٠-٩]+" id="hamkharid-priority" name="hamkharid_priority" value="<?php echo esc_attr( $priority_display ); ?>" class="small-text" />
						<?php $this->render_field_error( $error_code, 'hamkharid_priority_invalid' ); ?>
						<p class="description"><?php esc_html_e( 'A higher number wins among rules at the same category distance. A direct category match still wins over a parent category.', 'hamkharid' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php if ( array() === $categories ) : ?>
			<p class="notice notice-warning inline"><?php esc_html_e( 'Create at least one WooCommerce product category before saving a rule.', 'hamkharid' ); ?></p>
		<?php endif; ?>
		<?php if ( isset( $form_values['name'] ) ) : ?>
			<script>
			(function (title) {
				var input = document.getElementById('title');
				if (input) { input.value = title; }
			})(<?php echo wp_json_encode( sanitize_text_field( (string) $form_values['name'] ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* safely encodes a JS string literal. ?>);
			</script>
			<?php delete_user_meta( get_current_user_id(), RuleAdmin::form_values_meta_key( $post->ID ) ); ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders an inline message beside a field when its submitted value failed validation.
	 *
	 * @param string $error_code       Validation error from the redirect query.
	 * @param string $field_error_code Validation error represented by the field.
	 */
	private function render_field_error( string $error_code, string $field_error_code ): void {
		if ( $error_code !== $field_error_code ) {
			return;
		}

		$messages = array(
			'hamkharid_source_category_required'     => __( 'Select at least one existing source category.', 'hamkharid' ),
			'hamkharid_target_category_required'     => __( 'Select an existing target category.', 'hamkharid' ),
			'hamkharid_recommendation_count_invalid' => __( 'Enter a whole number from one to six.', 'hamkharid' ),
			'hamkharid_sort_strategy_invalid'        => __( 'Choose one of the available sorting methods.', 'hamkharid' ),
			'hamkharid_priority_invalid'             => __( 'Enter a whole number from zero to one hundred.', 'hamkharid' ),
		);

		if ( isset( $messages[ $field_error_code ] ) ) {
			printf( '<p class="description" role="alert">%s</p>', esc_html( $messages[ $field_error_code ] ) );
		}
	}

	/**
	 * Renders a selected category ID that no longer exists so invalid input remains visible.
	 *
	 * @param int[]     $selected_categories Submitted category IDs.
	 * @param WP_Term[] $categories          Existing categories.
	 */
	private function render_missing_category_options( array $selected_categories, array $categories ): void {
		$existing_ids = array_map(
			static function ( WP_Term $category ): int {
				return (int) $category->term_id;
			},
			$categories
		);

		foreach ( array_unique( array_map( 'absint', $selected_categories ) ) as $category_id ) {
			if ( 0 === $category_id || in_array( $category_id, $existing_ids, true ) ) {
				continue;
			}

			$label = sprintf(
				// translators: %d is the ID of a category that no longer exists.
				__( 'Unavailable category (#%d)', 'hamkharid' ),
				$category_id
			);
			printf(
				'<option value="%1$s" selected>%2$s</option>',
				esc_attr( (string) $category_id ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Renders product category options in hierarchy order.
	 *
	 * @param WP_Term[] $categories          Product categories.
	 * @param int[]     $selected_categories Selected category IDs.
	 */
	private function render_category_options( array $categories, array $selected_categories ): void {
		$children = array();

		foreach ( $categories as $category ) {
			$children[ $category->parent ][] = $category;
		}

		$visited = array();
		$this->render_category_branch( 0, $children, $selected_categories, $visited );

		foreach ( $categories as $category ) {
			if ( isset( $visited[ $category->term_id ] ) ) {
				continue;
			}

			$this->render_category_option( $category, $selected_categories, 0 );
			$visited[ $category->term_id ] = true;
			$this->render_category_branch( (int) $category->term_id, $children, $selected_categories, $visited, 1 );
		}
	}

	/**
	 * Renders one category tree branch.
	 *
	 * @param int                   $parent_id          Parent term ID.
	 * @param array<int, WP_Term[]> $children           Terms grouped by parent.
	 * @param int[]                 $selected_categories Selected category IDs.
	 * @param array<int, bool>      $visited            Already-rendered term IDs.
	 * @param int                   $depth              Current hierarchy depth.
	 */
	private function render_category_branch( int $parent_id, array $children, array $selected_categories, array &$visited, int $depth = 0 ): void {
		if ( ! isset( $children[ $parent_id ] ) ) {
			return;
		}

		foreach ( $children[ $parent_id ] as $category ) {
			if ( isset( $visited[ $category->term_id ] ) ) {
				continue;
			}

			$visited[ $category->term_id ] = true;
			$this->render_category_option( $category, $selected_categories, $depth );
			$this->render_category_branch( (int) $category->term_id, $children, $selected_categories, $visited, $depth + 1 );
		}
	}

	/**
	 * Renders a single escaped category option.
	 *
	 * @param WP_Term $category            Product category.
	 * @param int[]   $selected_categories Selected category IDs.
	 * @param int     $depth               Hierarchy depth.
	 */
	private function render_category_option( WP_Term $category, array $selected_categories, int $depth ): void {
		$label = str_repeat( '— ', $depth ) . $category->name;

		printf(
			'<option value="%1$d" %2$s>%3$s</option>',
			(int) $category->term_id,
			selected( in_array( (int) $category->term_id, $selected_categories, true ), true, false ),
			esc_html( $label )
		);
	}

	/**
	 * Returns translated sort labels keyed by strategy.
	 *
	 * @return array<string, string>
	 */
	private function sort_labels(): array {
		return array(
			Rule::SORT_RANDOM       => __( 'Random', 'hamkharid' ),
			Rule::SORT_NEWEST       => __( 'Newest', 'hamkharid' ),
			Rule::SORT_BEST_SELLING => __( 'Best Selling', 'hamkharid' ),
		);
	}
}
