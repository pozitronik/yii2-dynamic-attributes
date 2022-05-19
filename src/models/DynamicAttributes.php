<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributes as DynamicAttributesAR;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;
use yii\db\Exception;

/**
 * Class DynamicAttributes
 * @property null|string $alias The alias for current model
 */
class DynamicAttributes extends DynamicAttributesAR {

	public const TYPE_BOOL = 1;
	public const TYPE_INT = 2;
	public const TYPE_FLOAT = 3;
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
	 * @inheritDoc
	 */
	public function rules():array {
		return array_merge_recursive(parent::rules(), [
			[['alias'], 'string']
		]);
	}

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
	 * @param bool|null $index True: создать индекс на атрибут (если поддерживается), null: по настройке из конфига
	 * @return null|static null, если алиас не зарегистрирован
	 * @throws Throwable
	 */
	public static function ensureAttribute(string|ActiveRecordInterface $model, string $attribute_name, ?int $type = null, ?bool $index = null):?static {
		if (null === $alias_id = DynamicAttributesAliases::ensureAlias(static::alias($model))?->id) return null;
		$attributes = compact('alias_id', 'attribute_name');

		/** @var null|static $currentAttribute */
		$currentAttribute = static::find()->where($attributes)->one();

		if (null === $type) {//если атрибут пришёл без типа, попытаемся определить тип по уже сохранённым атрибутам
			$type = $currentAttribute?->type;
		}
		if (null === $currentAttribute) {//атрибута с таким именем не существует
			$attributes['type'] = $type;
			$currentAttribute = static::Upsert($attributes);
			if (true === ($index??DynamicAttributesModule::param('createIndexes', false))) {//todo: сейчас инициализируется без использования в модуле
				static::indexAttribute($model, $attribute_name, $type, $currentAttribute->alias_id);
			}
		} elseif (null === $currentAttribute->type) {//атрибут существует, но тип неизвестен -> установим тип
			$currentAttribute->type = $type;
			$currentAttribute->save();
		}
//		elseif ($currentAttribute->type !== $type) {//типы не совпадают - ничего не делаем, полагаясь на последующую конвертацию валидаторами
//			throw new TypeError(static::TYPE_ERROR_TEXT);//различия в текущем и сохранённом типах данных
//		}

		return $currentAttribute;
	}

	/**
	 * Создать индекс по динамическому полю
	 * @param string|ActiveRecordInterface $model
	 * @param string $attribute_name
	 * @param int|null $type
	 * @param int|null $alias_id
	 * @return void
	 * @throws Exception
	 */
	public static function indexAttribute(string|ActiveRecordInterface $model, string $attribute_name, ?int $type = null, ?int $alias_id = null):void {
		$model::getDb()->createCommand()->setRawSql(Adapter::indexOnJsonField($attribute_name, $type, $alias_id))->execute();
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
			->select([static::fieldName('attribute_name as attribute_name'), static::fieldName('type as type')])
			->where([DynamicAttributesAliases::fieldName('alias') => static::alias($model)])
			->all(), 'attribute_name', 'type');
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
		if (null === $alias_id = DynamicAttributesAliases::ensureAlias(static::alias($model))?->id) return;//алиас не зарегистрирован
		$model_id = static::extractKey($model);
		foreach ($attributes as $name => $value) {
			static::ensureAttribute($model, $name, static::getType($value));
		}
		DynamicAttributesValues::setAttributesValues($alias_id, $model_id, $attributes);
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
	 * @return string[]|null
	 */
	public static function getModelsAliases():array {
		return self::$_modelsAliases??[];
	}

	/**
	 * @return string[]|null
	 */
	public static function getAliasesList():array {
		$values = array_values(static::getModelsAliases());
		return array_combine($values, $values);
	}

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', static::$_modelsAliases);
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
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', static::$_modelsAliases);
		$alias = $alias??$className;
		ArrayHelper::setValue(static::$_modelsAliases, $className, $alias);
	}

	/**
	 * Вернуть класс для известного алиаса, null, если алиас неизвестен
	 * @param string $alias
	 * @return null|string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAliasClass(string $alias):?string {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', static::$_modelsAliases);
		return (false === $class = array_search($alias, static::$_modelsAliases, true))?null:$class;
	}

	/**
	 * Вернуть алиас зарегистрированного класса, null, если отсутствует
	 * @param string $className
	 * @return string|null
	 * @throws Throwable
	 */
	public static function getClassAlias(string $className):?string {
		static::$_modelsAliases ??= DynamicAttributesModule::param('models', static::$_modelsAliases);
		return ArrayHelper::getValue(static::$_modelsAliases, $className);
	}

	/**
	 * @return string[]
	 * NULL не включаем, это не фактический тип
	 */
	public static function typesList():array {
		return [
			static::TYPE_BOOL => 'Логическое значение',
			static::TYPE_INT => 'Целочисленное значение',
			static::TYPE_FLOAT => 'Значение с плавающей точкой',
			static::TYPE_STRING => 'Строка',
			static::TYPE_ARRAY => 'Массив',
			static::TYPE_OBJECT => 'Объект',
			static::TYPE_RESOURCE => 'Ресурс',
			static::TYPE_UNKNOWN => 'Тип не установлен',
			static::TYPE_RESOURCE_CLOSED => 'Закрытый ресурс'
		];
	}

	/**
	 * @param mixed $variable
	 * @return int|null
	 */
	public static function getType(mixed $variable):?int {
		return match (gettype($variable)) {
			"boolean" => static::TYPE_BOOL,
			"integer" => static::TYPE_INT,
			"double" => static::TYPE_FLOAT,
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
	 * Привести значение $value к типу $typeId
	 * @param int $typeId
	 * @param $value
	 * @return bool Возможность совершить преобразование
	 */
	public static function castTo(int $typeId, &$value):bool {
		try {
			$value = match ($typeId) {
				static::TYPE_BOOL => (bool)$value,
				static::TYPE_INT => (int)$value,
				static::TYPE_FLOAT => (float)$value,
				static::TYPE_STRING => (string)$value,
				static::TYPE_ARRAY => (array)$value,
				static::TYPE_OBJECT => (object)$value,
			};
		} catch (Throwable $e) {
			return false;
		}
		return true;
	}

	/**
	 * Генерирует алиас каждого атрибута для обхода проблем со «странными» именами атрибутов. Обращение к алиасу равнозначно обращению к атрибуту
	 * @param ActiveRecordInterface|string $model
	 * @return array [attribute name => attribute alias]
	 * @throws Throwable
	 */
	public static function getDynamicAttributesAliasesMap(ActiveRecordInterface|string $model):array {
		$attributes = static::listAttributes($model);
		$old_attributes = $attributes;
		array_walk($attributes, static fn(&$value, $key) => $value = 'da'.$key);
		return array_combine($old_attributes, $attributes);
	}

	/**
	 * @return string|null
	 */
	public function getAlias():?string {
		return $this->relatedDynamicAttributesAliases?->alias;
	}

	/**
	 * @param string|null $alias
	 */
	public function setAlias(?string $alias):void {
		$this->alias_id = DynamicAttributesAliases::ensureAlias($alias)?->id;
	}

}