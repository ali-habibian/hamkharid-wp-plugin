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
		$rule       = $this->repository->find( $post->ID );
		$categories = get_terms(
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
							<?php $this->render_category_options( $categories, $source_category_ids ); ?>
						</select>
						<p class="description"><?php esc_html_e( 'The rule applies when a product belongs to any selected category or its descendants.', 'hamkharid' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-target-category"><?php esc_html_e( 'Target Category', 'hamkharid' ); ?></label></th>
					<td>
						<select id="hamkharid-target-category" name="hamkharid_target_category_id" class="widefat" required>
							<option value=""><?php esc_html_e( 'Select a category', 'hamkharid' ); ?></option>
							<?php $this->render_category_options( $categories, array( $target_category_id ) ); ?>
						</select>
						<p class="description"><?php esc_html_e( 'Recommended products will be selected from this category and its descendants.', 'hamkharid' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-recommendation-count"><?php esc_html_e( 'Recommendation Count', 'hamkharid' ); ?></label></th>
					<td><input type="number" id="hamkharid-recommendation-count" name="hamkharid_recommendation_count" value="<?php echo esc_attr( (string) $recommendation_count ); ?>" min="<?php echo esc_attr( (string) Rule::MIN_RECOMMENDATION_COUNT ); ?>" max="<?php echo esc_attr( (string) Rule::MAX_RECOMMENDATION_COUNT ); ?>" step="1" class="small-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="hamkharid-sort-strategy"><?php esc_html_e( 'Sort Strategy', 'hamkharid' ); ?></label></th>
					<td>
						<select id="hamkharid-sort-strategy" name="hamkharid_sort_strategy">
							<?php foreach ( $this->sort_labels() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sort_strategy, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php if ( array() === $categories ) : ?>
			<p class="notice notice-warning inline"><?php esc_html_e( 'Create at least one WooCommerce product category before saving a rule.', 'hamkharid' ); ?></p>
		<?php endif; ?>
		<?php
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
