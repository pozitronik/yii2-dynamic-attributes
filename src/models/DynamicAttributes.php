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
	 * Для класса либо экземпляра класса возвращает зарегистрированный алиас.
	 * @param string|ActiveRecordInterface $model Класс модели либо экземпляр модели, для которой нужно вернуть алиас
	 * @return null|string Найденный алиас, null, если отсутствует
	 * @throws Throwable
	 */
	private static function alias(string|ActiveRecordInterface $model):?string {
		return is_string($model)
			?static::getClassAlias($model)
			:static::getClassAlias($model::class);
	}

	/**
	 * Удостоверяется (и, при необходимости, создаёт) динамический атрибут указанного типа, привязанный к модели
	 * @param string|ActiveRecordInterface $model
	 * @param string $attribute_name
	 * @param int|null $type
	 * @return static
	 * @throws Throwable
	 */
	public static function ensureAttribute(string|ActiveRecordInterface $model, string $attribute_name, ?int $type = null):static {
		$attributes = [
			'alias_id' => DynamicAttributesAliases::ensureAlias(static::alias($model))->id,
			'attribute_name' => $attribute_name,

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
	 * Возвращает список известных атрибутов
	 * @param string|ActiveRecordInterface $model
	 * @return array
	 * @throws Throwable
	 */
	public static function listAttributes(ActiveRecordInterface|string $model):array {
		return ArrayHelper::getColumn(static::find()
			->joinWith(['relatedDynamicAttributesAliases'])
			->select(['attribute_name'])
			->where([DynamicAttributesAliases::fieldName('alias') => static::alias($model)])
			->asArray()
			->all(), 'attribute_name');
	}

	/**
	 * Возвращает список известных атрибутов в формате [название => тип]
	 * @param string|ActiveRecordInterface $model
	 * @return array
	 * @throws Throwable
	 */
	public static function getAttributesTypes(ActiveRecordInterface|string $model):array {
		return ArrayHelper::map(static::find()
			->joinWith(['relatedDynamicAttributesAliases'])
			->select([static::fieldName('attribute_name as name'), static::fieldName('type as type')])
			->where([DynamicAttributesAliases::fieldName('alias') => static::alias($model)])
			->asArray()
			->all(), 'name', 'type');
	}

	/**
	 * Возвращает тип атрибута, null если тип неизвестен, или атрибут отсутствует
	 * @param string|ActiveRecordInterface $model
	 * @param string $attribute_name
	 * @return int|null
	 * @throws Throwable
	 */
	public static function attributeType(ActiveRecordInterface|string $model, string $attribute_name):?int {
		/** @var static|null $found */
		return (null === $found = static::find()
				->joinWith(['relatedDynamicAttributesAliases'])
				->where([
					DynamicAttributesAliases::fieldName('alias') => static::alias($model),
					static::fieldName('attribute_name') => $attribute_name
				])->one())?null:$found->type;
	}

	/**
	 * Возвращает значение динамического атрибута по имени
	 * @param ActiveRecordInterface $model
	 * @param string $attribute_name
	 * @return mixed
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributeValue(ActiveRecordInterface $model, string $attribute_name):mixed {
		/**
		 * Выборка напрямую по JSON не имеет смысла при работе через ActiveQuery, поскольку Yii не создаст никакого
		 * "псевдополя" для значения. Логичнее вытащить его из массива/
		 **/
		return ArrayHelper::getValue(static::getAttributesValues($model), $attribute_name);
	}

	/**
	 * Возвращает значения всех динамических атрибутов
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributesValues(ActiveRecordInterface $model):array {
		return (DynamicAttributesValues::find()
				->select('attributes_values')
				->joinWith(['relatedDynamicAttributesAliases'])
				->where([DynamicAttributesAliases::fieldName('alias') => static::getClassAlias($model::class)])
				->andWhere([DynamicAttributesValues::fieldName('model_id') => static::extractKey($model)])
				->one())?->attributes_values??[];
	}

	/**
	 * Присвоить динамические атрибуты списком
	 * @param ActiveRecordInterface $model
	 * @param array $attributes Устанавливаемые атрибуты в формате [имя => значение]
	 * @return void
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function setAttributesValues(ActiveRecordInterface $model, array $attributes):void {
		$model_id = static::extractKey($model);
		foreach ($attributes as $name => $value) {
			$alias_id = static::ensureAttribute($model, $name, static::getType($value))->alias_id;
			DynamicAttributesValues::setAttributesValue($alias_id, $model_id, $name, $value);
		}
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return void
	 * @throws Throwable
	 */
	public static function deleteValues(ActiveRecordInterface $model):void {
		DynamicAttributesValues::deleteAll([
			DynamicAttributesValues::fieldName('id') => static::find()
				->joinWith(['relatedDynamicAttributesAliases'])
				->select([static::fieldName('id')])
				->where([DynamicAttributesAliases::fieldName('alias') => static::getClassAlias($model::class)]),
			DynamicAttributesValues::fieldName('model_id') => static::extractKey($model)
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
	 * Выковыривает из модели её первичный ключ
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
	 * Установить динамически алиас для класса $className
	 * @param string $className
	 * @param null|string $alias
	 * @return void
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function setClassAlias(string $className, ?string $alias = null):void {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
		$alias = $alias??$className;
		ArrayHelper::setValue(self::$_modelsAliases, $className, $alias);
	}

	/**
	 * Вернуть класс для известного алиаса, null, если алиас неизвестен
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
	 * Вернуть алиас зарегистрированного класса, null, если отсутствует
	 * @param string $className
	 * @return string|null
	 * @throws Throwable
	 */
	public static function getClassAlias(string $className):?string {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', self::$_modelsAliases);
		return ArrayHelper::getValue(self::$_modelsAliases, $className);
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