<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use yii\db\ActiveRecordInterface;

/**
 * Методы адаптации динамических параметров для ActiveQuery для MySQL
 */
class MySQLAdapter implements AdapterInterface {

	/**
	 * @inheritDoc
	 * todo::common
	 */
	public static function adaptField(string $jsonFieldName, string|ActiveRecordInterface|null $model = null):string {
		return self::jsonFieldName($jsonFieldName, null === $model?null:DynamicAttributes::attributeType($model, $jsonFieldName));
	}

	/**
	 * @inheritDoc
	 * todo::common
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
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string {
		return 'JSON_VALUE('.DynamicAttributesValues::tableName().'.attributes_values, "$.'.$jsonFieldName.'" '.static::PHPTypeToMySQLType($fieldType).' NULL ON EMPTY)';
	}

	/**
	 * Для типа данных php возвращает подходящий тип mysql для операторов JSON_VALUE()/CAST()
	 * Именно в таком варианте, потому что вариант по умолчанию RETURNING VARCHAR(512) не может быть указан прямо.
	 * @param int|null $type
	 * @return string
	 */
	private static function PHPTypeToMySQLType(?int $type):string {
		return match ($type) {
			DynamicAttributes::TYPE_BOOL => 'RETURNING DECIMAL',
			DynamicAttributes::TYPE_INT => 'RETURNING DECIMAL',
			DynamicAttributes::TYPE_FLOAT => 'RETURNING FLOAT',
			default => ''
		};
	}

	/**
	 * @inheritDoc
	 */
	public static function indexOnJsonField(string $jsonFieldName, ?int $fieldType, ?int $alias_id):?string {
		return null;
	}

	/**
	 * @param mixed $value
	 * @return string
	 * todo:: common
	 */
	private static function getOperator(mixed $value):string {
		if (null === $value) return 'is';
		if (is_array($value)) return 'in';
		return '=';
	}
}