<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;

/**
 * Class LimitFloatPrecisionTest
 */
class LimitFloatPrecisionTest extends Unit {

	/**
	 * @return void
	 */
	public function testLimitFloatPrecision():void {
		self::assertEquals(0.0, DynamicAttributesValues::LimitFloatPrecision(0));
		self::assertEquals(0.4285714285714, DynamicAttributesValues::LimitFloatPrecision(3 / 7));
		self::assertEquals(2.3333333333333, DynamicAttributesValues::LimitFloatPrecision(7 / 3));
		self::assertEquals(2.333333333333333333333333, DynamicAttributesValues::LimitFloatPrecision(7 / 3));
		self::assertEquals(7 / 3, DynamicAttributesValues::LimitFloatPrecision(7 / 3));
		self::assertEquals(42.857142857142, DynamicAttributesValues::LimitFloatPrecision(300 / 7));
		self::assertEquals(4285.7142857142, DynamicAttributesValues::LimitFloatPrecision(30000 / 7));
		self::assertEquals(1.4285714E-6, DynamicAttributesValues::LimitFloatPrecision(0.1 / 70000));
		self::assertEquals(1.4285714E-6, DynamicAttributesValues::LimitFloatPrecision(1.4285714E-6));
		self::assertEquals(1.42857142E-5, DynamicAttributesValues::LimitFloatPrecision(1.4285714285714285714285714285714e-5));
		self::assertEquals(10.630145812735, DynamicAttributesValues::LimitFloatPrecision(sqrt(113)));
		self::assertEquals(0.0, DynamicAttributesValues::LimitFloatPrecision(-0));
		self::assertEquals(-0.428571428571, DynamicAttributesValues::LimitFloatPrecision(-3 / 7));
		self::assertEquals(-42.857142857142, DynamicAttributesValues::LimitFloatPrecision(-300 / 7));

		self::assertEquals(-1.4285714E-6, DynamicAttributesValues::LimitFloatPrecision(-(0.1 / 70000)));
		self::assertEquals(-1.42857142E-5, DynamicAttributesValues::LimitFloatPrecision(-1.4285714285714285714285714285714e-5));

		self::assertTrue(is_nan(DynamicAttributesValues::LimitFloatPrecision(sqrt(-1))));//NAN.0
		self::assertTrue(is_infinite(DynamicAttributesValues::LimitFloatPrecision(100 ** 500)));//INF.0
	}
}