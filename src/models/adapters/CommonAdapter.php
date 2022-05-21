<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use yii\db\ActiveRecordInterface;

/**
 * Class CommonAdapter
 */
abstract class CommonAdapter implements AdapterInterface {

	/**
	 * @inheritDoc
	 */
	public static function adaptField(string $jsonFieldName, string|ActiveRecordInterface|null $model = null):string {
		return static::jsonFieldName($jsonFieldName, null === $model?null:DynamicAttributes::getAttributeType($model, $jsonFieldName));
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
		$adaptedExpression = [$operator, static::jsonFieldName($attribute_name, DynamicAttributes::getType($attribute_value)), $attribute_value];
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
	abstract public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string;

	/**
	 * @inheritDoc
	 */
	abstract public static function indexOnJsonField(string $jsonFieldName, ?int $fieldType, ?int $alias_id):?string;

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected static function getOperator(mixed $value):string {
		if (null === $value) return 'is';
		if (is_array($value)) return 'in';
		return '=';
	}
}