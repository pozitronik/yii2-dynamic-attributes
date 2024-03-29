<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributes as DynamicAttributesAR;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\caching\TagDependency;
use yii\db\ActiveRecordInterface;
use yii\db\Exception;

/**
 * Class DynamicAttributes
 * @property null|string $alias The alias for current model
 */
class DynamicAttributes extends DynamicAttributesAR {

	/*null обрабатывается отдельно: воспринимаем его не как тип, а как метку «тип неизвестен», и пытаемся определить
	тип либо по существующим записям, либо при будущих записях*/
	public const TYPE_NULL = null;
	public const TYPE_BOOL = 1;
	public const TYPE_INT = 2;
	public const TYPE_FLOAT = 3;
	public const TYPE_STRING = 4;
	public const TYPE_ARRAY = 5;
	public const TYPE_OBJECT = 6;
	public const TYPE_RESOURCE = 7;
	public const TYPE_UNKNOWN = 9;
	public const TYPE_RESOURCE_CLOSED = 10;

	/**
	 * @var null|string[] Перечисление класс модели => используемый алиас. null до инициализации.
	 */
	private static ?array $_modelsAliases = null;

	public const TYPE_ERROR_TEXT = 'Attribute type does not match with previous';

	/*Список ключей кеша в формате операция - ключ с подстановкой. Предполагается, что ключ будут локально подстанавливаться какие-то идентификаторы, см. по реализациям*/
	private const CACHE_IDENTIFIERS = [
		'listAttributes' => "%s::listAttributes(%s)",
		'getAttributesTypes' => "%s::getAttributesTypes(%s)",
		'getAttributeType' => "%s::getAttributeType(%s)",
		'getAttributesValues' => "%s::getAttributesValues(%s)",
		'setAttributesValues' => "%s::setAttributesValues(%s)",
	];

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
		$alias = static::alias($model);
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
			/*Сброс кешей произойдёт также в static::save()*/
			if (true === ($index??DynamicAttributesModule::param('createIndexes', false))) {
				static::indexAttribute($model, $attribute_name, $type, $currentAttribute->alias_id);
			}
		} elseif (null === $currentAttribute->type) {//атрибут существует, но тип неизвестен -> установим тип
			$currentAttribute->type = $type;
			$currentAttribute->save();
			/*Сброс кешей произойдёт также в static::save()*/
			TagDependency::invalidate(Yii::$app->cache, [
				static::GetCacheIdentifier('getAttributeType', $alias, $attribute_name),//Сброс кеша для getAttributesType() по изменению типа атрибута
			]);
		}
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
	 * Возвращает список известных атрибутов для модели
	 * @param string|ActiveRecordInterface|null $model Если null - то все атрибуты всех моделей. Это нужно указывать принудительно
	 * @param bool $refresh Освежить содержимое кеша
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @throws NotSupportedException
	 */
	public static function listAttributes(null|ActiveRecordInterface|string $model, bool $refresh = false):array {
		$alias = null === $model
			?null
			:static::alias($model);
		$resultFn = static fn() => ArrayHelper::getColumn(static::find()
			->select(['attribute_name'])
			->andFilterWhereRelation([
				DynamicAttributesAliases::fieldName('alias') => $alias
			], 'relatedDynamicAttributesAliases')
			->asArray()
			->all(), 'attribute_name');
		if ($refresh) {
			TagDependency::invalidate(Yii::$app->cache, [
				static::GetCacheIdentifier('listAttributes', $alias),//Сброс кеша для listAttributes() по запросу
			]);
		}

		if ((DynamicAttributesModule::param('cacheEnabled', true))) {
			$cacheIdentifier = static::GetCacheIdentifier('listAttributes', $alias);
			return Yii::$app->cache->getOrSet($cacheIdentifier, $resultFn, null, new TagDependency(['tags' => $cacheIdentifier]));//Сброс в static::ensureAttribute()
		}

		return $resultFn();
	}

	/**
	 * Возвращает список известных атрибутов в формате [название => тип]
	 * @param string|ActiveRecordInterface $model
	 * @return array
	 * @throws Throwable
	 */
	public static function getAttributesTypes(ActiveRecordInterface|string $model):array {
		$alias = static::alias($model);
		$resultFn = static fn() => ArrayHelper::map(static::find()
			->joinWith(['relatedDynamicAttributesAliases'])
			->select([static::fieldName('attribute_name as attribute_name'), static::fieldName('type as type')])
			->where([DynamicAttributesAliases::fieldName('alias') => $alias])
			->all(), 'attribute_name', 'type');
		if ((DynamicAttributesModule::param('cacheEnabled', true))) {
			$cacheIdentifier = static::GetCacheIdentifier('getAttributesTypes', $alias);
			return Yii::$app->cache->getOrSet($cacheIdentifier, $resultFn, null, new TagDependency(['tags' => $cacheIdentifier]));//Сброс в static::ensureAttribute()
		}

		return $resultFn();
	}

	/**
	 * Возвращает тип атрибута, null если тип неизвестен, или атрибут отсутствует
	 * @param string|ActiveRecordInterface $model
	 * @param string $attribute_name
	 * @return int|null
	 * @throws Throwable
	 */
	public static function getAttributeType(ActiveRecordInterface|string $model, string $attribute_name):?int {
		$alias = static::alias($model);
		$resultFn = static function() use ($alias, $attribute_name) {
			/** @var static $found */
			return (null === $found = static::find()
					->joinWith(['relatedDynamicAttributesAliases'])
					->where([
						DynamicAttributesAliases::fieldName('alias') => $alias,
						static::fieldName('attribute_name') => $attribute_name
					])->one())
				?null
				:$found->type;
		};
		if ((DynamicAttributesModule::param('cacheEnabled', true))) {
			$cacheIdentifier = static::GetCacheIdentifier('getAttributeType', $alias, $attribute_name);
			return Yii::$app->cache->getOrSet($cacheIdentifier, $resultFn, null, new TagDependency(['tags' => $cacheIdentifier]));//Сброс в static::ensureAttribute()
		}

		return $resultFn();
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
	 * @param bool $refresh
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributesValues(ActiveRecordInterface $model, bool $refresh = false):array {
		$alias = static::getClassAlias($model::class);
		$key = static::extractKey($model);
		$resultFn = static fn() => (DynamicAttributesValues::find()
				->select('attributes_values')
				->joinWith(['relatedDynamicAttributesAliases'])
				->where([DynamicAttributesAliases::fieldName('alias') => $alias])
				->andWhere([DynamicAttributesValues::fieldName('model_id') => $key])
				->one())?->attributes_values??[];
		if ($refresh) {
			TagDependency::invalidate(Yii::$app->cache, [
				static::GetCacheIdentifier('getAttributesValues', $alias, $key),//Сброс кеша для getAttributesValues() по запросу
			]);
		}

		if ((DynamicAttributesModule::param('cacheEnabled', true))) {
			$cacheIdentifier = static::GetCacheIdentifier('getAttributesValues', $alias, $key);
			return Yii::$app->cache->getOrSet($cacheIdentifier, $resultFn, null, new TagDependency(['tags' => $cacheIdentifier]));//Сброс в static::setAttributesValues(), static::deleteValues()
		}

		return $resultFn();
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
		$alias = static::alias($model);
		if (null === $alias_id = DynamicAttributesAliases::ensureAlias($alias)?->id) return;//алиас не зарегистрирован
		$model_id = static::extractKey($model);
		foreach ($attributes as $name => $value) {
			static::ensureAttribute($model, $name, static::getType($value));
			TagDependency::invalidate(Yii::$app->cache, [
				static::GetCacheIdentifier('setAttributesValues', $alias, $name),//Сброс кеша для setAttributesValues() по изменению значений
			]);
		}
		DynamicAttributesValues::setAttributesValues($alias_id, $model_id, $attributes);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return void
	 * @throws Throwable
	 */
	public static function deleteValues(ActiveRecordInterface $model):void {
		$alias = static::getClassAlias($model::class);
		$key = static::extractKey($model);
		DynamicAttributesValues::deleteAll([
			DynamicAttributesValues::fieldName('id') => static::find()
				->joinWith(['relatedDynamicAttributesAliases'])
				->select([static::fieldName('id')])
				->where([DynamicAttributesAliases::fieldName('alias') => $alias]),
			DynamicAttributesValues::fieldName('model_id') => $key
		]);
		TagDependency::invalidate(Yii::$app->cache, [
			static::GetCacheIdentifier('getAttributesValues', $alias, $key),//Сброс кеша для getAttributesValues() по удалению значений
		]);
	}

	/**
	 * @return array
	 */
	public static function getModelsAliases():array {
		return self::$_modelsAliases??[];
	}

	/**
	 * @return array
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
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable $e) {
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

	/**
	 * Генерирует уникальный идентификатор записи в кеше/тега кеша для операции и параметра.
	 * Удобный шорткат для операций, завязанных на алиас класса.
	 * @param string $operation
	 * @param string|null $alias
	 * @param string|null|int $parameter Необязательный идентификатор, например, имя атрибута или ключ модели.
	 * @return string
	 */
	private static function GetCacheIdentifier(string $operation, ?string $alias, null|string|int $parameter = null):string {
		return sprintf(static::CACHE_IDENTIFIERS[$operation], static::class, CacheHelper::MethodParametersSignature([$alias, $parameter]));
	}

	/**
	 * @inheritDoc
	 */
	public function save($runValidation = true, $attributeNames = null):bool {
		$alias = DynamicAttributesAliases::findModel($this->alias_id)?->alias;
		TagDependency::invalidate(Yii::$app->cache, [
			static::GetCacheIdentifier('listAttributes', $alias),//Сброс кеша для listAttributes() по изменению списка атрибутов
			static::GetCacheIdentifier('getAttributesTypes', $alias),//Сброс кеша для getAttributesTypes() по изменению типов атрибутов
		]);
		return parent::save($runValidation, $attributeNames);
	}

	/**
	 * @inheritDoc
	 */
	public function delete() {
		$alias = DynamicAttributesAliases::findModel($this->alias_id)?->alias;
		TagDependency::invalidate(Yii::$app->cache, [
			static::GetCacheIdentifier('listAttributes', $alias),//Сброс кеша для listAttributes() по изменению списка атрибутов
			static::GetCacheIdentifier('getAttributesTypes', $alias),//Сброс кеша для getAttributesTypes() по изменению типов атрибутов
		]);
		return parent::delete();
	}

}