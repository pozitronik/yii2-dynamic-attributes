<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\models\active_record\DynamicAttributesAliases;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use yii\base\BaseObject;
use yii\db\ExpressionInterface;

/**
 * Адаптируем условие для динамических параметров в ActiveQuery-условие
 */
class ConditionAdapter extends BaseObject implements ExpressionInterface {

	public array $expression = [];

	/**
	 * @param array $condition
	 * @param array $config
	 */
	public function __construct(array $condition, array $config = []) {
		$this->expression = $this->adaptExpression($condition);
		parent::__construct($config);
	}

	/**
	 * Превращает упрощённое условие выборки в массив для QueryBuilder
	 * @param array $condition
	 * @return array
	 */
	private function adaptExpression(array $condition):array {
		if (isset($condition[0])) {//['operator', 'attribute_name', 'attribute_value']
			$operator = strtoupper(array_shift($condition));
			$attribute_name = $condition[1];
			$attribute_value = $condition[2];
		} else { //['attribute_name' => 'attribute_value']
			$operator = '=';
			/** @var string $attribute_name */
			$attribute_name = array_key_first($condition);
			$attribute_value = $condition[$attribute_name];
		}

		//вероятно, придётся изменить сериализацию на json $query->andWhere(['=', 'json', new ArrayExpression(['foo' => 'bar'])])
		return ['and', [DynamicAttributesAliases::fieldName('attribute_name') => $attribute_name], [$operator, DynamicAttributesValues::fieldName('value'), $attribute_value]];
	}
}