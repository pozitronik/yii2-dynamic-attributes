<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use yii\db\ActiveRecordInterface;

/**
 * Методы адаптации динамических параметров для ActiveQuery для PgSQL
 */
class PgSQLAdapter implements AdapterInterface {

	/**
	 * @inheritDoc
	 */
	public static function adaptField(string $jsonFieldName, ActiveRecordInterface|string|null $model = null):string {
		return self::jsonFieldName($jsonFieldName, null === $model?null:DynamicAttributes::attributeType($model, $jsonFieldName));
	}

	/**
	 * @inheritDoc
	 * fixme: функция не умеет обрабатывать случаи, когда первым оператором идёт условие or/and
	 */
	public static function adaptWhere(array $condition):array {
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
		$adaptedExpression = [$operator, self::jsonFieldName($attribute_name, DynamicAttributes::getType($attribute_value)), $attribute_value];
		//В массиве могут остаться ещё какие-то параметры, например false в like - их просто добавим в адаптированное выражение
		return array_merge($adaptedExpression, $condition);
	}

	/**
	 * @inheritDoc
	 */
	public static function adaptOrder(string $jsonFieldName, string|ActiveRecordInterface|null $model = null, int $order = SORT_ASC):array {
		return [
			static::adaptField($jsonFieldName, $model) => $order
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string {
		$dataType = (null === $fieldType)
			?''
			:"::".static::PHPTypeToPgSQLType($fieldType);
		return "(\"".DynamicAttributesValues::tableName()."\".\"attributes_values\"->>'".$jsonFieldName."'){$dataType}";
	}

	/**
	 * Для типа данных php возвращает подходящий тип pgsql
	 * @param int|null $type
	 * @return string
	 */
	private static function PHPTypeToPgSQLType(?int $type):string {
		return match ($type) {
			DynamicAttributes::TYPE_BOOL => 'boolean',
			DynamicAttributes::TYPE_INT => 'int',
			DynamicAttributes::TYPE_FLOAT => 'float',
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

	/**
	 * @inheritDoc
	 */
	public static function indexOnJsonField(string $jsonFieldName, ?int $fieldType, ?int $alias_id):?string {
		if (null === $alias_id) {
			$indexName = "{$jsonFieldName}_idx";
			$columnsList = "(".static::jsonFieldName($jsonFieldName, $fieldType).")";
		} else {
			$indexName = "{$jsonFieldName}_{$alias_id}_idx";
			$columnsList = "alias_id,(".static::jsonFieldName($jsonFieldName, $fieldType).")";
		}
		return "CREATE INDEX IF NOT EXISTS ".$indexName." ON ".DynamicAttributesValues::tableName()." (".$columnsList.") WHERE (".static::jsonFieldName($jsonFieldName, $fieldType).") IS NOT NULL";
	}

}