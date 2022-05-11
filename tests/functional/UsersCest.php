<?php
declare(strict_types = 1);

use app\models\Users;
use yii\base\Exception as BaseException;
use yii\helpers\ArrayHelper;

/**
 * Class ManagersCest
 */
class UsersCest {

	/**
	 * @param FunctionalTester $I
	 * @throws BaseException
	 */
	public function create(FunctionalTester $I):void {
		$user = Users::CreateUser()->saveAndReturn();

		$I->amLoggedInAs($user);
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		$I->submitForm("#users-create", [
			'Users' => [
				'username' => 'Test Successful',
			]
		]);
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		$I->assertCount(1, Users::find()->all());
		$model = Users::findOne(['username' => 'Test Successful']);
		$I->assertNotNull($model);
	}
}
