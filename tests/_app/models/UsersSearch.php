<?php
declare(strict_types = 1);

namespace _app\models;

use app\models\Users;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * Class UsersSearch
 */
class UsersSearch extends Users {

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [['username', 'login', 'password'], 'safe'];
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
			'attributes' => [
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

			]
		]);

		$this->load($params);
		$query->andFilterWhere([static::fieldName('id') => $this->id]);
		$query->andFilterWhere(['like', static::fieldName('username'), $this->username]);
		$query->andFilterWhere(['like', static::fieldName('login'), $this->login]);

//		foreach (DynamicAttributes::getAttributesTypes(Users::class) as $name => $type) {
//			switch ($type) {
//				case DynamicAttributes::TYPE_BOOL:
//				case DynamicAttributes::TYPE_INT:
//				case DynamicAttributes::TYPE_DOUBLE:
//					$query->andFilterWhere(Adapter::adaptWhere([$name => $this->{TemporaryHelper::GetDynamicAttributeAlias(DynamicAttributes::listAttributes(Users::class), $name)}]));
//				break;
//				case DynamicAttributes::TYPE_STRING:
//					$query->andFilterWhere(Adapter::adaptWhere(['like', $name, $this->{TemporaryHelper::GetDynamicAttributeAlias(DynamicAttributes::listAttributes(Users::class), $name)}]));
//				break;
//			}
//		}
		Yii::debug($dataProvider->query->createCommand()->rawSql, 'sql');
		return $dataProvider;
	}
}