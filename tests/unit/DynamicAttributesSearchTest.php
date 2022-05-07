<?php /** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use app\models\UsersSearch;
use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\helpers\Utils;
use Yii;

/**
 * Class DynamicAttributesSearchTest
 */
class DynamicAttributesSearchTest extends Unit {

	/**
	 * @inheritDoc
	 */
	protected function _before():void {
		/**
		 * Динамически регистрируем алиас класса. Проверить:
		 * 1) Работу класса без регистрации.
		 */
		DynamicAttributes::setClassAlias(Users::class, 'users');

		$testTypes = ['тип1', 'тип2', 'тип3', null];
		$testSP = ['Штат', 'Офис', null, 'Шлёпа', 'USA'];
		$tIndex = 0;
		$sIndex = 0;
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser($i)->saveAndReturn();
			$user->Тип = $testTypes[$tIndex++];//da3
			/** @noinspection PhpFieldImmediatelyRewrittenInspection */
			$user->{'Структурная принадлежность'} = $testSP[$sIndex++];//da2
			$user->{'Код компании'} = $i;//da1
			$user->cbo = Utils::random_str(255);//da0
			$user->save();
			if ($tIndex >= count($testTypes)) $tIndex = 0;
			if ($sIndex >= count($testSP)) $sIndex = 0;
		}
	}

	/**
	 * @return void
	 */
	public function testDynamicAttributesSearch():void {
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search(['UsersSearch' => ['id' => 1]]);

		self::assertCount(1, $dataProvider->models);
		self::assertEquals(1, $dataProvider->totalCount);

		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search(['UsersSearch' => ['da3' => 'тип3']]);
		self::assertEquals(25, $dataProvider->totalCount);
		self::assertEquals(2, $dataProvider->models[0]->id);

		$searchModel = new UsersSearch();
		/* \yii\data\Sort::getAttributeOrders() всегда загружает атрибуты сортировки из запроса, если он установлен. Передавать атрибут сортировки в запрос нельзя, только так*/
		Yii::$app->request->setQueryParams(['dp-2-sort' => 'da2']);//see BaseDataProvider::$id - каждый новый датапровайдер будет инкрементить значение
		$dataProvider = $searchModel->search(['UsersSearch' => ['da3' => 'тип2']]);
		self::assertEquals(25, $dataProvider->totalCount);
		self::assertEquals(89, $dataProvider->models[0]->id);

		$searchModel = new UsersSearch();
		Yii::$app->request->setQueryParams(['dp-3-sort' => '-da2']);
		$dataProvider = $searchModel->search(['UsersSearch' => ['da3' => 'тип2']]);
		self::assertEquals(25, $dataProvider->totalCount);
		self::assertEquals(97, $dataProvider->models[0]->id);
	}

}