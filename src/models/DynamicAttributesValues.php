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
	 * @var bool limit float attributes size to 64 bits (as php native floats).
	 * By default, php uses a 64 bit floating-point precision (roughly 14 decimal digits, see [[https://www.php.net/manual/en/language.types.float.php]]).
	 * But PostgreSQL may save float values with extended precision, that can cause problems with search, e.g.
	 * php:8/7 => 1.1428571428571,
	 * pgsql:5/7 => 1.1428571428571428
	 *
	 * >select * from table where float_column = 1.1428571428571 => nothing will be found
	 *
	 * This option forcibly restricts stored floats precision to 14 decimal digits, e.g. 1.1428571428571428 (16 digits after decimal point) will be stored as 1.1428571428571 (14 decimal digits)
	 */
	public bool $limitFloatPrecision = true;

	/**
	 * @var bool enables indexes creation on every single json subfield in sys_dynamic_attributes_values.attribute_value, if supported by DB engine.
	 */
	public bool $createIndexes = false;

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		$this->limitFloatPrecision = DynamicAttributesModule::param('limitFloatPrecision', $this->limitFloatPrecision);
		$this->createIndexes = DynamicAttributesModule::param('createIndexes', $this->createIndexes);
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
		if ((new static())->limitFloatPrecision) {
			$attribute_value = static::LimitFloatPrecision($attribute_value);
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
	 * Волшебная магия.
	 * Нужно обрезать число так, чтобы оно было «длиной» в 14 знаков (не после десятичного знака, а вообще). Неважно, что PHP почти всегда отдаёт float как 14-знаковое число,
	 * внутреннее представление у него хранится с максимально возможной для платформы точностью (которая уйдёт в БД, что вызовет проблемы).
	 *
	 * Поэтому float превращается в строку с максимальным размером дробной части в 16 символов (избыточное представление), затем из этой строки берётся
	 * 15 символов числа (14 знаков + разделитель) и приводится к float.
	 * Надеюсь, стало понятнее.
	 *
	 * Стоит понимать, что результат может быть отдан в экспоненциальной форме (например 1.4285714E-6) - так оно работает, и это нормально.
	 * Для отрицательных чисел знак включается в максимальную длину.
	 * Для пограничных случаев, когда передано NaN/Inf возвращаются именно эти значения, иначе интерпретатор неявно приведёт их к 0.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function LimitFloatPrecision(mixed $value):mixed {
		if ((is_float($value))) {
			if (is_nan($value)) return NAN;
			if (is_infinite($value)) return INF;
			return (float)substr(sprintf('%.16f', $value), 0, 16);
		}

		return $value;
	}

	/**
	 * Изменяет значения динамических атрибутов
	 * @param int $alias_id
	 * @param int $model_id
	 * @param array $attributes_values [attribute name => attribute value]
	 * @return static|null
	 */
	public static function setAttributesValues(int $alias_id, int $model_id, array $attributes_values):?static {
		if ((new static())->limitFloatPrecision) {
			array_walk($attributes_values, static function(&$value, $key) {
				$value = static::LimitFloatPrecision($value);
			});
		}

		try {
			$valueRecord = static::Upsert(compact('model_id', 'alias_id'));
			$oldValues = $valueRecord->attributes_values;
			$oldValues = array_replace_recursive($oldValues??[], $attributes_values);
			$valueRecord->attributes_values = $oldValues;
			$valueRecord->save();
			return $valueRecord;
		} catch (Throwable $e) {
			Yii::warning("Unable to update or insert table value: {$e->getMessage()}", __METHOD__);
		}
		return null;
	}
}