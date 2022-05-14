<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use ArrayObject;
use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributesAliases;
use pozitronik\dynamic_attributes\models\AttributesStorage;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use pozitronik\traits\traits\ActiveRecordTrait;
use Throwable;
use TypeError;
use yii\base\InvalidConfigException;
use yii\base\InvalidConfigException as InvalidConfigExceptionAlias;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\SafeValidator;
use yii\validators\Validator;

/**
 * Trait DynamicAttributesTrait
 * @property-read DynamicAttributesAliases $relatedDynamicAttributesAliases
 * @property-read DynamicAttributes[] $relatedDynamicAttributes Связь к таблице атрибутов
 * @property-read DynamicAttributesValues[] $relatedDynamicAttributesValues Связь к таблице значений атрибутов
 * @property-read array $dynamicAttributes Список динамических атрибутов модели
 * @property-read array $dynamicAttributesValues Массив значений динамических атрибутов в формате имя-значение
 * @property-read array $dynamicAttributesTypes Массив типов динамических атрибутов в формате имя-тип
 */
trait DynamicAttributesTrait {
	use ActiveRecordTrait;

	/**
	 * @var AttributesStorage|null Объект динамических атрибутов модели
	 */
	private ?AttributesStorage $_dynamicAttributesStorage = null;
	/**
	 * @var array The dynamic attributes names may contain any identifiers (even whole sentences up to 255 chars),
	 * they can not be supported by some Yii2 generators (like Html attributes identifiers generator).
	 * Therefore dynamic attributes names are dynamically replaced by short aliases.
	 */
	private $_dynamicAttributesAliases = [];

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		$this->_dynamicAttributesStorage = new AttributesStorage();
		$this->reloadDynamicAttributes();
	}

	/**
	 * @inheritDoc
	 */
	public function afterFind():void {
		parent::afterFind();
		$this->reloadDynamicAttributes();
	}

	/**
	 * @inheritDoc
	 */
	public function afterRefresh():void {
		parent::afterRefresh();
		$this->reloadDynamicAttributes();
	}

	/**
	 * @return void
	 * @throws Throwable
	 * @throws InvalidConfigExceptionAlias
	 */
	private function reloadDynamicAttributes():void {
		/*empty attributes + filled attributes*/
		$allAttributes = array_merge(array_fill_keys(DynamicAttributes::listAttributes($this), null), DynamicAttributes::getAttributesValues($this));
		$this->_dynamicAttributesStorage->loadAttributes($allAttributes);
		$this->_dynamicAttributesAliases = DynamicAttributes::getDynamicAttributesAliasesMap($this);
	}

	/**
	 * @inheritDoc
	 */
	public function delete():void {
		if (false !== parent::delete()) {
			DynamicAttributes::deleteValues($this);
			$this->reloadDynamicAttributes();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function save($runValidation = true, $attributeNames = null):bool {
		if (parent::save($runValidation, $attributeNames)) {
			DynamicAttributes::setAttributesValues($this, $this->_dynamicAttributesStorage->attributes);
			$this->reloadDynamicAttributes();
			return true;
		}
		return false;
	}

	/**
	 * inheritDoc
	 * Добавляет к прописанным в модели валидаторам валидаторы для алиасов динамических атрибутов
	 */
	public function createValidators():ArrayObject {
		$validators = parent::createValidators();
		foreach ($this->_dynamicAttributesAliases as $attribute => $alias) {
			$validators->append($this->getDynamicAttributeValidator($attribute, $alias));
		}
		return $validators;
	}

	/**
	 * @param string $attribute
	 * @param string|null $alias
	 * @return Validator
	 * @throws Throwable
	 * todo: валидация может (и должна) происходить в AttributesStorage
	 */
	public function getDynamicAttributeValidator(string $attribute, ?string $alias = null):Validator {
		$alias = $alias??$attribute;
		return match (DynamicAttributes::attributeType($this, $attribute)) {
			DynamicAttributes::TYPE_BOOL => Validator::createValidator(BooleanValidator::class, $this, [$alias], []),
			DynamicAttributes::TYPE_INT => Validator::createValidator(NumberValidator::class, $this, [$alias], ['integerOnly' => true]),
			default => Validator::createValidator(SafeValidator::class, $this, [$alias], []),
		};
	}

	/**
	 * see Component::__get()
	 * @inheritDoc
	 */
	public function __get($name):mixed {
		/*Запрос атрибута по алиасу имени атрибута*/
		if (false !== $attributeName = array_search($name, $this->_dynamicAttributesAliases, true)) {
			return $this->$attributeName;
		}
		if ($this->hasDynamicAttribute($name)) {
			return $this->_dynamicAttributesStorage->$name;
		}
		return parent::__get($name);
	}

	/**
	 * @see Component::__set()
	 * @inheritDoc
	 */
	public function __set($name, $value):void {
		if (false !== $attributeName = array_search($name, $this->_dynamicAttributesAliases, true)) {
			$this->$attributeName = $value;
		} elseif (false !== $knownType = $this->getDynamicAttributeType($name)) {
			if (null !== $knownType && null !== $value && DynamicAttributes::getType($value) !== $knownType) {//тип значения известен, но не совпадает с ранее указанным
				if (!$this->getDynamicAttributeValidator($name)->validate($value) || !DynamicAttributes::castTo($knownType, $value)) {//значение не может быть провалидировано или приведено к известному типу
					throw new TypeError(DynamicAttributes::TYPE_ERROR_TEXT);
				}
			}
			$this->_dynamicAttributesStorage->$name = $value;
		} else {
			try {
				parent::__set($name, $value);
			} /** @noinspection PhpRedundantCatchClauseInspection Не прокинуто во фреймворке */ catch (UnknownPropertyException $exception) {
				if (DynamicAttributesModule::param('allowRuntimeAttributes', true)) {//todo: документировать
					$this->addDynamicAttribute($name, DynamicAttributes::getType($value));
					$this->_dynamicAttributesStorage->$name = $value;
				} else {
					throw $exception;
				}

			}

		}
	}

	/**
	 * @see Component::__isset()
	 * @inheritDoc
	 */
	public function __isset($name) {
		return parent::__isset($name);
	}

	/**
	 * @return array
	 * @throws Throwable
	 */
	public function getDynamicAttributes():array {
		return DynamicAttributes::listAttributes($this);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasDynamicAttribute(string $name):bool {
		return (null !== $this->_dynamicAttributesStorage && $this->_dynamicAttributesStorage->hasAttribute($name));
	}

	/**
	 * Возвращает тип проверяемого атрибута (из зарегистрированных)
	 * @param string $name
	 * @return false|int|null false: атрибута не существует, null: тип не известен, int: id типа
	 * @throws Throwable
	 */
	public function getDynamicAttributeType(string $name):null|false|int {
		if (null === $this->_dynamicAttributesStorage || !$this->_dynamicAttributesStorage->hasAttribute($name)) return false;
		return DynamicAttributes::attributeType($this, $name);
	}

	/**
	 * @param string $name
	 * @param int|null $type
	 * @param bool|null $index True: создать индекс на атрибут (если поддерживается), null: по настройке из конфига
	 * @return void
	 * @throws Throwable
	 */
	public function addDynamicAttribute(string $name, ?int $type = null, ?bool $index = null):void {
		$this->_dynamicAttributesStorage->defineAttribute($name);
		DynamicAttributes::ensureAttribute($this, $name, $type, $index);
	}

	/**
	 * @return array
	 */
	public function getDynamicAttributesValues():array {
		return $this->_dynamicAttributesStorage->attributes;
	}

	/**
	 * @return array
	 * @throws Throwable
	 */
	public function getDynamicAttributesTypes():array {
		return DynamicAttributes::getAttributesTypes($this::class);
	}

	/**
	 * @return ActiveQuery
	 * @throws Throwable
	 */
	public function getRelatedDynamicAttributesAliases():ActiveQuery {
		return $this->hasMany(DynamicAttributesAliases::class, [])->onCondition(['alias' => DynamicAttributes::getClassAlias($this::class)]);
	}

	/**
	 * @return ActiveQuery
	 * @throws Throwable
	 */
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributes::class, ['alias_id' => 'id'])->via('relatedDynamicAttributesAliases');
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesValues():ActiveQuery {
		return $this->hasMany(DynamicAttributesValues::class, ['alias_id' => 'id'])
			->via('relatedDynamicAttributesAliases')
			->andOnCondition(new Expression(DynamicAttributesValues::fieldName('model_id').' = '.static::fieldName('id')));
	}

}