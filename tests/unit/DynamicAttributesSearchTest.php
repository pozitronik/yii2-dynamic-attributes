<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use app\models\UsersSearch;
use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\helpers\Utils;

/**
 * Class DynamicAttributesSearchTest
 */
class DynamicAttributesSearchTest extends Unit {

	/**
	 * @inheritDoc
	 */
	protected function _before() {
		/**
		 * Динамически регистрируем алиас класса. Проверить:
		 * 1) Работу класса без регистрации.
		 */
		DynamicAttributes::setClassAlias(Users::class, 'users');

		$testTypes = ['тип1', 'тип2', 'тип3', null];
		$testSP= ['Штат', 'Офис', null, 'Шлёпа', 'USA'];
		$tIndex = 0;
		$sIndex = 0;
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser($i)->saveAndReturn();
			$user->Тип = $testTypes[$tIndex++];
			$user->{'Структурная принадлежность'} = $testSP[$sIndex++];
			$user->{'Код компании'} = $i;
			$user->cbo = Utils::random_str(255);
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
		$dataProvider = $searchModel->search([]);

		self::assertCount($dataProvider->pagination->pageSize, $dataProvider->models);
	}

}