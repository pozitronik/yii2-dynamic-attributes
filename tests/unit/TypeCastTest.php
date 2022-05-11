<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\models\DynamicAttributes;

/**
 * Class TypeCastTest
 */
class TypeCastTest extends Unit {

	/**
	 * @return void
	 * @see https://www.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting
	 */
	public function testTypeCastBool():void {
		$string_true = 'true';
		$string_1 = '1';
		$string_0 = '0';
		$string_10 = '10';
		$string_null = 'null';
		$string_empty = '';
		$string_string = 'string';
		$string_float = '1.1';
		$int_negative = -1;
		$int_1 = 1;
		$int_0 = 0;
		$int_10 = 10;
		$null = null;
		$false = false;
		$true = true;
		$float = 1.1;
		$array_empty = [];
		$array_not_empty = ['any'];

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_true));
		self::assertEquals(true, $string_true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_1));
		self::assertEquals(true, $string_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_0));
		self::assertEquals(false, $string_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_10));
		self::assertEquals(true, $string_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_null));
		self::assertEquals(true, $string_null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_empty));
		self::assertEquals(false, $string_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_string));
		self::assertEquals(true, $string_string);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $string_float));
		self::assertEquals(true, $string_float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $int_negative));
		self::assertEquals(true, $int_negative);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $int_1));
		self::assertEquals(true, $int_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $int_0));
		self::assertEquals(false, $int_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $int_10));
		self::assertEquals(true, $int_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $null));
		self::assertEquals(false, $null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $false));
		self::assertEquals(false, $false);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $true));
		self::assertEquals(true, $true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $float));
		self::assertEquals(true, $float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $array_empty));
		self::assertEquals(false, $array_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_BOOL, $array_not_empty));
		self::assertEquals(true, $array_not_empty);
	}

	/**
	 * @return void
	 * @see https://www.php.net/manual/en/language.types.integer.php#language.types.integer.casting
	 */
	public function testTypeCastInt():void {
		$string_true = 'true';
		$string_1 = '1';
		$string_0 = '0';
		$string_10 = '10';
		$string_null = 'null';
		$string_empty = '';
		$string_string = 'string';
		$string_float = '1.1';
		$int_negative = -1;
		$int_1 = 1;
		$int_0 = 0;
		$int_10 = 10;
		$null = null;
		$false = false;
		$true = true;
		$float = 1.1;
		$array_empty = [];
		$array_not_empty = ['any'];

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_true));
		self::assertEquals(0, $string_true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_1));
		self::assertEquals(1, $string_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_0));
		self::assertEquals(0, $string_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_10));
		self::assertEquals(10, $string_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_null));
		self::assertEquals(0, $string_null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_empty));
		self::assertEquals(0, $string_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_string));
		self::assertEquals(0, $string_string);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $string_float));
		self::assertEquals(1, $string_float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $int_negative));
		self::assertEquals(-1, $int_negative);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $int_1));
		self::assertEquals(1, $int_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $int_0));
		self::assertEquals(0, $int_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $int_10));
		self::assertEquals(10, $int_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $null));
		self::assertEquals(0, $null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $false));
		self::assertEquals(0, $false);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $true));
		self::assertEquals(1, $true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $float));
		self::assertEquals(1, $float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $array_empty));
		self::assertEquals(0, $array_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_INT, $array_not_empty));
		self::assertEquals(1, $array_not_empty);
	}

	/**
	 * @return void
	 * @see https://www.php.net/manual/en/language.types.float.php#language.types.float.casting
	 */
	public function testTypeCastFloat():void {
		$string_true = 'true';
		$string_1 = '1';
		$string_0 = '0';
		$string_10 = '10';
		$string_null = 'null';
		$string_empty = '';
		$string_string = 'string';
		$string_float = '1.1';
		$int_negative = -1;
		$int_1 = 1;
		$int_0 = 0;
		$int_10 = 10;
		$null = null;
		$false = false;
		$true = true;
		$float = 1.1;
		$array_empty = [];
		$array_not_empty = ['any'];

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_true));
		self::assertEquals(0.0, $string_true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_1));
		self::assertEquals(1.0, $string_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_0));
		self::assertEquals(0.0, $string_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_10));
		self::assertEquals(10.0, $string_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_null));
		self::assertEquals(0.0, $string_null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_empty));
		self::assertEquals(0.0, $string_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_string));
		self::assertEquals(0.0, $string_string);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $string_float));
		self::assertEquals(1.1, $string_float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $int_negative));
		self::assertEquals(-1.0, $int_negative);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $int_1));
		self::assertEquals(1.0, $int_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $int_0));
		self::assertEquals(0.0, $int_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $int_10));
		self::assertEquals(10.0, $int_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $null));
		self::assertEquals(0.0, $null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $false));
		self::assertEquals(0.0, $false);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $true));
		self::assertEquals(1.0, $true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $float));
		self::assertEquals(1.1, $float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $array_empty));
		self::assertEquals(0.0, $array_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_FLOAT, $array_not_empty));
		self::assertEquals(1.0, $array_not_empty);
	}

	/**
	 * @return void
	 * @see https://www.php.net/manual/en/language.types.string.php#language.types.string.casting
	 */
	public function testTypeCastString():void {
		$string_true = 'true';
		$string_1 = '1';
		$string_0 = '0';
		$string_10 = '10';
		$string_null = 'null';
		$string_empty = '';
		$string_string = 'string';
		$string_float = '1.1';
		$int_negative = -1;
		$int_1 = 1;
		$int_0 = 0;
		$int_10 = 10;
		$null = null;
		$false = false;
		$true = true;
		$float = 1.1;
		$array_empty = [];
		$array_not_empty = ['any'];

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_true));
		self::assertEquals('true', $string_true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_1));
		self::assertEquals('1', $string_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_0));
		self::assertEquals('0', $string_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_10));
		self::assertEquals('10', $string_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_null));
		self::assertEquals('null', $string_null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_empty));
		self::assertEquals('', $string_empty);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_string));
		self::assertEquals('string', $string_string);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $string_float));
		self::assertEquals('1.1', $string_float);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $int_negative));
		self::assertEquals('-1', $int_negative);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $int_1));
		self::assertEquals('1', $int_1);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $int_0));
		self::assertEquals('0', $int_0);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $int_10));
		self::assertEquals('10', $int_10);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $null));
		self::assertEquals('', $null);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $false));
		self::assertEquals('', $false);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $true));
		self::assertEquals('1', $true);

		self::assertTrue(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $float));
		self::assertEquals('1.1', $float);

		self::assertFalse(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $array_empty));
		self::assertEquals([], $array_empty);

		self::assertFalse(DynamicAttributes::castTo(DynamicAttributes::TYPE_STRING, $array_not_empty));
		self::assertEquals(['any'], $array_not_empty);
	}
}