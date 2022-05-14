<?php /** @noinspection PhpFieldImmediatelyRewrittenInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use DummyClass;
use pozitronik\dynamic_attributes\helpers\ArrayHelper;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\traits\DynamicAttributesTrait;
use Throwable;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecordInterface;
use yii\db\Exception;

/**
 * Class DynamicAttributesTest
 * Важно: тест будет выполняться при очистке БД на каждом шаге.
 */
class DynamicAttributesTest extends Unit {

	/**
	 * name => type
	 */
	private const DYNAMIC_ATTRIBUTES = [
		'weight' => DynamicAttributes::TYPE_INT,
		'sex' => DynamicAttributes::TYPE_BOOL,
		'memo about' => DynamicAttributes::TYPE_STRING,
		'кириллическое имя' => null,
		",./;'[]\\-=" => DynamicAttributes::TYPE_FLOAT,
		"" => DynamicAttributes::TYPE_STRING,
		"<foo val=“bar” />" => DynamicAttributes::TYPE_STRING,
		"❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙" => DynamicAttributes::TYPE_STRING,
		"Ṱ̺̺̕o͞ ̷i̲̬͇̪͙n̝̗͕v̟̜̘̦͟o̶̙̰̠kè͚̮̺̪̹̱̤ ̖t̝͕̳̣̻̪͞h̼͓̲̦̳̘̲e͇̣̰̦̬͎ ̢̼̻̱̘h͚͎͙̜̣̲ͅi̦̲̣̰̤v̻͍e̺̭̳̪̰-m̢iͅn̖̺̞̲̯̰d̵̼̟͙̩̼̘̳ ̞̥̱̳̭r̛̗̘e͙p͠r̼̞̻̭̗e̺̠̣͟s̘͇̳͍̝͉e͉̥̯̞̲͚̬͜ǹ̬͎͎̟̖͇̤t͍̬̤͓̼̭͘ͅi̪̱n͠g̴͉ ͏͉ͅc̬̟h͡a̫̻̯͘o̫̟̖͍̙̝͉s̗̦̲.̨̹͈̣" => DynamicAttributes::TYPE_STRING,
		"𝕋𝕙𝕖 𝕢𝕦𝕚𝕔𝕜 𝕓𝕣𝕠𝕨𝕟 𝕗𝕠𝕩 𝕛𝕦𝕞𝕡𝕤 𝕠𝕧𝕖𝕣 𝕥𝕙𝕖 𝕝𝕒𝕫𝕪 𝕕𝕠𝕘" => DynamicAttributes::TYPE_STRING,
		"<img src=x onerror=\\x00\"javascript:alert(1)\">" => DynamicAttributes::TYPE_STRING,
		"(ﾉಥ益ಥ）ﾉ﻿ ┻━┻" => DynamicAttributes::TYPE_STRING,
	];

	/**
	 * @param ActiveRecordInterface|DynamicAttributesTrait $model
	 * @param int|null $limit
	 * @return void
	 * @noinspection PhpSameParameterValueInspection
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	private static function fillAttributes(ActiveRecordInterface $model, int $limit = null):void {
		$attributes = (null !== $limit)
			?self::DYNAMIC_ATTRIBUTES
			:array_slice(self::DYNAMIC_ATTRIBUTES, 0, $limit, true);
		foreach ($attributes as $name => $type) {
			$model->addDynamicAttribute($name, $type);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function _before():void {
		/**
		 * Динамически регистрируем алиас класса.
		 */
		DynamicAttributes::setClassAlias(Users::class, 'users');
	}

	/**
	 * Тестирование работы с алиасами классов
	 * @return void
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function testDynamicAttributesModelsAliases():void {
		/*Динамическая регистрация алиаса*/
		self::assertEquals(Users::class, DynamicAttributes::getAliasClass('users'));
		self::assertEquals('users', DynamicAttributes::getClassAlias(Users::class));
		/*Несуществующий алиас*/
		self::assertNull(DynamicAttributes::getAliasClass('unknown'));
		/*Проверим регистрацию через конфиг*/
		self::assertEquals(DummyClass::class, DynamicAttributes::getAliasClass('dummy'));
	}

	/**
	 * Регистрация динамических атрибутов класса заранее
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testDynamicAttributesRegistration():void {
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);
		static::fillAttributes($user);
		self::assertTrue(ArrayHelper::isEqual(self::DYNAMIC_ATTRIBUTES, $user->dynamicAttributesTypes));

		$user->weight = 85;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';
		$user->{'кириллическое имя'} = '𐐜 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐙𐐊𐐡𐐝𐐓/𐐝𐐇𐐗𐐊𐐤𐐔 𐐒𐐋𐐗 𐐒𐐌 𐐜 𐐡𐐀𐐖𐐇𐐤𐐓𐐝 𐐱𐑂 𐑄 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐏𐐆𐐅𐐤𐐆𐐚𐐊𐐡𐐝𐐆𐐓𐐆';
		$user->{",./;'[]\\-="} = 3.1415926535897;
		$user->{"❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙"} = "🐵 🙈 🙉 🙊";

		$user->save();

		/** @var Users $newUserModel */
		$newUserModel = Users::find()->where(['id' => $user->id])->one();

		self::assertTrue(ArrayHelper::isEqual([
			'weight' => 85,
			'sex' => true,
			'memo about' => 'user memo',
			'кириллическое имя' => '𐐜 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐙𐐊𐐡𐐝𐐓/𐐝𐐇𐐗𐐊𐐤𐐔 𐐒𐐋𐐗 𐐒𐐌 𐐜 𐐡𐐀𐐖𐐇𐐤𐐓𐐝 𐐱𐑂 𐑄 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐏𐐆𐐅𐐤𐐆𐐚𐐊𐐡𐐝𐐆𐐓𐐆',
			",./;'[]\\-=" => 3.1415926535897,
			"❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙" => "🐵 🙈 🙉 🙊",
			"(ﾉಥ益ಥ）ﾉ﻿ ┻━┻" => null,
			"<foo val=“bar” />" => null,
			"<img src=x onerror=\\x00\"javascript:alert(1)\">" => null,
			"" => null,
			"Ṱ̺̺̕o͞ ̷i̲̬͇̪͙n̝̗͕v̟̜̘̦͟o̶̙̰̠kè͚̮̺̪̹̱̤ ̖t̝͕̳̣̻̪͞h̼͓̲̦̳̘̲e͇̣̰̦̬͎ ̢̼̻̱̘h͚͎͙̜̣̲ͅi̦̲̣̰̤v̻͍e̺̭̳̪̰-m̢iͅn̖̺̞̲̯̰d̵̼̟͙̩̼̘̳ ̞̥̱̳̭r̛̗̘e͙p͠r̼̞̻̭̗e̺̠̣͟s̘͇̳͍̝͉e͉̥̯̞̲͚̬͜ǹ̬͎͎̟̖͇̤t͍̬̤͓̼̭͘ͅi̪̱n͠g̴͉ ͏͉ͅc̬̟h͡a̫̻̯͘o̫̟̖͍̙̝͉s̗̦̲.̨̹͈̣" => null,
			"𝕋𝕙𝕖 𝕢𝕦𝕚𝕔𝕜 𝕓𝕣𝕠𝕨𝕟 𝕗𝕠𝕩 𝕛𝕦𝕞𝕡𝕤 𝕠𝕧𝕖𝕣 𝕥𝕙𝕖 𝕝𝕒𝕫𝕪 𝕕𝕠𝕘" => null
		], $newUserModel->dynamicAttributesValues));

	}

	/**
	 * Регистрация атрибутов при первом обращении к ним
	 * @return void
	 * @throws Exception
	 */
	public function testDynamicAttributesOnTheFly():void {
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);

		$user->weight = 85;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';
		$user->{'кириллическое имя'} = '𐐜 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐙𐐊𐐡𐐝𐐓/𐐝𐐇𐐗𐐊𐐤𐐔 𐐒𐐋𐐗 𐐒𐐌 𐐜 𐐡𐐀𐐖𐐇𐐤𐐓𐐝 𐐱𐑂 𐑄 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐏𐐆𐐅𐐤𐐆𐐚𐐊𐐡𐐝𐐆𐐓𐐆';
		$user->{",./;'[]\\-="} = 3.1415926535897;
		$user->{"❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙"} = "🐵 🙈 🙉 🙊";

		$user->save();

		/** @var Users $newUserModel */
		$newUserModel = Users::find()->where(['id' => $user->id])->one();

		self::assertTrue(ArrayHelper::isEqual([
			'weight' => 85,
			'sex' => true,
			'memo about' => 'user memo',
			'кириллическое имя' => '𐐜 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐙𐐊𐐡𐐝𐐓/𐐝𐐇𐐗𐐊𐐤𐐔 𐐒𐐋𐐗 𐐒𐐌 𐐜 𐐡𐐀𐐖𐐇𐐤𐐓𐐝 𐐱𐑂 𐑄 𐐔𐐇𐐝𐐀𐐡𐐇𐐓 𐐏𐐆𐐅𐐤𐐆𐐚𐐊𐐡𐐝𐐆𐐓𐐆',
			",./;'[]\\-=" => 3.1415926535897,
			"❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙" => "🐵 🙈 🙉 🙊"
		], $newUserModel->dynamicAttributesValues));

	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function testDynamicAttributes():void {
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);

		$user->weight = 100;
		$user->sex = true;
		$user->{'memo about'} = 'user memo';
		$user->some_dynamic_attribute = 500;
		/*Возможно платформозависимое поведение. Представление чисел с плавающей запятой зависит от платформы и поддержки БД*/
		$user->some_float_attribute = (float)(5 / 7);
		/*float <=> double <=> real*/
		$user->some_double_attribute = (double)(8 / 7);

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
		], $user->dynamicAttributesValues);

		/*Получение всех атрибутов из хранилища*/
		self::assertTrue(ArrayHelper::isEqual([
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
			'some_float_attribute' => DynamicAttributes::TYPE_FLOAT,
			'some_double_attribute' => DynamicAttributes::TYPE_FLOAT
		], $user->dynamicAttributesTypes);

		/*Получение списка известных атрибутов из хранилища*/
		self::assertEquals([
			'weight' => DynamicAttributes::TYPE_INT,
			'sex' => DynamicAttributes::TYPE_BOOL,
			'memo about' => DynamicAttributes::TYPE_STRING,
			'some_dynamic_attribute' => DynamicAttributes::TYPE_INT,
			'some_float_attribute' => DynamicAttributes::TYPE_FLOAT,
			'some_double_attribute' => DynamicAttributes::TYPE_FLOAT
		], DynamicAttributes::getAttributesTypes(Users::class));

		self::assertEquals('100', $newUserModel->weight);
		self::assertTrue($newUserModel->sex);
		self::assertEquals('user memo', $newUserModel->{'memo about'});
		self::assertEquals(500, $newUserModel->some_dynamic_attribute);
		self::assertEquals(0.71428571428571, $newUserModel->some_float_attribute);
		self::assertEquals(1.1428571428571, $newUserModel->some_double_attribute);

		$secondUser = Users::CreateUser()->saveAndReturn();
		self::assertTrue(ArrayHelper::isEqual(
			['weight', 'sex', 'memo about', 'some_dynamic_attribute', 'some_float_attribute', 'some_double_attribute'],
			$secondUser->dynamicAttributes,
			ArrayHelper::FLAG_COMPARE_VALUES
		));

		$secondUser->delete();
		self::assertTrue(ArrayHelper::isEqual([
			'weight' => null,
			'sex' => null,
			'memo about' => null,
			'some_dynamic_attribute' => null,
			'some_float_attribute' => null,
			'some_double_attribute' => null
		], $secondUser->dynamicAttributesValues));
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function testDynamicAttributesTypeError():void {
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);
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
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);
		$this->expectExceptionObject(new UnknownPropertyException('Getting unknown property: app\models\Users::unknown_attribute'));
		/** @noinspection PhpUnusedLocalVariableInspection */
		$a = $user->unknown_attribute;
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testDynamicAttributesActiveQuery():void {
		/*Нафигачим моделей*/
		$searchDataWadawada = ['foo', 'bar', 'baz', 'literally', null, 'frog', 'dude', 'aaz'];
		$searchDataBububu = [4, 8, 15, 16, 23, 42, 108, null];
		$wIndex = 0;
		$bIndex = 0;
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser()->saveAndReturn();
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
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => 'frog']))
			->all()
		);
		/*Можно и так*/
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere([Adapter::adaptField('wadawada', Users::class) => 'frog'])
			->all()
		);
		/*%like%*/
		self::assertCount(26, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', 'ba']))
			->all()
		);
		/*%like*/
		self::assertCount(25, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', '%az', false]))
			->all()
		);
		/*%like*/
		self::assertCount(26, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['ilike', 'wadawada', 'ba%', false]))
			->all()
		);
		/*is not set*/
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => null]))
			->all());
		/*same*/
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'wadawada', null]))
			->all()
		);
		/*is set*/
		self::assertCount(88, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is not', 'wadawada', null]))
			->all()
		);
		/*in*/
		self::assertCount(25, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['wadawada' => ['dude', 'literally']]))
			->all()
		);

		/*Выборки по целочисленным динамическим полям*/
		/*сравнение*/
		self::assertCount(13, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => 16]))
			->all()
		);
		/*> <*/
		self::assertCount(24, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['>', 'bububu', 16]))
			->andWhere(Adapter::adaptWhere(['<', 'bububu', 108]))
			->all()
		);
		/*!=*/
		self::assertCount(76, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['!=', 'bububu', 42]))
			->all()
		);
		/*in*/
		self::assertCount(25, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => [16, 23]]))
			->all()
		);
		/*is not set*/
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['bububu' => null]))
			->all()
		);
		/*same*/
		self::assertCount(12, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['is', 'bububu', null]))
			->all()
		);
		/*is not null*/
		self::assertCount(88, Users::find()
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
		self::assertCount(0, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['pipi' => null]))
			->all()
		);
		/*null*/
		self::assertCount(0, Users::find()
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
			->andWhere(Adapter::adaptWhere(['fluffy' => 13.0]))
			->orWhere(Adapter::adaptWhere(['ilike', 'fluffy', '8.1428571428571%', false]))//из-за разницы в формате PHP/PGSQL приходится искать так
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
			->andWhere(Adapter::adaptWhere(['not ilike', 'fluffy', '13.142857142857%', false]))
			->all()
		);
		/*in*/
		//todo: PHP подставит обрезанные значения, в БД нужно их искать как ilike%, а adaptWhere пока этого не умеет
		/*self::assertCount(2, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['fluffy' => [1.1428571428571428, 14.285714285714286, 7]]))
			->all()
		);*/
		/*is not set*/
		self::assertCount(0, Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->andWhere(Adapter::adaptWhere(['fluffy' => null]))
			->all()
		);
		/*same*/
		self::assertCount(0, Users::find()
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
		self::assertEquals(2.8571428571428, $sortedByFloat[20]->fluffy);
		self::assertEquals(7, $sortedByFloat[49]->fluffy);
		self::assertEquals(14.142857142857, $sortedByFloat[99]->fluffy);

		/** @var Users[] $sortedByInt */
		$sortedByInt = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->orderBy([Adapter::adaptField('bububu') => SORT_ASC])//если класс не указать, то сортировка произойдёт без типизации -> т.е. в алфавитном порядке
			->all();

		self::assertEquals(108, $sortedByInt[11]->bububu);
		self::assertEquals(15, $sortedByInt[24]->bububu);
		self::assertEquals(4, $sortedByInt[56]->bububu);
		self::assertNull($sortedByInt[99]->bububu);

		$sortedByInt = Users::find()
			->joinWith(['relatedDynamicAttributesValues'])
			->orderBy([Adapter::adaptField('bububu', $user) => SORT_ASC])//вместо класса можно указать и экземпляр класса
			->all();

		self::assertEquals(15, $sortedByInt[26]->bububu);
		self::assertEquals(15, $sortedByInt[38]->bububu);
		self::assertEquals(16, $sortedByInt[51]->bububu);
		self::assertNull($sortedByInt[99]->bububu);
	}

	/**
	 * Тесты алиасов для атрибутов
	 * @return void
	 * @throws Exception
	 */
	public function testDynamicAttributesAliases():void {
		$user = Users::CreateUser()->saveAndReturn();
		self::assertEquals(1, $user->id);
		self::assertEquals([], $user->dynamicAttributes);
		$user->weight = 1110;
		$user->sex = false;
		$user->{'memo about'} = 'any text';
		$user->save();

		/*Алиасы генерируются автоматически, порядок не гарантируется*/
		self::assertEquals(1110, $user->da2);
		self::assertEquals(false, $user->da1);
		self::assertEquals('any text', $user->da0);

		$user->da2 = 2220;
		$user->da1 = true;
		$user->da0 = 'some other text';

		$user->save();

		self::assertEquals(2220, $user->weight);
		self::assertEquals(true, $user->sex);
		self::assertEquals('some other text', $user->{'memo about'});
		self::assertTrue(ArrayHelper::isEqual(['weight', 'sex', 'memo about'], $user->dynamicAttributes, ArrayHelper::FLAG_COMPARE_VALUES));
	}

}