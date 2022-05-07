<?php
declare(strict_types = 1);

namespace app\models;

use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\traits\DynamicAttributesSearchTrait;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * Class UsersSearch
 */
class UsersSearch extends Users {
	use DynamicAttributesSearchTrait;

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['username', 'login', 'password'], 'safe'],
			[$this->_dynamicAttributesAliases, 'safe']
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 */
	public function search(array $params):ActiveDataProvider {
		$query = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->active();

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);

		$dataProvider->setSort([
			'defaultOrder' => ['id' => SORT_ASC],
			'attributes' => array_merge([
				'id' => [
					'asc' => [Users::fieldName('id') => SORT_ASC],
					'desc' => [Users::fieldName('id') => SORT_DESC]
				],
				'username' => [
					'asc' => [Users::fieldName('username') => SORT_ASC],
					'desc' => [Users::fieldName('username') => SORT_DESC]
				],
				'login' => [
					'asc' => [Users::fieldName('login') => SORT_ASC],
					'desc' => [Users::fieldName('login') => SORT_DESC]
				]
			], $this->dynamicAttributesSort())
		]);

		$this->load($params);
		$query->andFilterWhere([static::fieldName('id') => $this->id]);
		$query->andFilterWhere(['like', static::fieldName('username'), $this->username]);
		$query->andFilterWhere(['like', static::fieldName('login'), $this->login]);

		foreach (DynamicAttributes::getAttributesTypes(parent::class) as $name => $type) {
			switch ($type) {
				case DynamicAttributes::TYPE_BOOL:
				case DynamicAttributes::TYPE_INT:
				case DynamicAttributes::TYPE_DOUBLE:
					$query->andFilterWhere(Adapter::adaptWhere([$name => $this->{$this->_dynamicAttributesAliases[$name]}]));
				break;
				case DynamicAttributes::TYPE_STRING:
					$query->andFilterWhere(Adapter::adaptWhere(['like', $name, $this->{$this->_dynamicAttributesAliases[$name]}]));
				break;
			}
		}
		Yii::debug($dataProvider->query->createCommand()->rawSql, 'sql');
		return $dataProvider;
	}

}