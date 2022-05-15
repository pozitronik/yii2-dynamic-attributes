<?php
declare(strict_types = 1);

namespace unit\helpers;
use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\helpers\ArrayHelper;

/**
 * Class ArrayHelperTests
 * todo: move to Yii2Helpers when tests will be done
 */
class ArrayHelperTest extends Unit {

	/**
	 * @return void
	 */
	public function testIsEqual():void {
		$array_one = ["red", "green", "blue"];
		$array_two = ["red", "green", "yellow"];
		$array_three = ["red", "blue", "green"];
		$array_four = ["a" => "red", "b" => "green", "c" => "yellow"];
		$array_five = ["x" => "red", "z" => "yellow", "y" => "green"];
		$array_six = ["x" => "red", "z" => "yellow", "y" => "green"];
		$array_seven = ["null", null, "not null"];
		$array_eight = ["c" => "not null", "b" => "null", "a" => null];
		$array_nine = [[M_PI, [["a" => "red"]]], [null, "string", 0], [false, true]];
		$array_ten = ["a" => [M_PI, [["a" => 'red']]], "b" => [null, "string", 0], "c" => [false, true]];

		self::assertTrue(ArrayHelper::isEqual($array_one, $array_two, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_two, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_two, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_two, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS + ArrayHelper::FLAG_COMPARE_KEYS + ArrayHelper::FLAG_COMPARE_VALUES));

		self::assertTrue(ArrayHelper::isEqual($array_one, $array_three, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_one, $array_three, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_three, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));
		self::assertTrue(ArrayHelper::isEqual($array_one, $array_three, ArrayHelper::FLAG_COMPARE_KEYS + ArrayHelper::FLAG_COMPARE_VALUES));

		self::assertFalse(ArrayHelper::isEqual($array_four, $array_five, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_four, $array_five, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_four, $array_five, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));
		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS + ArrayHelper::FLAG_COMPARE_KEYS + ArrayHelper::FLAG_COMPARE_VALUES));

		self::assertTrue(ArrayHelper::isEqual($array_one, $array_seven, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_seven, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_one, $array_seven, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertTrue(ArrayHelper::isEqual($array_seven, $array_seven, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_seven, $array_seven, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertTrue(ArrayHelper::isEqual($array_seven, $array_seven, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertFalse(ArrayHelper::isEqual($array_seven, $array_eight, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_seven, $array_eight, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_seven, $array_eight, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertTrue(ArrayHelper::isEqual($array_nine, $array_nine, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_nine, $array_nine, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertTrue(ArrayHelper::isEqual($array_nine, $array_nine, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertFalse(ArrayHelper::isEqual($array_nine, $array_ten, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_nine, $array_ten, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertFalse(ArrayHelper::isEqual($array_nine, $array_ten, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

		self::assertTrue(ArrayHelper::isEqual($array_ten, $array_ten, ArrayHelper::FLAG_COMPARE_KEYS));
		self::assertTrue(ArrayHelper::isEqual($array_ten, $array_ten, ArrayHelper::FLAG_COMPARE_VALUES));
		self::assertTrue(ArrayHelper::isEqual($array_ten, $array_ten, ArrayHelper::FLAG_COMPARE_KEY_VALUES_PAIRS));

	}

}