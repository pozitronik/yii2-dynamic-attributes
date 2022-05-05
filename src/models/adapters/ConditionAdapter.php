<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use Exception;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;

/**
 * Адаптируем условие для динамических параметров в ActiveQuery-условие
 */
class ConditionAdapter {

	/**
	 * Превращает упрощённое условие выборки в массив для QueryBuilder
	 * @param array $condition
	 * @return array
	 * @throws Exception
	 * @throws Exception
	 */
	public static function adapt(array $condition):array {
		if (isset($condition[0])) {//['operator', 'attribute_name', 'attribute_value']
			$operator = array_shift($condition);
			$attribute_name = array_shift($condition);
			$attribute_value = array_shift($condition);
		} else { //['attribute_name' => 'attribute_value']
			/** @var string $attribute_name */
			$attribute_name = array_key_first($condition);
			$attribute_value = $condition[$attribute_name];
			$condition = [];
			$operator = static::getOperator($attribute_value);
		}
		$adaptedExpression = [$operator, self::jsonFieldName(DynamicAttributesValues::tableName(), 'attributes_values', $attribute_name, DynamicAttributes::getType($attribute_value)), $attribute_value];
		//В массиве могут остаться ещё какие-то параметры, например false в like - их просто добавим в адаптированное выражение
		return array_merge($adaptedExpression, $condition);
	}

	/**
	 * MySQL и PostgreSQL по разному атрибутируют поля в json.
	 * PGSQL ONLY!
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string $jsonFieldName
	 * @param int|null $fieldType
	 * @return string
	 */
	public static function jsonFieldName(string $tableName, string $fieldName, string $jsonFieldName, ?int $fieldType):string {
		$dataType = static::PHPTypeToPgSQLType($fieldType);
		return "(\"".$tableName."\".\"".$fieldName."\"->>'".$jsonFieldName."')::{$dataType}";
	}

	/**
	 * Для типа данных php возвращает подходящий тип pgsql
	 * @param int|null $type
	 * @return string
	 */
	public static function PHPTypeToPgSQLType(?int $type):string {
		return match ($type) {
			DynamicAttributes::TYPE_BOOL => 'boolean',
			DynamicAttributes::TYPE_INT => 'int',
			DynamicAttributes::TYPE_DOUBLE => 'float',
//			DynamicAttributes::TYPE_STRING => 'text',
//			DynamicAttributes::TYPE_ARRAY => '', unsupported
//			DynamicAttributes::TYPE_OBJECT => '',unsupported
//			DynamicAttributes::TYPE_RESOURCE => '',unsupported
//			DynamicAttributes::TYPE_NULL => '',//meaningless
//			DynamicAttributes::TYPE_UNKNOWN => 'text',//compatibility
//			DynamicAttributes::TYPE_RESOURCE_CLOSED => '',
			default => 'text'
		};
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private static function getOperator(mixed $value):string {
		if (null === $value) return 'is';
		if (is_array($value)) return 'in';
		return '=';
	}

}