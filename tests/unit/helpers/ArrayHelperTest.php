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
		$array_seven = ['null', null, 'not null'];
		$array_eight = [null, null, null, -1];
		$array_nine = [0, 1, 2, 42];
		$array_ten = ['', null, -1, pi()];

		self::assertTrue(ArrayHelper::isEqual($array_one, $array_three));
		self::assertTrue(ArrayHelper::isEqual($array_two, $array_four));
		self::assertTrue(ArrayHelper::isEqual($array_five, $array_six));
		self::assertTrue(ArrayHelper::isEqual($array_seven, $array_seven));
		self::assertTrue(ArrayHelper::isEqual($array_eight, $array_eight));
		self::assertTrue(ArrayHelper::isEqual($array_nine, $array_nine));
		self::assertTrue(ArrayHelper::isEqual($array_ten, $array_ten));

		self::assertFalse(ArrayHelper::isEqual($array_one, $array_two));
		self::assertFalse(ArrayHelper::isEqual($array_two, $array_three));
		self::assertFalse(ArrayHelper::isEqual($array_five, $array_four));
		self::assertFalse(ArrayHelper::isEqual($array_eight, $array_seven));
		self::assertFalse(ArrayHelper::isEqual($array_eight, $array_ten));

	}

}