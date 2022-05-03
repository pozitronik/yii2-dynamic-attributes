<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\dynamic_attributes\models\DynamicAttributes;

/**
 * Class DynamicAttributesTest
 */
class DynamicAttributesTest extends Unit {

	public function testDynamicAttributes():void {
		DynamicAttributes::setClassAlias(Users::class, 'users');
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(Users::class, DynamicAttributes::getAliasClass('users'));
		self::assertEquals('users', DynamicAttributes::getClassAlias(Users::class));
		self::assertNull(DynamicAttributes::getAliasClass('unknown'));

		$user->addDynamicAttribute('weight', DynamicAttributes::TYPE_INT);
		$user->addDynamicAttribute('sex', DynamicAttributes::TYPE_BOOL);
		$user->addDynamicAttribute('memo about', DynamicAttributes::TYPE_STRING);

//		$userDynamicAttributesModel->addAttribute
//		$userDynamicAttributesModel->addAttribute
//		$userDynamicAttributesModel->addAttribute

		$user->weight = 100;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';
		/*Type should be autodetected*/
//		$user->some_dynamic_attribute = 500;

		$user->save();

		$newUserModel = Users::findOne([$user->id]);

		self::assertEquals('100', $user->weight);
//		self::assertTrue($user->sex);
//		self::assertEquals('user memo', $user->{'memo about'});
//		self::assertEquals(500, $user->some_dynamic_attribute);

//		self::assertEquals('100', $user->getDynamicAttribute('weight'));
//		self::assertTrue($user->getDynamicAttribute('sex'));
//		self::assertEquals('user memo', $user->getDynamicAttribute('memo about'));
//		self::assertEquals(500, $user->getDynamicAttribute('some_dynamic_attribute'));
//
//		self::assertEquals('100', DynamicAttributes::get($user, 'weight'));
//		self::assertTrue(DynamicAttributes::get($user, 'sex'));
//		self::assertEquals('user memo', DynamicAttributes::get($user, 'memo about'));
//		self::assertEquals(500, DynamicAttributes::get($user, 'some_dynamic_attribute'));

//		self::assertEquals([
//			'weight' => 100,
//			'sex' => true,
//			'memo about' => 'user memo',
//			'some_dynamic_attribute' => 500
//		], DynamicAttributes::get($user));
//
//		self::assertEquals([
//			'weight' => DynamicAttributes::TYPE_INT,
//			'sex' => DynamicAttributes::TYPE_BOOL,
//			'memo about' => DynamicAttributes::TYPE_STRING,
//			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT
//		], DynamicAttributes::list(Users::class));
//
//		$user->sex = false;
//		$user->saveDynamicAttributes();
//		self::assertFalse($user->sex);

		$newUserModel = Users::findOne([$user->id]);

		self::assertEquals('100', $newUserModel->weight);
		self::assertTrue($newUserModel->sex);
		self::assertEquals('user memo', $newUserModel->{'memo about'});
//		self::assertEquals(500, $user->some_dynamic_attribute);

//		/*Assigning string value to int property should create an error*/
//		$this->expectExceptionObject(new TypeError());
//		$user->weight = 'fat';

		$secondUser = Users::CreateSecondUser()->saveAndReturn();
		self::assertEquals(['weight', 'sex', 'memo about'], $secondUser->getDynamicAttributes());

	}

}