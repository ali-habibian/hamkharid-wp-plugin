<?php
/**
 * Tests for the complement rule value object.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests\Unit
 */

namespace Hamkharid\SmartCategoryComplement\Tests\Unit\Rules;

use Hamkharid\SmartCategoryComplement\Rules\Rule;
use PHPUnit\Framework\TestCase;

/**
 * Verifies normalization of stored rule values.
 */
final class RuleTest extends TestCase {

	/**
	 * Duplicate and non-positive source category IDs are removed.
	 */
	public function test_rule_normalizes_duplicate_and_invalid_source_ids(): void {
		$rule = new Rule( 8, 'Accessories', true, array( 5, '5', -1, 0, 9 ), 12, 3, Rule::SORT_NEWEST );

		self::assertSame( array( 5, 9 ), $rule->source_category_ids() );
	}

	/**
	 * Recommendation counts cannot exceed the public maximum.
	 */
	public function test_rule_clamps_recommendation_count_to_maximum(): void {
		$rule = new Rule( 8, 'Accessories', true, array( 5 ), 12, 99, Rule::SORT_NEWEST );

		self::assertSame( Rule::MAX_RECOMMENDATION_COUNT, $rule->recommendation_count() );
	}

	/**
	 * The public minimum recommendation count remains valid.
	 */
	public function test_rule_preserves_minimum_recommendation_count(): void {
		$rule = new Rule(
			8,
			'Accessories',
			true,
			array( 5 ),
			12,
			Rule::MIN_RECOMMENDATION_COUNT,
			Rule::SORT_NEWEST
		);

		self::assertSame( Rule::MIN_RECOMMENDATION_COUNT, $rule->recommendation_count() );
	}

	/**
	 * Corrupt persisted values fall back to safe defaults.
	 */
	public function test_rule_uses_safe_defaults_for_corrupt_values(): void {
		$rule = new Rule( 8, 'Accessories', false, array( 5 ), 12, 0, 'unsupported' );

		self::assertSame( Rule::DEFAULT_RECOMMENDATION_COUNT, $rule->recommendation_count() );
		self::assertSame( Rule::SORT_RANDOM, $rule->sort_strategy() );
		self::assertFalse( $rule->is_enabled() );
	}

	/**
	 * Non-scalar metadata cannot trigger a type error in the sanitizer.
	 */
	public function test_sort_metadata_sanitizer_rejects_non_string_values(): void {
		self::assertSame( Rule::SORT_RANDOM, Rule::sanitize_sort_strategy( array( 'newest' ) ) );
	}

	/**
	 * Legacy rules and corrupt priority metadata resolve to the default value.
	 */
	public function test_priority_defaults_and_is_clamped_to_supported_range(): void {
		$legacy = new Rule( 8, 'Legacy', true, array( 5 ), 12, 3, Rule::SORT_RANDOM );

		self::assertSame( Rule::DEFAULT_PRIORITY, $legacy->priority() );
		self::assertSame( Rule::MAX_PRIORITY, Rule::normalize_priority( 120 ) );
		self::assertSame( Rule::MIN_PRIORITY, Rule::sanitize_priority( -4 ) );
		self::assertSame( Rule::DEFAULT_PRIORITY, Rule::sanitize_priority( array( 90 ) ) );
	}
}
