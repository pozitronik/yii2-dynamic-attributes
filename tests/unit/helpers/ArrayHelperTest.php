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
		$array_two = ["red", "green", "blue", "yellow"];
		$array_three = ["red", "blue", "green"];
		$array_four = ["a" => "red", "b" => "green", "c" => "yellow", "z" => "blue"];
		$array_five = [$array_one, $array_two];
		$array_six = [$array_two, $array_one];

		self::assertTrue(ArrayHelper::isEqual($array_one, $array_three));
		self::assertTrue(ArrayHelper::isEqual($array_two, $array_four));
		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six));

		self::assertFalse(ArrayHelper::isEqual($array_one, $array_two));
		self::assertFalse(ArrayHelper::isEqual($array_two, $array_three));
		self::assertFalse(ArrayHelper::isEqual($array_five, $array_four));

	}

}