<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use yii\base\NotSupportedException;
use yii\data\ActiveDataProvider;

/**
 * Class DynamicAttributesSearch
 * @property-read null|int $count This attribute records count
 * @property null|string $alias Set of class aliases
 * @property null|string $attribute Attribute name
 * @property null|int[] $types Set of types
 * // * @property null|bool $indexed Is attribute indexed? todo
 */
class DynamicAttributesSearch extends DynamicAttributes {

	public $alias = null;
	public $attribute = null;
	public $types = null;
//	public $indexed = null;

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['attribute'], 'string'],
			[['alias', 'types'], 'safe']
//			[['indexed'], 'boolean']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels():array {
		return array_merge(parent::attributeLabels(), [
			'alias' => 'Алиас',
			'attribute' => 'Атрибут',
			'types' => 'Тип',
			'count' => 'Записи',
			'indexed' => 'Индекс'
		]);
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 * @throws NotSupportedException
	 */
	public function search(array $params):ActiveDataProvider {
		$query = self::find()->active();

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);

		$dataProvider->setSort([
			'defaultOrder' => ['id' => SORT_ASC],
		]);

		$this->load($params);

		if (!$this->validate()) return $dataProvider;

		$query->andFilterWhereRelation([DynamicAttributesAliases::fieldName('alias') => $this->alias], 'relatedDynamicAttributesAliases');
		$query->andFilterWhere(['like', static::fieldName('attribute_name'), $this->attribute]);
		$query->andFilterWhere([static::fieldName('type') => $this->types]);

		return $dataProvider;
	}
}