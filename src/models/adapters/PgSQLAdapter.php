<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;

/**
 * Методы адаптации динамических параметров для ActiveQuery для PgSQL
 */
class PgSQLAdapter extends CommonAdapter {

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