<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use yii\db\ActiveRecordInterface;

/**
 * Методы адаптации динамических параметров для ActiveQuery для MySQL
 */
class MySQLAdapter implements AdapterInterface {

	/**
	 * @inheritDoc
	 */
	public static function adaptField(string $jsonFieldName, string|ActiveRecordInterface|null $model = null):string {
		// TODO: Implement adaptField() method.
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public static function adaptWhere(array $condition):array {
		// TODO: Implement adaptWhere() method.
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string {
		// TODO: Implement jsonFieldName() method.
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public static function indexOnJsonField(string $jsonFieldName, ?int $fieldType, ?int $alias_id):?string {
		return null;
	}
}