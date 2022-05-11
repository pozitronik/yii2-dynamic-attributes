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
			if (($flags & self::FLAG_COMPARE_KEYS) && !array_key_exists($a1key, $array_two)) {//разница по ключам
				return false;
			}
			if (($flags & self::FLAG_COMPARE_VALUES) && !in_array($a1value, $array_two, true)) {//разница по значениям
				return false;
			}
			if ($flags & self::FLAG_COMPARE_KEY_VALUES_PAIRS) {
				if (!array_key_exists($a1key, $array_two) || $a1value !== $array_two[$a1key]) {
					return false;
				}
			}
			if (static::isTraversable($a1value) && static::isTraversable($array_two[$a1key]??null) && false === static::isEqual($a1value, $array_two[$a1key]??null)) {
				return false;
			}
		}

		return true;
	}
}