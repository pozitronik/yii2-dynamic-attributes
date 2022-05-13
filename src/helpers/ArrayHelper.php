<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\helpers;

use pozitronik\helpers\ArrayHelper as VendorArrayHelper;
use Traversable;

/**
 * Class ArrayHelper
 * todo: move to Yii2Helpers when tests will be done
 */
class ArrayHelper extends VendorArrayHelper {
	public const FLAG_COMPARE_KEYS = 1;//наборы ключей должны совпадать
	public const FLAG_COMPARE_VALUES = 2;//наборы значений должны совпадать
	public const FLAG_COMPARE_KEY_VALUES_PAIRS = 4;//наборы ключ-значение должны совпадать
	public const FLOAT_DELTA = 1.0E-13;//Дельта сравнения двух чисел с плавающей точкой внутри PHP

	/**
	 * Сравнивает два массива между собой по наборам данных (с учётом вложенности).
	 * Сравнение всегда строгое
	 * @param array|Traversable $array_one
	 * @param array|Traversable $array_two
	 * @param int $flags
	 * @return bool
	 */
	public static function isEqual(array|Traversable $array_one, array|Traversable $array_two, int $flags = 7):bool {
		if (count($array_one) !== count($array_two)) return false;
		foreach ($array_one as $a1key => $a1value) {
			if (($flags & self::FLAG_COMPARE_KEYS) && !static::keyExists($a1key, $array_two)) {//разница по ключам
				return false;
			}
			if (($flags & self::FLAG_COMPARE_VALUES) && !static::isInWithFloatDelta($a1value, $array_two, true)) {//разница по значениям
				return false;
			}
			if ($flags & self::FLAG_COMPARE_KEY_VALUES_PAIRS) {
				if (!static::keyExists($a1key, $array_two) || !static::isEquals($a1value, $array_two[$a1key])) {
					return false;
				}
			}
			if (static::isTraversable($a1value) && static::isTraversable($array_two[$a1key]??null) && false === static::isEqual($a1value, $array_two[$a1key]??null)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param mixed $scalarOne
	 * @param mixed $scalarTwo
	 * @return bool
	 */
	private static function isEquals(mixed $scalarOne, mixed $scalarTwo):bool {
		if (is_float($scalarOne) && is_float($scalarTwo)) return static::isFloatEquals($scalarOne, $scalarTwo);
		return $scalarOne === $scalarTwo;
	}

	/**
	 * @param mixed $floatOne
	 * @param mixed $floatTwo
	 * @param float $delta
	 * @return bool
	 */
	public static function isFloatEquals(mixed $floatOne, mixed $floatTwo, float $delta = self::FLOAT_DELTA):bool {
		if (is_infinite($floatTwo) && is_infinite($floatOne)) {
			return true;
		}

		if ((is_infinite($floatTwo) xor is_infinite($floatOne)) ||
			(is_nan($floatTwo) || is_nan($floatOne)) ||
			abs($floatTwo - $floatOne) > $delta) {
			return false;
		}
		return true;
	}

	/**
	 * Функция аналогична static::isIn() но для поиска float-значений учитывает дельту PHP
	 * @param mixed $needle
	 * @param array|Traversable $haystack
	 * @param bool $strict
	 * @return bool
	 */
	public static function isInWithFloatDelta(mixed $needle, array|Traversable $haystack, bool $strict = false):bool {
		if (is_float($needle)) {
			foreach ($haystack as $value) {
				if (is_float($value) && static::isFloatEquals($needle, $value)) {
					return true;
				}
			}
			return false;
		}
		return static::isIn($needle, $haystack, $strict);
	}

}