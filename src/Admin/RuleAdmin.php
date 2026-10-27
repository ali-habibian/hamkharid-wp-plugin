<?php
/**
 * Complement rule administration.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Admin;

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use Hamkharid\SmartCategoryComplement\Rules\RulePostType;
use Hamkharid\SmartCategoryComplement\Rules\RuleRepository;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates secure rule saves and status changes.
 */
final class RuleAdmin {

	public const NONCE_ACTION  = 'hamkharid_save_rule';
	public const NONCE_NAME    = 'hamkharid_rule_nonce';
	public const TOGGLE_ACTION = 'hamkharid_toggle_rule';

	private const ERROR_QUERY_ARG         = 'hamkharid_rule_error';
	private const TOGGLED_QUERY_ARG       = 'hamkharid_rule_toggled';
	private const FORM_VALUES_META_PREFIX = '_hamkharid_invalid_rule_form_';

	/**
	 * Rule persistence gateway.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Validation error captured during the current save request.
	 *
	 * @var string
	 */
	private $save_error_code = '';

	/**
	 * Guards the callback while the repository updates the post.
	 *
	 * @var bool
	 */
	private $is_saving = false;

	/**
	 * Original post snapshots captured before WordPress saves the native edit form.
	 *
	 * @var array<int, WP_Post>
	 */
	private $pre_update_posts = array();

	/**
	 * Creates the rule administration controller.
	 *
	 * @param RuleRepository $repository Rule persistence gateway.
	 */
	public function __construct( RuleRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Registers admin hooks and native UI components.
	 */
	public function register_hooks(): void {
		add_action( 'pre_post_update', array( $this, 'capture_pre_update' ), 10, 2 );
		add_action( 'save_post_' . RulePostType::POST_TYPE, array( $this, 'save' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
		add_action( 'admin_post_' . self::TOGGLE_ACTION, array( $this, 'toggle_rule' ) );
		add_filter( 'redirect_post_location', array( $this, 'add_save_error_to_redirect' ) );

		$meta_box = new RuleMetaBox( $this->repository );
		$meta_box->register_hooks();

		$list_table = new RuleListTable( $this->repository );
		$list_table->register_hooks();
	}

	/**
	 * Captures existing post data before the native editor writes its submitted title/status.
	 *
	 * @param int                  $post_id Post being updated.
	 * @param array<string, mixed> $data    Fields about to be written.
	 */
	public function capture_pre_update( int $post_id, array $data = array() ): void {
		unset( $data );

		if ( $this->is_saving || RulePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( $post instanceof WP_Post ) {
			$this->pre_update_posts[ $post_id ] = clone $post;
		}
	}

	/**
	 * Persists validated rule fields after a native post save.
	 *
	 * @param int     $post_id Rule post ID.
	 * @param WP_Post $post    Rule post.
	 * @param bool    $update  Whether this is an update.
	 */
	public function save( int $post_id, WP_Post $post, bool $update ): void {
		if ( $this->is_saving || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$source_category_values = array();
		if ( isset( $_POST['hamkharid_source_category_ids'] ) && is_array( $_POST['hamkharid_source_category_ids'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Every scalar value is sanitized before it is used below.
			$submitted_source_values = wp_unslash( $_POST['hamkharid_source_category_ids'] );

			foreach ( $submitted_source_values as $submitted_source_value ) {
				if ( is_scalar( $submitted_source_value ) ) {
					$source_category_values[] = sanitize_text_field( (string) $submitted_source_value );
				}
			}
		}
		$source_category_ids        = array_map( 'absint', $source_category_values );
		$target_category_value      = isset( $_POST['hamkharid_target_category_id'] ) && is_scalar( $_POST['hamkharid_target_category_id'] )
			? sanitize_text_field( wp_unslash( $_POST['hamkharid_target_category_id'] ) )
			: '';
		$target_category_id         = '' !== $target_category_value
			? absint( $target_category_value )
			: 0;
		$recommendation_count_value = isset( $_POST['hamkharid_recommendation_count'] ) && is_scalar( $_POST['hamkharid_recommendation_count'] )
			? sanitize_text_field( wp_unslash( $_POST['hamkharid_recommendation_count'] ) )
			: null;
		$recommendation_count       = null !== $recommendation_count_value
			? $this->parse_integer_input( $recommendation_count_value )
			: Rule::DEFAULT_RECOMMENDATION_COUNT;
		$sort_strategy              = isset( $_POST['hamkharid_sort_strategy'] ) && is_scalar( $_POST['hamkharid_sort_strategy'] )
			? sanitize_key( wp_unslash( $_POST['hamkharid_sort_strategy'] ) )
			: Rule::SORT_RANDOM;
		$priority_value             = isset( $_POST['hamkharid_priority'] ) && is_scalar( $_POST['hamkharid_priority'] )
			? sanitize_text_field( wp_unslash( $_POST['hamkharid_priority'] ) )
			: null;
		$priority                   = null !== $priority_value
			? $this->parse_integer_input( $priority_value )
			: Rule::DEFAULT_PRIORITY;
		$enabled                    = isset( $_POST['hamkharid_enabled'] );

		$this->is_saving = true;

		try {
			$result = $this->repository->update(
				$post_id,
				$post->post_title,
				$enabled,
				$source_category_ids,
				$target_category_id,
				$recommendation_count,
				$sort_strategy,
				$priority
			);

			if ( is_wp_error( $result ) ) {
				$this->save_error_code = $result->get_error_code();
				update_user_meta(
					get_current_user_id(),
					self::form_values_meta_key( $post_id ),
					$this->submitted_form_values( $post, $source_category_values, $target_category_value, $recommendation_count_value, $sort_strategy, $priority_value, $enabled )
				);

				if ( isset( $this->pre_update_posts[ $post_id ] ) ) {
					$original = $this->pre_update_posts[ $post_id ];
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_title'  => $original->post_title,
							'post_name'   => $original->post_name,
							'post_status' => $original->post_status,
						)
					);
				} elseif ( ! $update ) {
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_status' => 'draft',
						)
					);
				}
			} else {
				delete_user_meta( get_current_user_id(), self::form_values_meta_key( $post_id ) );
			}
		} finally {
			$this->is_saving = false;
			unset( $this->pre_update_posts[ $post_id ] );
		}
	}

	/**
	 * Returns the per-user key used to preserve invalid form values for one rule.
	 *
	 * @param int $post_id Rule post ID.
	 */
	public static function form_values_meta_key( int $post_id ): string {
		return self::FORM_VALUES_META_PREFIX . max( 0, $post_id );
	}

	/**
	 * Parses a submitted integer without silently clamping invalid values.
	 *
	 * @param mixed $value Submitted value.
	 */
	private function parse_integer_input( $value ): int {
		if ( ! is_scalar( $value ) ) {
			return PHP_INT_MIN;
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		return 1 === preg_match( '/^-?\d+$/', $value ) ? (int) $value : PHP_INT_MIN;
	}

	/**
	 * Captures safe form values so the editor can repopulate them after validation fails.
	 *
	 * @param WP_Post     $post         Rule post with the submitted native title.
	 * @param string[]    $source_ids  Sanitized source category IDs submitted by the user.
	 * @param string      $target_id   Sanitized target category ID submitted by the user.
	 * @param string|null $count    Sanitized recommendation count, if submitted.
	 * @param string      $sort_strategy Sanitized sorting strategy submitted by the user.
	 * @param string|null $priority Sanitized priority, if submitted.
	 * @param bool        $enabled     Whether the submitted rule is enabled.
	 * @return array<string, mixed>
	 */
	private function submitted_form_values( WP_Post $post, array $source_ids, string $target_id, ?string $count, string $sort_strategy, ?string $priority, bool $enabled ): array {
		return array(
			'name'                 => sanitize_text_field( $post->post_title ),
			'enabled'              => $enabled,
			'source_category_ids'  => $source_ids,
			'target_category_id'   => $target_id,
			'recommendation_count' => $count ?? '',
			'sort_strategy'        => $sort_strategy,
			'priority'             => $priority ?? '',
		);
	}

	/**
	 * Adds a validation result to WordPress' post-save redirect.
	 *
	 * @param string $location Redirect URL.
	 */
	public function add_save_error_to_redirect( string $location ): string {
		if ( '' === $this->save_error_code ) {
			return $location;
		}

		return add_query_arg( self::ERROR_QUERY_ARG, $this->save_error_code, $location );
	}

	/**
	 * Renders save, validation, and toggle notices.
	 */
	public function render_admin_notice(): void {
		$screen = get_current_screen();

		if ( null === $screen || RulePostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- These query values only select a read-only notice.
		$error_code = isset( $_GET[ self::ERROR_QUERY_ARG ] )
			? sanitize_key( wp_unslash( $_GET[ self::ERROR_QUERY_ARG ] ) )
			: '';
		$toggled    = isset( $_GET[ self::TOGGLED_QUERY_ARG ] )
			? sanitize_key( wp_unslash( $_GET[ self::TOGGLED_QUERY_ARG ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' !== $error_code ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $this->error_message( $error_code ) )
			);
		}

		if ( in_array( $toggled, array( 'enabled', 'disabled' ), true ) ) {
			$message = 'enabled' === $toggled
				? __( 'The complement rule was enabled.', 'hamkharid' )
				: __( 'The complement rule was disabled.', 'hamkharid' );

			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}

	/**
	 * Toggles a rule after verifying capability and nonce.
	 */
	public function toggle_rule(): void {
		$rule_id = isset( $_GET['rule_id'] ) ? absint( wp_unslash( $_GET['rule_id'] ) ) : 0;

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to manage complement rules.', 'hamkharid' ),
				esc_html__( 'Permission denied', 'hamkharid' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( self::TOGGLE_ACTION . '_' . $rule_id );

		$rule = $this->repository->find( $rule_id );

		if ( null === $rule ) {
			$this->redirect_to_list( '', 'hamkharid_rule_not_found' );
		}

		$result = $this->repository->update(
			$rule->id(),
			$rule->name(),
			! $rule->is_enabled(),
			$rule->source_category_ids(),
			$rule->target_category_id(),
			$rule->recommendation_count(),
			$rule->sort_strategy(),
			$rule->priority()
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_list( '', $result->get_error_code() );
		}

		$this->redirect_to_list( $rule->is_enabled() ? 'disabled' : 'enabled' );
	}

	/**
	 * Converts a validation code to a safe administrator-facing message.
	 *
	 * @param string $error_code Validation error code.
	 */
	private function error_message( string $error_code ): string {
		$messages = array(
			'hamkharid_rule_name_required'           => __( 'Enter a name for the complement rule.', 'hamkharid' ),
			'hamkharid_source_category_required'     => __( 'Select at least one valid source category. The saved rule was preserved.', 'hamkharid' ),
			'hamkharid_target_category_required'     => __( 'Select a valid target category. The saved rule was preserved.', 'hamkharid' ),
			'hamkharid_recommendation_count_invalid' => __( 'Choose between one and six recommended products.', 'hamkharid' ),
			'hamkharid_priority_invalid'             => __( 'Priority must be between zero and one hundred.', 'hamkharid' ),
			'hamkharid_sort_strategy_invalid'        => __( 'Choose a supported sorting method.', 'hamkharid' ),
			'hamkharid_rule_not_found'               => __( 'The complement rule could not be found.', 'hamkharid' ),
		);

		return $messages[ $error_code ] ?? __( 'The complement rule could not be saved. Please review its settings.', 'hamkharid' );
	}

	/**
	 * Redirects back to the rule list and terminates the request.
	 *
	 * @param string $toggled    Toggle result.
	 * @param string $error_code Optional error code.
	 */
	private function redirect_to_list( string $toggled = '', string $error_code = '' ): void {
		$url = add_query_arg(
			array_filter(
				array(
					'post_type'             => RulePostType::POST_TYPE,
					self::TOGGLED_QUERY_ARG => $toggled,
					self::ERROR_QUERY_ARG   => $error_code,
				)
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
