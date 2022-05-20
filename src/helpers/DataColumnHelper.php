<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\helpers;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
use yii\db\ActiveRecordInterface;
use yii\grid\DataColumn;

/**
 * Class DataColumnHelper
 */
class DataColumnHelper {
	/**
	 * @param ActiveRecordInterface|string $model
	 * @param string $dataColumnClass
	 * @param callable|null $valueGenerator
	 * @return array
	 * @throws Throwable
	 */
	public static function DynamicDataColumns(ActiveRecordInterface|string $model, string $dataColumnClass = DataColumn::class, ?callable $valueGenerator = null):array {
		$result = [];
		foreach (DynamicAttributes::getAttributesTypes($model) as $name => $type) {
			$result[] = [
				'class' => $dataColumnClass,
				'label' => $name,
				'attribute' => static::GetDynamicAttributeAlias(DynamicAttributes::listAttributes($model), $name),
				'format' => 'raw',
				'value' => $valueGenerator??static fn(ActiveRecordInterface $model) => $model->$name
			];
		}
		return $result;
	}

	/**
	 * @param array $attributes
	 * @return array
	 */
	public static function DynamicColumnsAliasesGenerator(array $attributes):array {
		array_walk($attributes, static fn(&$value, $key) => $value = 'da'.$key);
		return $attributes;
	}

	/**
	 * @param array $attributes
	 * @param string $attribute_name
	 * @return string
	 */
	public static function GetDynamicAttributeAlias(array $attributes, string $attribute_name):string {
		$index = array_search($attribute_name, $attributes, true);
		return 'da'.$index;
	}
}