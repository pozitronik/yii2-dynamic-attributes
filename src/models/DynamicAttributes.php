<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributes as DynamicAttributesAR;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;

/**
 * Class DynamicAttributes
 */
class DynamicAttributes extends DynamicAttributesAR {

	public const TYPE_BOOL = 1;
	public const TYPE_INT = 2;
	public const TYPE_DOUBLE = 3;
	public const TYPE_STRING = 4;
	public const TYPE_ARRAY = 5;
	public const TYPE_OBJECT = 6;
	public const TYPE_RESOURCE = 7;
	public const TYPE_NULL = 8;
	public const TYPE_UNKNOWN = 9;
	public const TYPE_RESOURCE_CLOSED = 10;

	/**
	 * @var string[] Перечисление класс модели => используемый алиас
	 */
	private static array $_modelsAliases = [];

	/**
	 * @param ActiveRecordInterface $model
	 * @param string $attribute
	 * @param int|null $type
	 * @return static
	 * @throws Throwable
	 */
	public static function ensureAttribute(ActiveRecordInterface $model, string $attribute, ?int $type = null):static {
		return static::Upsert([
			'model' => static::getClassAlias($model::class),
			'attribute_name' => $attribute,
			'type' => $type
		]);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws Throwable
	 */
	public static function listAttributes(ActiveRecordInterface $model):array {
		return ArrayHelper::getColumn(static::find()
			->select(['attribute_name'])
			->where([static::fieldName('model') => static::getClassAlias($model::class)])
			->asArray()
			->all(), 'attribute_name');
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributesValues(ActiveRecordInterface $model):array {
		return DynamicAttributesValues::find()
			->joinWith(['relatedDynamicAttributes'])
			->where([DynamicAttributes::fieldName('attribute_name') => static::getClassAlias($model::class)])
			->andWhere([static::fieldName('model') => static::extractKey($model)])
			->all();
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @param array $attributes
	 * @return void
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function setAttributesValues(ActiveRecordInterface $model, array $attributes):void {
		$alias = static::getClassAlias($model::class);
		$modelKey = static::extractKey($model);
		foreach ($attributes as $name => $value) {
			$attributeIndex = static::Upsert([
				'model' => $alias,
				'attribute_name' => $name,
				'type' => static::getType($value)
			])->id;

			DynamicAttributesValues::setAttributeValue($attributeIndex, $modelKey, $value);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function init() {
		parent::init();
		self::$_modelsAliases = DynamicAttributesModule::param('models', self::$_modelsAliases);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return null|int
	 * @throws InvalidConfigException
	 */
	private static function extractKey(ActiveRecordInterface $model):?int {
		/** @var array $primaryKeyName */
		$primaryKeyName = $model::primaryKey();
		if (isset($primaryKeyName[0])) {
			$primaryKeyValue = $model->{$primaryKeyName[0]};
			if (is_int($primaryKeyValue) || null === $primaryKeyValue) {
				return $primaryKeyValue;
			}
		}
		throw new InvalidConfigException(sprintf("\"%s\" must have a integer primary key.", $model::class));
	}

	/**
	 * Установить динамически алиас модели
	 * @param string $class
	 * @param null|string $alias
	 * @return void
	 */
	public static function setClassAlias(string $class, ?string $alias = null):void {
		$alias = $alias??$class;
		ArrayHelper::setValue(self::$_modelsAliases, $class, $alias);
	}

	/**
	 * @param string $alias
	 * @return null|string
	 */
	public static function getAliasClass(string $alias):?string {
		return (false === $class = array_search($alias, self::$_modelsAliases, true))?null:$class;
	}

	/**
	 * @param string $class
	 * @return string|null
	 * @throws Throwable
	 */
	public static function getClassAlias(string $class):?string {
		return ArrayHelper::getValue(self::$_modelsAliases, $class);
	}

	/**
	 * @param mixed $variable
	 * @return int|null
	 */
	public static function getType(mixed $variable):?int {
		return match (gettype($variable)) {
			"boolean" => static::TYPE_BOOL,
			"integer" => static::TYPE_INT,
			"double" => static::TYPE_DOUBLE,
			"string" => static::TYPE_STRING,
			"array" => static::TYPE_ARRAY,
			"object" => static::TYPE_OBJECT,
			"resource" => static::TYPE_RESOURCE,
			"NULL" => static::TYPE_NULL,
			"unknown type" => static::TYPE_UNKNOWN,
			"resource (closed)" => static::TYPE_RESOURCE_CLOSED,
			default => null
		};
	}
}