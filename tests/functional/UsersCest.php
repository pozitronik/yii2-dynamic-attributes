<?php
declare(strict_types = 1);

use app\models\Users;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use yii\base\Exception as BaseException;

/**
 * Class ManagersCest
 */
class UsersCest {

	/**
	 * @param FunctionalTester $I
	 * @throws BaseException
	 */
	public function create(FunctionalTester $I):void {
		DynamicAttributes::setClassAlias(Users::class, 'users');
		$user = Users::CreateUser()->saveAndReturn();
		$user->addDynamicAttribute('weight', DynamicAttributes::TYPE_INT);
		$user->addDynamicAttribute('sex', DynamicAttributes::TYPE_BOOL);
		$user->addDynamicAttribute('кириллическое имя', DynamicAttributes::TYPE_STRING);

		$I->amLoggedInAs($user);
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		$I->submitForm("#users-create", [
			'Users' => [
				'username' => 'Test Successful',
				'login' => 'test_user_2',
				'password' => '123',
				'da2' => 'abyrvalg',//кириллическое имя
				'da0' => true,//sex,
				'da1' => 42//weight
			]
		]);
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		$I->assertCount(2, Users::find()->all());
		$model = Users::findOne(['username' => 'Test Successful']);
		$I->assertNotNull($model);
		$I->assertEquals(2, $model->id);
		$I->assertEquals('test_user_2', $model->login);
		$I->assertEquals('123', $model->password);
		$I->assertEquals('abyrvalg', $model->{'кириллическое имя'});
		$I->assertEquals(true, $model->sex);
		$I->assertEquals(42, $model->weight);
	}
}
