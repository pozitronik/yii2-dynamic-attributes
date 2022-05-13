<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;

/**
 * Class Adapter
 * Общий адаптер, проксирующий вызовы нужному адаптеру
 */
class Adapter implements AdapterInterface {

	/**
	 * Имя используемого драйвера. Если не установлено принудительно, берётся из конфигурации, если не установлено там
	 * - берётся основной драйвер из конфигурации Yii.
	 * @var string|null
	 */
	public static null|string $driverName = null;

	private static null|string $_adapter = null;

	/**
	 * driver name = adapter class
	 */
	public const ADAPTERS = [
		'pgsql' => PgSQLAdapter::class,
		'mysql' => MySQLAdapter::class
	];

	/**
	 * @inheritDoc
	 */
	public static function adaptField(string $jsonFieldName, string|ActiveRecordInterface|null $model = null):string {
		return static::GetAdapter()::adaptField($jsonFieldName, $model);
	}

	/**
	 * @inheritDoc
	 */
	public static function adaptWhere(array $condition):array {
		return static::GetAdapter()::adaptWhere($condition);
	}

	/**
	 * @inheritDoc
	 */
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string {
		return static::GetAdapter()::jsonFieldName($jsonFieldName, $fieldType);
	}

	/**
	 * @return string|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function GetDriverName():?string {
		return static::$driverName ??= DynamicAttributesModule::param('driverName', Yii::$app->db->driverName);//todo документация
	}

	/**
	 * @return AdapterInterface|string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function GetAdapter():string {
		if (null === static::$_adapter ??= ArrayHelper::getValue(static::ADAPTERS, static::GetDriverName())) {
			throw new InvalidConfigException(sprintf("Adapter for %s is not set", static::$driverName));
		}
		return static::$_adapter;
	}

}