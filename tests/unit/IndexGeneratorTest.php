<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use Exception;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\helpers\Utils;
use Throwable;
use Yii;
use yii\db\Exception as DbException;

/**
 * Class IndexGeneratorTest
 *
 */
class IndexGeneratorTest extends Unit {

	/**
	 * name => type
	 */
	private const DYNAMIC_ATTRIBUTES = [
		'f_boolean' => DynamicAttributes::TYPE_BOOL,
		'f_integer' => DynamicAttributes::TYPE_INT,
		'f_float' => DynamicAttributes::TYPE_FLOAT,
		'f_string' => DynamicAttributes::TYPE_STRING,
		'f_array' => DynamicAttributes::TYPE_ARRAY,
		'f_null' => DynamicAttributes::TYPE_NULL
	];

	/**
	 * @inheritDoc
	 */
	protected function _before():void {
		/*Сбрасывает последовательности в таблицах перед каждым тестом*/
		foreach (['sys_dynamic_attributes_aliases', 'sys_dynamic_attributes', 'sys_dynamic_attributes_values', 'users'] as $tableName) {
			Yii::$app->db
				->createCommand()
				->setRawSql("TRUNCATE TABLE $tableName CASCADE")//независимо от того, выполняется ли тест внутри транзакции, сбросим таблицу
				->execute();
			Yii::$app->db
				->createCommand()->resetSequence($tableName)
				->execute();
		}

		/**
		 * Динамически регистрируем алиас класса.
		 */
		DynamicAttributes::setClassAlias(Users::class, 'users');
	}

	/**
	 * @param int $rowsCount
	 * @return void
	 * @throws Exception
	 */
	private static function fillData(int $rowsCount = 100000):void {
		for ($rowCount = 0; $rowCount < $rowsCount; $rowCount++) {
			$user = Users::CreateUser();
			foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
				$user->$attributeName = match ($attributeType) {
					DynamicAttributes::TYPE_BOOL => random_int(0, 100) % 2,
					DynamicAttributes::TYPE_INT => mt_rand(),
					DynamicAttributes::TYPE_FLOAT => mt_rand() / mt_rand(),
					DynamicAttributes::TYPE_STRING => Utils::random_str(mt_rand()),
					DynamicAttributes::TYPE_ARRAY => static function():array {//проверить возможность вызова
						$x = range(0, random_int(0, 100));
						shuffle($x);
						return $x;
					},
					default => null
				};
				$user->save();
			}

		}
	}

	/**
	 * Тестирует создание индексированных полей с последующим их заполнением
	 * @return void
	 * @throws Throwable
	 */
	public function testIndexCreation():void {
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::ensureAttribute(Users::class, $attributeName, $attributeType, true);
		}
		static::fillData();
	}

	/**
	 * Тестирует создание неиндексированных полей с последующим их заполнением и генерацией индексов
	 * @return void
	 * @throws Throwable
	 * @throws DbException
	 */
	public function testIndexGeneration():void {
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::ensureAttribute(Users::class, $attributeName, $attributeType, false);
		}
		static::fillData();
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::indexAttribute(Users::class, $attributeName, $attributeType);
		}

	}

}