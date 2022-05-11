<?php
declare(strict_types = 1);

namespace app\components;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\widgets\BadgeWidget;
use Throwable;
use yii\db\ActiveRecordInterface;
use yii\grid\DataColumn;

/**
 * Class TemporaryHelper
 */
class TemporaryHelper {

	/**
	 * @param ActiveRecordInterface|string $model
	 * @return array
	 * @throws Throwable
	 */
	public static function DynamicColumns(ActiveRecordInterface|string $model):array {
		$result = [];
		foreach (DynamicAttributes::getAttributesTypes($model) as $name => $type) {
			$result[] = [
				'class' => DataColumn::class,
				'label' => $name,
				'attribute' => static::GetDynamicAttributeAlias(DynamicAttributes::listAttributes($model), $name),
				'format' => 'raw',
				'value' => static fn(ActiveRecordInterface $model) => BadgeWidget::widget([
					'items' => $model,
					'subItem' => $name
				]),
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