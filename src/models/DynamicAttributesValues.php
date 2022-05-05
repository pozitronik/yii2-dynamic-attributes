<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributesValues as DynamicAttributesValuesAR;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use Yii;

/**
 * Class DynamicAttributesValues
 */
class DynamicAttributesValues extends DynamicAttributesValuesAR {
	/**
	 * @var bool enables a intermediate caching via Yii::$app->cache (must be configured in framework). The default option
	 * value can be set in the module configuration, e.g.
	 * ...
	 * 'dynamic_attributes' => [
	 *        'class' => DynamicAttributesModule::class,
	 *            'params' => [
	 *                'cacheEnabled' => true//defaults to false
	 *            ]
	 *        ],
	 * ...
	 */
	public bool $cacheEnabled = true;//todo: implement

	/**
	 * @var bool limit float attributes size to 64 bits (as php native floats).
	 * By default, php uses a 64 bit floating-point precision (roughly 14 decimal digits, see [[https://www.php.net/manual/en/language.types.float.php]]).
	 * But PostgreSQL may save float values with extended precision, that can cause problems with search, e.g.
	 * php:8/7 => 1.1428571428571,
	 * pgsql:5/7 => 1.1428571428571428
	 *
	 * >select * from table where float_column = 5/7 => nothing will be found
	 *
	 * This option forcibly restricts stored floats precision to 14 decimal digits, e.g. 1.1428571428571428 (16 digits after decimal point) will be stored as 1.1428571428571 (14 decimal digits)
	 */
	public bool $limitFloatPrecision = true;

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		$this->cacheEnabled = DynamicAttributesModule::param('cacheEnabled', $this->cacheEnabled);
		$this->limitFloatPrecision = DynamicAttributesModule::param('limitFloatPrecision', $this->limitFloatPrecision);
	}

	/**
	 * Изменяет значение динамического атрибута
	 * @param int $alias_id
	 * @param int $model_id
	 * @param string $attribute_name
	 * @param mixed $attribute_value
	 * @return DynamicAttributesValues|null
	 */
	public static function setAttributesValue(int $alias_id, int $model_id, string $attribute_name, mixed $attribute_value):?static {
		if (is_float($attribute_value) && (new static())->limitFloatPrecision) {
			/**
			 * Волшебная магия.
			 * Нужно обрезать число так, чтобы оно было «длиной» в 14 знаков (не после десятичного знака, а вообще). Неважно, что PHP почти всегда отдаёт float как 14-знаковое число,
			 * внутреннее представление у него хранится с максимально возможной для платформы точностью (которая уйдёт в БД, что вызовет проблемы).
			 * Поэтому значение умножается на такой множитель, чтобы при преобразовании в int отбросить лишний по длине «хвост», а затем делится на этот же множитель,
			 * чтобы снова стать float. Множитель же зависит от того, какая десятичная степень у целой части изначального значения.
			 * Надеюсь, стало понятнее.
			 **/
			$attribute_value = (int)($attribute_value * ($p = 10 ** (13 - intdiv((int)$attribute_value, 10)))) / $p;
		}

		try {
			$valueRecord = static::Upsert(compact('model_id', 'alias_id'));
			if (ArrayHelper::getValue($valueRecord->attributes_values, $attribute_name) !== $attribute_value) {
				$oldValues = $valueRecord->attributes_values;//изменять напрямую нельзя, поэтому промежуточная переменная
				$oldValues[$attribute_name] = $attribute_value;
				$valueRecord->attributes_values = $oldValues;
				$valueRecord->save();
			}
			return $valueRecord;

		} catch (Throwable $e) {
			Yii::warning("Unable to update or insert table value: {$e->getMessage()}", __METHOD__);
		}
		return null;
	}

	/**
	 * Изменяет значения динамических атрибутов
	 * @param int $alias_id
	 * @param int $model_id
	 * @param array $attributes_values [attribute name => attribute value]
	 * @return static|null
	 * не проверялось
	 */
	public static function setAttributesValues(int $alias_id, int $model_id, array $attributes_values):?static {
		try {
			$valueRecord = static::Upsert(compact('model_id', 'alias_id'));
			$oldValues = $valueRecord->attributes_values;
			$oldValues = array_merge_recursive($oldValues, $attributes_values);
			$valueRecord->attributes_values = $oldValues;
			$valueRecord->save();
			return $valueRecord;
		} catch (Throwable $e) {
			Yii::warning("Unable to update or insert table value: {$e->getMessage()}", __METHOD__);
		}
		return null;
	}
}