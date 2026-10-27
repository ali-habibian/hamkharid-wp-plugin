<?php
/**
 * Complement rule value object.
 *
 * @package Hamkharid\SmartCategoryComplement
 */

namespace Hamkharid\SmartCategoryComplement\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Provides a normalized, read-only representation of a stored rule.
 */
final class Rule {

	public const DEFAULT_RECOMMENDATION_COUNT = 3;
	public const MIN_RECOMMENDATION_COUNT     = 1;
	public const MAX_RECOMMENDATION_COUNT     = 6;
	public const DEFAULT_PRIORITY             = 0;
	public const MIN_PRIORITY                 = 0;
	public const MAX_PRIORITY                 = 100;

	public const SORT_RANDOM       = 'random';
	public const SORT_NEWEST       = 'newest';
	public const SORT_BEST_SELLING = 'best_selling';

	/**
	 * Rule post ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Rule name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Whether the rule is enabled.
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * Source product category IDs.
	 *
	 * @var int[]
	 */
	private $source_category_ids;

	/**
	 * Target product category ID.
	 *
	 * @var int
	 */
	private $target_category_id;

	/**
	 * Number of products to recommend.
	 *
	 * @var int
	 */
	private $recommendation_count;

	/**
	 * Recommendation sort strategy.
	 *
	 * @var string
	 */
	private $sort_strategy;

	/**
	 * Rule priority within a specificity tier.
	 *
	 * @var int
	 */
	private $priority;

	/**
	 * Creates a normalized rule value.
	 *
	 * @param int    $id                    Rule post ID.
	 * @param string $name                  Rule name.
	 * @param bool   $enabled               Whether the rule is enabled.
	 * @param int[]  $source_category_ids   Source product category IDs.
	 * @param int    $target_category_id    Target product category ID.
	 * @param int    $recommendation_count  Number of recommendations.
	 * @param string $sort_strategy         Recommendation sort strategy.
	 * @param int    $priority              Rule priority within its specificity tier.
	 */
	public function __construct(
		int $id,
		string $name,
		bool $enabled,
		array $source_category_ids,
		int $target_category_id,
		int $recommendation_count,
		string $sort_strategy,
		int $priority = self::DEFAULT_PRIORITY
	) {
		$source_category_ids = array_map( 'intval', $source_category_ids );
		$source_category_ids = array_filter(
			$source_category_ids,
			static function ( int $category_id ): bool {
				return $category_id > 0;
			}
		);

		$this->id                   = max( 0, $id );
		$this->name                 = $name;
		$this->enabled              = $enabled;
		$this->source_category_ids  = array_values( array_unique( $source_category_ids ) );
		$this->target_category_id   = max( 0, $target_category_id );
		$this->recommendation_count = self::normalize_recommendation_count( $recommendation_count );
		$this->sort_strategy        = self::normalize_sort_strategy( $sort_strategy );
		$this->priority             = self::normalize_priority( $priority );
	}

	/**
	 * Returns the rule post ID.
	 */
	public function id(): int {
		return $this->id;
	}

	/**
	 * Returns the rule name.
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Reports whether the rule is enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Returns the source product category IDs.
	 *
	 * @return int[]
	 */
	public function source_category_ids(): array {
		return $this->source_category_ids;
	}

	/**
	 * Returns the target product category ID.
	 */
	public function target_category_id(): int {
		return $this->target_category_id;
	}

	/**
	 * Returns the number of products to recommend.
	 */
	public function recommendation_count(): int {
		return $this->recommendation_count;
	}

	/**
	 * Returns the recommendation sort strategy.
	 */
	public function sort_strategy(): string {
		return $this->sort_strategy;
	}

	/**
	 * Returns the priority within the rule's specificity tier.
	 */
	public function priority(): int {
		return $this->priority;
	}

	/**
	 * Returns the supported sort strategies.
	 *
	 * @return string[]
	 */
	public static function sort_strategies(): array {
		return array(
			self::SORT_RANDOM,
			self::SORT_NEWEST,
			self::SORT_BEST_SELLING,
		);
	}

	/**
	 * Normalizes a requested recommendation count.
	 *
	 * @param int $count Requested recommendation count.
	 */
	public static function normalize_recommendation_count( int $count ): int {
		if ( $count < self::MIN_RECOMMENDATION_COUNT ) {
			return self::DEFAULT_RECOMMENDATION_COUNT;
		}

		return min( self::MAX_RECOMMENDATION_COUNT, $count );
	}

	/**
	 * Normalizes a priority to the supported inclusive range.
	 *
	 * @param int $priority Requested priority.
	 */
	public static function normalize_priority( int $priority ): int {
		return min( self::MAX_PRIORITY, max( self::MIN_PRIORITY, $priority ) );
	}

	/**
	 * Sanitizes an arbitrary metadata value as a priority.
	 *
	 * @param mixed $priority Raw metadata value.
	 */
	public static function sanitize_priority( $priority ): int {
		if ( ! is_scalar( $priority ) || ! is_numeric( $priority ) ) {
			return self::DEFAULT_PRIORITY;
		}

		return self::normalize_priority( (int) $priority );
	}

	/**
	 * Normalizes a requested sort strategy.
	 *
	 * @param string $sort_strategy Requested sort strategy.
	 */
	public static function normalize_sort_strategy( string $sort_strategy ): string {
		if ( ! in_array( $sort_strategy, self::sort_strategies(), true ) ) {
			return self::SORT_RANDOM;
		}

		return $sort_strategy;
	}

	/**
	 * Sanitizes an arbitrary metadata value as a sort strategy.
	 *
	 * @param mixed $sort_strategy Raw metadata value.
	 */
	public static function sanitize_sort_strategy( $sort_strategy ): string {
		if ( ! is_string( $sort_strategy ) ) {
			return self::SORT_RANDOM;
		}

		return self::normalize_sort_strategy( $sort_strategy );
	}
}
