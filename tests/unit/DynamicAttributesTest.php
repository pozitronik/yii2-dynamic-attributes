<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\sys_options\models\DynamicAttributes;

/**
 * Class DynamicAttributesTest
 */
class DynamicAttributesTest extends Unit {

	public function testDynamicAttributes():void {
		$user = Users::CreateUser()->saveAndReturn();
		DynamicAttributes::setModelAlias(Users::class, 'users');
		$userDynamicAttributesModel = DynamicAttributes::init($user);
		$userDynamicAttributesModel->addAttribute('weight', DynamicAttributes::TYPE_INT);
		$userDynamicAttributesModel->addAttribute('sex', DynamicAttributes::TYPE_BOOL);
		$userDynamicAttributesModel->addAttribute('memo about', DynamicAttributes::TYPE_STRING);

		$user->weight = 100;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';

		self::assertEquals('100', $user->weight);
		self::assertTrue($user->sex);
		self::assertEquals('user memo', $user->{'memo about'});

		self::assertEquals('100', $user->getDynamicAttribute('weight'));
		self::assertTrue($user->getDynamicAttribute('sex'));
		self::assertEquals('user memo', $user->getDynamicAttribute('memo about'));

		self::assertEquals('100', DynamicAttributes::get($user, 'weight'));
		self::assertTrue(DynamicAttributes::get($user, 'sex'));
		self::assertEquals('user memo', DynamicAttributes::get($user, 'memo about'));

		self::assertEquals([
			'weight' => 100,
			'sex' => true,
			'memo about' => 'user memo'
		], DynamicAttributes::get($user));

		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING
		], DynamicAttributes::list(Users::class));

	}

}