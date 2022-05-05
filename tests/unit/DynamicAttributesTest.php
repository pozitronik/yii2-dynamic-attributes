<?php /** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use DummyClass;
use pozitronik\dynamic_attributes\models\adapters\ConditionAdapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\Exception;

/**
 * Class DynamicAttributesTest
 */
class DynamicAttributesTest extends Unit {

	/**
	 * @inheritDoc
	 */
	protected function _before() {
		/**
		 * Динамически регистрируем алиас класса. Проверить:
		 * 1) Работу класса без регистрации.
		 */
		DynamicAttributes::setClassAlias(Users::class, 'users');
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function testDynamicAttributes():void {

		self::assertEquals(Users::class, DynamicAttributes::getAliasClass('users'));
		self::assertEquals('users', DynamicAttributes::getClassAlias(Users::class));
		self::assertNull(DynamicAttributes::getAliasClass('unknown'));
		/*Проверим регистрацию через конфиг*/
		self::assertEquals(DummyClass::class, DynamicAttributes::getAliasClass('dummy'));

		$user = Users::CreateUser()->saveAndReturn();

		$user->addDynamicAttribute('weight', DynamicAttributes::TYPE_INT);
		$user->addDynamicAttribute('sex', DynamicAttributes::TYPE_BOOL);
		$user->addDynamicAttribute('memo about', DynamicAttributes::TYPE_STRING);

		//todo: unregister

		$user->weight = 100;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';
		/*Type should be autodetected*/
		$user->some_dynamic_attribute = 500;

		$user->save();

		$newUserModel = Users::find()->where(['id' => $user->id])->one();

		/*Динамическое получение атрибутов из модели*/
		self::assertEquals('100', $user->weight);
		self::assertTrue($user->sex);
		self::assertEquals('user memo', $user->{'memo about'});
		self::assertEquals(500, $user->some_dynamic_attribute);

		/*Статическое получение атрибутов из хранилища*/
		self::assertEquals('100', DynamicAttributes::getAttributeValue($user, 'weight'));
		self::assertTrue(DynamicAttributes::getAttributeValue($user, 'sex'));
		self::assertEquals('user memo', DynamicAttributes::getAttributeValue($user, 'memo about'));
		self::assertEquals(500, DynamicAttributes::getAttributeValue($user, 'some_dynamic_attribute'));

		/*Получение всех атрибутов из модели*/
		self::assertEquals([
			'weight' => 100,
			'sex' => true,
			'memo about' => 'user memo',
			'some_dynamic_attribute' => 500
		], $user->getDynamicAttributesValues());

		/*Получение всех атрибутов из хранилища*/
		self::assertEquals([], array_diff([//array_diff чтобы не сортировать
			'weight' => 100,
			'sex' => true,
			'memo about' => 'user memo',
			'some_dynamic_attribute' => 500
		], DynamicAttributes::getAttributesValues($user)));

		/*Получение списка известных атрибутов из хранилища*/
		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING,
			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT
		], $user->getDynamicAttributesTypes());

		/*Получение списка известных атрибутов из хранилища*/
		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING,
			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT
		], DynamicAttributes::getAttributesTypes(Users::class));

		self::assertEquals('100', $newUserModel->weight);
		self::assertTrue($newUserModel->sex);
		self::assertEquals('user memo', $newUserModel->{'memo about'});
		self::assertEquals(500, $user->some_dynamic_attribute);

		$secondUser = Users::CreateUser(2)->saveAndReturn();
		self::assertEquals([], array_diff(['weight', 'sex', 'memo about', 'some_dynamic_attribute'], $secondUser->getDynamicAttributes()));

		$secondUser->delete();
		self::assertEquals([], array_diff(['weight' => null, 'sex' => null, 'memo about' => null, 'some_dynamic_attribute' => null], $secondUser->getDynamicAttributesValues()));
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function testDynamicAttributesTypeError():void {
		$user = Users::CreateUser(3)->saveAndReturn();
		$user->weight = 100;
		/*Assigning string value to int property should create an error*/
		$this->expectError();
		$user->weight = 'fat';
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function testUnknownAttributeError():void {
		$user = Users::CreateUser(4)->saveAndReturn();
		$this->expectExceptionObject(new UnknownPropertyException('Getting unknown property: app\models\Users::unknown_attribute'));
		/** @noinspection PhpUnusedLocalVariableInspection */
		$a = $user->unknown_attribute;
	}

	public function testDynamicAttributesSearch():void {
		/*Нафигачим моделей*/
		DynamicAttributes::setClassAlias(Users::class, 'users');
		$searchDataWadawada = ['foo', 'bar', 'baz', 'literally', 'frog', 'dude', 'aaz'];
		$searchDataBububu = [4, 8, 15, 16, 23, 42, 108];
		$wIndex = 0;
		$bIndex = 0;
		for ($i = 5; $i < 105; $i++) {
			$user = Users::CreateUser($i)->saveAndReturn();
			$user->wadawada = $searchDataWadawada[$wIndex++];//strings
			$user->bububu = $searchDataBububu[$bIndex++];//integers
			$user->pipi = 0 === $i % 2;//booleans
			if ($wIndex >= count($searchDataWadawada)) $wIndex = 0;
			if ($bIndex >= count($searchDataBububu)) $bIndex = 0;
			$user->save();
		}
		/*Выборки по строковыми динамическим полям*/
		/*сравнение*/
		self::assertCount(14, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['wadawada' => 'frog']))
			->all()
		);
		/*%like%*/
		self::assertCount(29, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['ilike', 'wadawada', 'ba']))
			->all()
		);
		/*%like*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['ilike', 'wadawada', '%az', false]))
			->all()
		);
		/*%like*/
		self::assertCount(29, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['ilike', 'wadawada', 'ba%', false]))
			->all()
		);
		/*is not set*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['wadawada' => null]))
			->all());
		/*same*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is', 'wadawada', null]))
			->all()
		);
		/*is set*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is not', 'wadawada', null]))
			->all()
		);
		/*in*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['wadawada' => ['dude', 'literally']]))
			->all()
		);

		/*Выборки по целочисленным динамическим полям*/
		/*сравнение*/
		self::assertCount(14, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['bububu' => 16]))
			->all()
		);
		/*> <*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['>', 'bububu', 16]))
			->andWhere(ConditionAdapter::adapt(['<', 'bububu', 108]))
			->all()
		);
		/*!=*/
		self::assertCount(86, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['!=', 'bububu', 42]))
			->all()
		);
		/*in*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['bububu' => [16, 23]]))
			->all()
		);
		/*is not set*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['bububu' => null]))
			->all()
		);
		/*same*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is', 'bububu', null]))
			->all()
		);
		/*is not null*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is not', 'bububu', null]))
			->all()
		);

		/*Выборки по логическим динамическим полям*/
		/*=*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['pipi' => true]))
			->all()
		);
		/*not*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['!=', 'pipi', true]))
			->all()
		);
		/*not*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['pipi' => false]))
			->all()
		);
		/*null*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['pipi' => null]))
			->all()
		);
		/*null*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is', 'pipi', null]))
			->all()
		);
		/*not null*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(ConditionAdapter::adapt(['is not', 'pipi', null]))
			->all()
		);
	}

}