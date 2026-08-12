<?php
/**
 * Tests for the main plugin coordinator.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests\Unit
 */

namespace Hamkharid\SmartCategoryComplement\Tests\Unit;

use Hamkharid\SmartCategoryComplement\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the foundation-level plugin behavior.
 */
final class PluginTest extends TestCase {

	/**
	 * The plugin can be constructed through Composer autoloading.
	 *
	 * @return void
	 */
	public function test_plugin_is_autoloadable(): void {
		$plugin = new Plugin();

		self::assertInstanceOf( Plugin::class, $plugin );
	}

	/**
	 * WooCommerce is reported as unavailable in the isolated unit environment.
	 *
	 * @return void
	 */
	public function test_woocommerce_is_unavailable_without_its_runtime(): void {
		$plugin = new Plugin();

		self::assertFalse( $plugin->is_woocommerce_available() );
	}
}
