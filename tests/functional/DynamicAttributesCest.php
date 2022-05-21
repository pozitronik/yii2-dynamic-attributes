<?php
declare(strict_types = 1);

namespace functional;

use app\models\Dummy;
use app\models\Users;
use Codeception\Exception\ModuleException;
use Exception;
use FunctionalTester;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
use yii\base\InvalidConfigException;

/**
 * Class DynamicAttributesCest
 */
class DynamicAttributesCest {

	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws ModuleException
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function createAndUpdateAndDelete(FunctionalTester $I):void {
		$user = Users::CreateUser()->saveAndReturn();
		$I->assertCount(0, DynamicAttributes::listAttributes(null, true));
		$I->amLoggedInAs($user);
		$I->amOnRoute('dynamic_attributes/default/create');
		$I->seeResponseCodeIs(200);
		$I->submitForm("#dynamic_attributes-create", [
			'DynamicAttributes' => [
				'alias' => 'dummy',
				'attribute_name' => 'new_attribute',
				'type' => DynamicAttributes::TYPE_BOOL
			]
		]);
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('dynamic_attributes/default');
		$I->assertCount(1, DynamicAttributes::listAttributes(Dummy::class));
		$I->assertEquals(['new_attribute'], DynamicAttributes::listAttributes(Dummy::class));
		$I->assertEquals(['new_attribute' => DynamicAttributes::TYPE_BOOL], DynamicAttributes::getAttributesTypes(Dummy::class));

		$I->amOnRoute('dynamic_attributes/default/update?id=1');
		$I->seeResponseCodeIs(200);
		$I->seeInFormFields("#dynamic_attributes-edit", [
			'DynamicAttributes' => [
				'alias' => 'dummy',
				'attribute_name' => 'new_attribute',
				'type' => DynamicAttributes::TYPE_BOOL
			]
		]);

		$I->submitForm("#dynamic_attributes-edit", [
			'DynamicAttributes' => [
				'alias' => 'dummy',
				'attribute_name' => 'changed_attribute',
				'type' => DynamicAttributes::TYPE_STRING
			]
		]);
		$I->seeResponseCodeIs(200);

		$I->seeInCurrentUrl('dynamic_attributes/default');
		$I->assertCount(1, DynamicAttributes::listAttributes(Dummy::class));
		$I->assertEquals(['changed_attribute'], DynamicAttributes::listAttributes(Dummy::class));
		$I->assertEquals(['changed_attribute' => DynamicAttributes::TYPE_STRING], DynamicAttributes::getAttributesTypes(Dummy::class));

		$I->amOnRoute('dynamic_attributes/default/delete?id=1');
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('dynamic_attributes/default');
		$I->assertCount(0, DynamicAttributes::listAttributes(Dummy::class));
		$I->assertEquals([], DynamicAttributes::listAttributes(Dummy::class));
	}

}