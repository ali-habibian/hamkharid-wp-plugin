<?php
/**
 * Complement rule list table.
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
 * Customizes the native rule list table without replacing WordPress CRUD.
 */
final class RuleListTable {

	/**
	 * Rule persistence gateway.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Creates the rule list-table integration.
	 *
	 * @param RuleRepository $repository Rule persistence gateway.
	 */
	public function __construct( RuleRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Registers list-table hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'manage_' . RulePostType::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . RulePostType::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . RulePostType::POST_TYPE, array( $this, 'bulk_actions' ) );
	}

	/**
	 * Returns native list-table columns for rules.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		return array(
			'cb'                => $columns['cb'] ?? '<input type="checkbox" />',
			'title'             => __( 'Rule Name', 'hamkharid' ),
			'hamkharid_source'  => __( 'Source Categories', 'hamkharid' ),
			'hamkharid_target'  => __( 'Target Category', 'hamkharid' ),
			'hamkharid_count'   => __( 'Count', 'hamkharid' ),
			'hamkharid_sort'    => __( 'Sort', 'hamkharid' ),
			'hamkharid_enabled' => __( 'Status', 'hamkharid' ),
			'date'              => $columns['date'] ?? __( 'Date', 'hamkharid' ),
		);
	}

	/**
	 * Renders a custom rule list-table column.
	 *
	 * @param string $column  Column identifier.
	 * @param int    $post_id Rule post ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		$rule = $this->repository->find( $post_id );

		if ( null === $rule ) {
			echo '&mdash;';
			return;
		}

		switch ( $column ) {
			case 'hamkharid_source':
				$this->render_category_names( $rule->source_category_ids() );
				break;
			case 'hamkharid_target':
				$this->render_category_names( array( $rule->target_category_id() ) );
				break;
			case 'hamkharid_count':
				echo esc_html( (string) $rule->recommendation_count() );
				break;
			case 'hamkharid_sort':
				$labels = $this->sort_labels();
				echo esc_html( $labels[ $rule->sort_strategy() ] ?? $rule->sort_strategy() );
				break;
			case 'hamkharid_enabled':
				echo esc_html( $rule->is_enabled() ? __( 'Enabled', 'hamkharid' ) : __( 'Disabled', 'hamkharid' ) );
				break;
		}
	}

	/**
	 * Adds a nonce-protected Enable/Disable row action.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param WP_Post               $post    List-table post.
	 * @return array<string, string>
	 */
	public function row_actions( array $actions, WP_Post $post ): array {
		if ( RulePostType::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );

		if ( ! current_user_can( 'manage_woocommerce' ) || ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
			return $actions;
		}

		$rule = $this->repository->find( $post->ID );

		if ( null === $rule ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => RuleAdmin::TOGGLE_ACTION,
					'rule_id' => $post->ID,
				),
				admin_url( 'admin-post.php' )
			),
			RuleAdmin::TOGGLE_ACTION . '_' . $post->ID
		);

		$actions['hamkharid_toggle'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html( $rule->is_enabled() ? __( 'Disable', 'hamkharid' ) : __( 'Enable', 'hamkharid' ) )
		);

		return $actions;
	}

	/**
	 * Removes bulk editing because rule fields require the dedicated form.
	 *
	 * @param array<string, string> $actions Existing bulk actions.
	 * @return array<string, string>
	 */
	public function bulk_actions( array $actions ): array {
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Renders category names, including a safe deleted-category fallback.
	 *
	 * @param int[] $category_ids Product category IDs.
	 */
	private function render_category_names( array $category_ids ): void {
		$names = array();

		foreach ( $category_ids as $category_id ) {
			$category = get_term( $category_id, 'product_cat' );

			if ( $category instanceof WP_Term ) {
				$names[] = $category->name;
			} else {
				$names[] = sprintf(
					/* translators: %d: Deleted product category ID. */
					__( 'Deleted category #%d', 'hamkharid' ),
					$category_id
				);
			}
		}

		echo array() !== $names ? esc_html( implode( ', ', $names ) ) : '&mdash;';
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
