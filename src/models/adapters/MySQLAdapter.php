<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use yii\db\ActiveRecordInterface;

/**
 * Методы адаптации динамических параметров для ActiveQuery для MySQL
 */
class MySQLAdapter extends CommonAdapter {

	/**
	 * @inheritDoc
	 */
	public static function adaptOrder(string $jsonFieldName, string|ActiveRecordInterface|null $model = null, int $order = SORT_ASC):array {
		return [
			"ISNULL(".static::adaptField($jsonFieldName, $model).")" => $order,
			static::adaptField($jsonFieldName, $model) => $order
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string {
		return sprintf("JSON_VALUE(%s.attributes_values, '\$.\"%s\"' %s NULL ON EMPTY)", DynamicAttributesValues::tableName(), $jsonFieldName, static::PHPTypeToMySQLType($fieldType));
	}

	/**
	 * Для типа данных php возвращает подходящий тип mysql для операторов JSON_VALUE()/CAST()
	 * Именно в таком варианте, потому что вариант по умолчанию RETURNING VARCHAR(512) не может быть указан прямо.
	 * @param int|null $type
	 * @return string
	 */
	private static function PHPTypeToMySQLType(?int $type):string {
		return match ($type) {
			DynamicAttributes::TYPE_BOOL, DynamicAttributes::TYPE_INT => 'RETURNING DECIMAL',
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

}