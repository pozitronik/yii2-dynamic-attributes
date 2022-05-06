<?php /** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use DummyClass;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
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

		/*Возможно платформозависимое поведение. Представление чисел с плавающей запятой зависит от платформы и поддержки БД*/
		$user->some_float_attribute = (float)5 / 7;
		/*float <=> double <=> real*/
		$user->some_double_attribute = (double)8 / 7;

//		$user->array = ['one', 2, pi()];//todo tests
//		$user->object = $user;//todo tests

		$user->save();

		$newUserModel = Users::find()->where(['id' => $user->id])->one();

		/*Динамическое получение атрибутов из модели*/
		self::assertEquals('100', $user->weight);
		self::assertTrue($user->sex);
		self::assertEquals('user memo', $user->{'memo about'});
		self::assertEquals(500, $user->some_dynamic_attribute);
		self::assertEquals(0.71428571428571, $user->some_float_attribute);
		/*Точность хранения в БД может быть выше, но php использует формат 64 bit IEEE, и отбросит часть, превышающую доступную ему точность*/
		self::assertEquals(1.1428571428571, $user->some_double_attribute);

		/*Статическое получение атрибутов из хранилища*/
		self::assertEquals('100', DynamicAttributes::getAttributeValue($user, 'weight'));
		self::assertTrue(DynamicAttributes::getAttributeValue($user, 'sex'));
		self::assertEquals('user memo', DynamicAttributes::getAttributeValue($user, 'memo about'));
		self::assertEquals(500, DynamicAttributes::getAttributeValue($user, 'some_dynamic_attribute'));
		self::assertEquals(0.71428571428571, DynamicAttributes::getAttributeValue($user, 'some_float_attribute'));
		self::assertEquals(1.1428571428571, DynamicAttributes::getAttributeValue($user, 'some_double_attribute'));

		/*Получение всех атрибутов из модели*/
		self::assertEquals([
			'weight' => 100,
			'sex' => true,
			'memo about' => 'user memo',
			'some_dynamic_attribute' => 500,
			'some_float_attribute' => 0.71428571428571,
			'some_double_attribute' => 1.1428571428571
		], $user->getDynamicAttributesValues());

		/*Получение всех атрибутов из хранилища*/
		self::assertEquals([], array_diff([//array_diff чтобы не сортировать
			'weight' => 100,
			'sex' => true,
			'memo about' => 'user memo',
			'some_dynamic_attribute' => 500,
			'some_float_attribute' => 0.7142857142857,
			'some_double_attribute' => 1.1428571428571
		], DynamicAttributes::getAttributesValues($user)));

		/*Получение списка известных атрибутов из хранилища*/
		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING,
			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT,
			'some_float_attribute' => DynamicAttributes::TYPE_DOUBLE,
			'some_double_attribute' => DynamicAttributes::TYPE_DOUBLE
		], $user->getDynamicAttributesTypes());

		/*Получение списка известных атрибутов из хранилища*/
		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING,
			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT,
			'some_float_attribute' => DynamicAttributes::TYPE_DOUBLE,
			'some_double_attribute' => DynamicAttributes::TYPE_DOUBLE
		], DynamicAttributes::getAttributesTypes(Users::class));

		self::assertEquals('100', $newUserModel->weight);
		self::assertTrue($newUserModel->sex);
		self::assertEquals('user memo', $newUserModel->{'memo about'});
		self::assertEquals(500, $newUserModel->some_dynamic_attribute);
		self::assertEquals(0.71428571428571, $newUserModel->some_float_attribute);
		self::assertEquals(1.1428571428571, $newUserModel->some_double_attribute);

		$secondUser = Users::CreateUser(2)->saveAndReturn();
		self::assertEquals([], array_diff(['weight', 'sex', 'memo about', 'some_dynamic_attribute', 'some_float_attribute', 'some_double_attribute'], $secondUser->getDynamicAttributes()));

		$secondUser->delete();
		self::assertEquals([], array_diff([
			'weight' => null,
			'sex' => null,
			'memo about' => null,
			'some_dynamic_attribute' => null,
			'some_float_attribute' => null,
			'some_double_attribute' => null
		], $secondUser->getDynamicAttributesValues()));
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

	public function testDynamicAttributesActiveQuery():void {
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
			$user->fluffy = (float)($i / 7);//обязательно говорим, что у нас float
			if ($wIndex >= count($searchDataWadawada)) $wIndex = 0;
			if ($bIndex >= count($searchDataBububu)) $bIndex = 0;
			$user->save();
		}
		/*Выборки по строковыми динамическим полям*/
		/*сравнение*/
		self::assertCount(14, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => 'frog']))
			->all()
		);
		/*Можно и так*/
		self::assertCount(14, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere([Adapter::adaptField('wadawada', Users::class) => 'frog'])
			->all()
		);
		/*%like%*/
		self::assertCount(29, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', 'ba']))
			->all()
		);
		/*%like*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', '%az', false]))
			->all()
		);
		/*%like*/
		self::assertCount(29, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', 'ba%', false]))
			->all()
		);
		/*is not set*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => null]))
			->all());
		/*same*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'wadawada', null]))
			->all()
		);
		/*is set*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is not', 'wadawada', null]))
			->all()
		);
		/*in*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => ['dude', 'literally']]))
			->all()
		);

		/*Выборки по целочисленным динамическим полям*/
		/*сравнение*/
		self::assertCount(14, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => 16]))
			->all()
		);
		/*> <*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['>', 'bububu', 16]))
			->andWhere(Adapter::adaptWhere(['<', 'bububu', 108]))
			->all()
		);
		/*!=*/
		self::assertCount(86, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['!=', 'bububu', 42]))
			->all()
		);
		/*in*/
		self::assertCount(28, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => [16, 23]]))
			->all()
		);
		/*is not set*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => null]))
			->all()
		);
		/*same*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'bububu', null]))
			->all()
		);
		/*is not null*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is not', 'bububu', null]))
			->all()
		);

		/*Выборки по логическим динамическим полям*/
		/*=*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['pipi' => true]))
			->all()
		);
		/*not*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['!=', 'pipi', true]))
			->all()
		);
		/*not*/
		self::assertCount(50, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['pipi' => false]))
			->all()
		);
		/*null*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['pipi' => null]))
			->all()
		);
		/*null*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'pipi', null]))
			->all()
		);
		/*not null*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is not', 'pipi', null]))
			->all()
		);

		/*Выборки по нецелочисленным динамическим полям*/
		/*сравнение*/
		self::assertCount(2, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['fluffy' => (float)13]))
			->orWhere(Adapter::adaptWhere(['fluffy' => 8.14285714285714]))
			->all()
		);
		/*> <*/
		self::assertCount(16, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['>', 'fluffy', 8.85714285]))
			->andWhere(Adapter::adaptWhere(['<', 'fluffy', 11.14285714285]))
			->all()
		);
		/*!=*/
		self::assertCount(99, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['!=', 'fluffy', 13.142857142857142]))
			->all()
		);
		/*in*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['fluffy' => [1.1428571428571428, 14.285714285714286, 7]]))
			->all()
		);
		/*is not set*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['fluffy' => null]))
			->all()
		);
		/*same*/
		self::assertCount(3, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'fluffy', null]))
			->all()
		);
		/*is not null*/
		self::assertCount(100, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is not', 'fluffy', null]))
			->all()
		);

		/*Сортировки*/
		$sortedByFloat = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->orderBy([Adapter::adaptField('fluffy', Users::class) => SORT_ASC])//если передать класс, то при сортировке будет учтён зарегистрированный тип поля
			->all();
		self::assertEquals(2.8571428571428, $sortedByFloat[15]->fluffy);
		self::assertEquals(7, $sortedByFloat[44]->fluffy);
		self::assertEquals(14.714285714285, $sortedByFloat[98]->fluffy);
		self::assertNull($sortedByFloat[102]->fluffy);

		$sortedByInt = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->orderBy([Adapter::adaptField('bububu') => SORT_ASC])//если класс не указать, то сортировка произойдёт без типизации -> т.е. в алфавитном порядке
			->all();

		self::assertEquals(108, $sortedByInt[13]->bububu);
		self::assertEquals(15, $sortedByInt[27]->bububu);
		self::assertEquals(4, $sortedByInt[56]->bububu);
		self::assertEquals(96, $sortedByInt[56]->id);
		self::assertNull($sortedByInt[102]->bububu);

		$sortedByInt = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->orderBy([Adapter::adaptField('bububu', $user) => SORT_ASC])//вместо класса можно указать и экземпляр класса
			->all();

		self::assertEquals(15, $sortedByInt[31]->bububu);
		self::assertEquals(15, $sortedByInt[41]->bububu);
		self::assertEquals(16, $sortedByInt[56]->bububu);
		self::assertEquals(29, $sortedByInt[56]->id);
		self::assertNull($sortedByInt[102]->bububu);
	}

}