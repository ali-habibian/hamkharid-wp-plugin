<?php
/**
 * PHPUnit bootstrap.
 *
 * @package Hamkharid\SmartCategoryComplement\Tests
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress owns this bootstrap constant.
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
