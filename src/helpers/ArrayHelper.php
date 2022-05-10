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

	/**
	 * Сравнивает два массива между собой по наборам данных (с учётом вложенности)
	 * @param array|Traversable $array_one
	 * @param array|Traversable $array_two
	 * @return bool
	 */
	public static function isEqual(array|Traversable $array_one, array|Traversable $array_two):bool {
		if (count($array_one) !== count($array_two)) return false;
		foreach ($array_one as $a1key => $a1value) {
			if (!array_key_exists($a1key, $array_two)) return false;
			$a2value = $array_two[$a1key];
			if (static::isTraversable($a1value) && static::isTraversable($a2value) && false === static::isEqual($a1value, $a2value)) return false;
			if ($a1value !== $a2value) return false;
		}

		return true;
	}
}