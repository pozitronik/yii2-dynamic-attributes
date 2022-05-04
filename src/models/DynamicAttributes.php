<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributes as DynamicAttributesAR;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use TypeError;
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
	/*null обрабатывается отдельно: воспринимаем его не как тип, а как метку «тип неизвестен», и пытаемся определить
	тип либо по существующим записям, либо при будущих записях*/
	public const TYPE_NULL = null;
	public const TYPE_UNKNOWN = 9;
	public const TYPE_RESOURCE_CLOSED = 10;

	/**
	 * @var null|string[] Перечисление класс модели => используемый алиас. null до инициализации.
	 */
	private static ?array $_modelsAliases = null;

	public const TYPE_ERROR_TEXT = 'Attribute type does not match with previous';

	/**
	 * @param string|ActiveRecordInterface $model
	 * @param string $attribute
	 * @param int|null $type
	 * @return static
	 * @throws Throwable
	 */
	public static function ensureAttribute(string|ActiveRecordInterface $model, string $attribute, ?int $type = null):static {
		$attributes = [
			'model' => is_string($model)?$model:static::getClassAlias($model::class),
			'attribute_name' => $attribute,

		];
		/*Если тип известен, то сверимся с ним*/
		if (null !== $type) {
			$attributes['type'] = $type;
		}
		$ensuredModel = static::Upsert($attributes);
		if ([] !== $ensuredModel->errors) {
			/*Единственная причина, по которой не произойдёт апсерт - различия в текущем и сохранённом типах данных*/
			throw new TypeError(self::TYPE_ERROR_TEXT);
		}
		return $ensuredModel;
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
	 * @param string $attribute
	 * @return mixed
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributeValue(ActiveRecordInterface $model, string $attribute):mixed {
		$rawValue = ArrayHelper::getValue(DynamicAttributesValues::find()
			->select([DynamicAttributesValues::fieldName('value as value')])
			->joinWith(['relatedDynamicAttributes'])
			->where([static::fieldName('model') => static::getClassAlias($model::class)])
			->andWhere([DynamicAttributesValues::fieldName('key') => static::extractKey($model)])
			->andWhere([static::fieldName('attribute_name') => $attribute])
			->asArray()
			->one(), 'value');
		return DynamicAttributesValues::unserializeValue($rawValue);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributesValues(ActiveRecordInterface $model):array {
		$rawValues = ArrayHelper::map(DynamicAttributesValues::find()
			->select([static::fieldName('attribute_name as key'), DynamicAttributesValues::fieldName('value as value')])
			->joinWith(['relatedDynamicAttributes'])
			->where([static::fieldName('model') => static::getClassAlias($model::class)])
			->andWhere([DynamicAttributesValues::fieldName('key') => static::extractKey($model)])
			->asArray()
			->all(), 'key', 'value');
		return array_map(static function($value) {
			return DynamicAttributesValues::unserializeValue($value);
		}, $rawValues);
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
			DynamicAttributesValues::setAttributeValue(static::ensureAttribute($alias, $name, static::getType($value))->id, $modelKey, $value);
		}
	}

	/**
	 * @param string $class
	 * @return array
	 * @throws Throwable
	 */
	public static function getAttributesTypes(string $class):array {
		return ArrayHelper::map(static::find()
			->select([static::fieldName('attribute_name as name'), static::fieldName('type as type')])
			->where([static::fieldName('model') => static::getClassAlias($class)])
			->asArray()
			->all(), 'name', 'type');
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return void
	 * @throws Throwable
	 */
	public static function deleteValues(ActiveRecordInterface $model):void {
		DynamicAttributesValues::deleteAll([DynamicAttributesValues::fieldName('attribute_id') => static::find()
			->select([static::fieldName('id')])
			->where([static::fieldName('model') => static::getClassAlias($model::class)]),
			DynamicAttributesValues::fieldName('key') => static::extractKey($model)
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		self::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
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
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function setClassAlias(string $class, ?string $alias = null):void {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
		$alias = $alias??$class;
		ArrayHelper::setValue(self::$_modelsAliases, $class, $alias);
	}

	/**
	 * @param string $alias
	 * @return null|string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAliasClass(string $alias):?string {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
		return (false === $class = array_search($alias, self::$_modelsAliases, true))?null:$class;
	}

	/**
	 * @param string $class
	 * @return string|null
	 * @throws Throwable
	 */
	public static function getClassAlias(string $class):?string {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
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

	/**
	 * @param ActiveRecordInterface $model
	 * @param string $attribute
	 * @return int|null
	 * @throws Throwable
	 */
	public static function attributeType(ActiveRecordInterface $model, string $attribute):?int {
		/** @var static|null $found */
		return (null === $found = static::find()->where([
				'model' => static::getClassAlias($model::class),
				'attribute_name' => $attribute
			])->one())?null:$found->type;
	}
}