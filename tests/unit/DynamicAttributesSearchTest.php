<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
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
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser($i)->saveAndReturn();
			$user->Тип = Utils::random_str(10);
			$user->{'Структурная принадлежность'} = Utils::random_str(10);
			$user->{'Код компании'} = rand(1,1000);
			$user->cbo = Utils::random_str(255);
			$user->save();
		}
	}


}