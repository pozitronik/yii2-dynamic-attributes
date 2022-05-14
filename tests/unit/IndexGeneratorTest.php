<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use Exception;
use pozitronik\dynamic_attributes\helpers\ArrayHelper;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\widgets\grid\DynamicAttributesGrid;
use pozitronik\helpers\Utils;
use Throwable;
use Yii;
use yii\console\widgets\Table;
use yii\db\Exception as DbException;
use yii\helpers\Console;

/**
 * Class IndexGeneratorTest
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
	 * Количество записей, на которых будут прогоняться тесты. Для теста работоспособности можно установить
	 * минимальное значение, для тестов производительности - повысить его.
	 */
	public const TESTING_RECORDS_CNT = 10000;
	/**
	 * Количество повторяющихся поисковых запросов на каждый тип атрибута.
	 */
	public const TESTING_SEARCH_REPEATS = 100;

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
	 * @return array
	 * @throws Exception
	 */
	private static function getRandomArray():array {
		$randomArray = range(0, random_int(0, 100));
		shuffle($randomArray);
		return $randomArray;
	}

	/**
	 * @param int $rowsCount
	 * @return void
	 * @throws Exception
	 */
	private static function fillData(int $rowsCount = 1000):void {
		Console::startProgress(0, $rowsCount);
		for ($rowCount = 0; $rowCount < $rowsCount; $rowCount++) {
			$user = Users::CreateUser();
			foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
				$user->$attributeName = match ($attributeType) {
					DynamicAttributes::TYPE_BOOL => (bool)(random_int(0, 100) % 2),
					DynamicAttributes::TYPE_INT => mt_rand(),
					DynamicAttributes::TYPE_FLOAT => mt_rand() / mt_rand(),
					DynamicAttributes::TYPE_STRING => Utils::random_str(random_int(0, 100)),
					DynamicAttributes::TYPE_ARRAY => static::getRandomArray(),
					default => null
				};
			}
			$user->save();
			Console::updateProgress($rowCount, $rowsCount);
		}
		Console::endProgress();
	}

	/**
	 * Тестирует создание индексированных полей с последующим их заполнением
	 * @return void
	 * @throws Throwable
	 * @skip
	 */
	public function testIndexCreation():void {
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::ensureAttribute(Users::class, $attributeName, $attributeType, true);
		}

		$startTime = microtime(true);
		static::fillData(self::TESTING_RECORDS_CNT);
		$finalTime = microtime(true) - $startTime;
		Console::output(sprintf("Write %d records to %s table take %s", self::TESTING_RECORDS_CNT, Console::renderColoredString("%rindexed%n"), Console::renderColoredString("%g{$finalTime} sec%n")));

	}

	/**
	 * Тестирует создание неиндексированных полей с последующим их заполнением и генерацией индексов
	 * @return void
	 * @throws Throwable
	 * @throws DbException
	 * @skip
	 */
	public function testIndexGeneration():void {
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::ensureAttribute(Users::class, $attributeName, $attributeType, false);
		}
		$startTime = microtime(true);
		static::fillData();
		$finalTime = microtime(true) - $startTime;
		Console::output(sprintf("Write %d records to %s table take %s", self::TESTING_RECORDS_CNT, Console::renderColoredString("%runindexed%n"), Console::renderColoredString("%g{$finalTime} sec%n")));
		/*измеряем скорость поиска без индексов*/
		Console::output(Console::renderColoredString("%gSearch time tests on unindexed table...%n"));
		static::measureSearchTime(self::TESTING_SEARCH_REPEATS);

		$startTime = microtime(true);
		foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
			DynamicAttributes::indexAttribute(Users::class, $attributeName, $attributeType);
		}
		$finalTime = microtime(true) - $startTime;
		Console::output(sprintf("Index creation on %d records take %s", self::TESTING_RECORDS_CNT, Console::renderColoredString("%g{$finalTime} sec%n")));
		/*измеряем скорость поиска с индексами*/
		Console::output(Console::renderColoredString("%gSearch time tests on indexed table...%n"));
		static::measureSearchTime(self::TESTING_SEARCH_REPEATS);
	}

	/**
	 * @param int $repeats
	 * @return void
	 * @throws Throwable
	 */
	public static function measureSearchTime(int $repeats = 10):void {
		$results = [];
		Console::startProgress(0, $repeats);
		for ($c = 0; $c < $repeats; $c++) {
			foreach (static::DYNAMIC_ATTRIBUTES as $attributeName => $attributeType) {
				$randomValue = match ($attributeType) {
					DynamicAttributes::TYPE_BOOL => (bool)(random_int(0, 100) % 2),
					DynamicAttributes::TYPE_INT => mt_rand(),
					DynamicAttributes::TYPE_FLOAT => mt_rand() / mt_rand(),
					DynamicAttributes::TYPE_STRING => Utils::random_str(random_int(0, 100)),
					DynamicAttributes::TYPE_ARRAY => static::getRandomArray(),
					default => null
				};
				$startTime = microtime(true);
				Users::find()
					->joinWith(['relatedDynamicAttributesValues'])
					->where([Adapter::jsonFieldName($attributeName, $attributeType) => $randomValue])->all();
				$results[$attributeType][$c] = microtime(true) - $startTime;//время поиска
			}
			Console::updateProgress($c, $repeats);
		}
		Console::endProgress();
		$rows = [];
		foreach ($results as $attributeType => $measures) {
			$rows[] = [
				DynamicAttributesGrid::GetAttributeTypeLabel($attributeType),//name
				array_sum($measures),//total
				array_sum($measures) / count($measures),//average
				min($measures),//min
				max($measures),//max
			];
		}
		$summary = ArrayHelper::getColumn($rows, "1");
		$rows[] = [
			Console::renderColoredString("%rСуммарно%n"),
			array_sum($summary),
			array_sum($summary) / (count($summary) * count($measures)),
		];

		Console::output("Search time summary after {$repeats} repeats, seconds:");
		Console::output(Table::widget([
			'headers' => ['attribute type', 'total time', 'average', 'min', 'max'],
			'rows' => $rows
		]));
	}

}