<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\AttributesStorage;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use pozitronik\traits\traits\ActiveRecordTrait;
use Throwable;
use TypeError;
use yii\base\InvalidConfigException as InvalidConfigExceptionAlias;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Trait DynamicAttributesTrait
 * @property-read DynamicAttributes[] $relatedDynamicAttributes Связь к таблице атрибутов
 * @property-read DynamicAttributesValues[] $relatedDynamicAttributesValues Связь к таблице значений атрибутов
 */
trait DynamicAttributesTrait {
	use ActiveRecordTrait;

	/**
	 * @var AttributesStorage|null Объект динамических атрибутов модели
	 */
	private ?AttributesStorage $_dynamicAttributesStorage = null;

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
	}

	/**
	 * @inheritDoc
	 */
	public function delete():void {
		if (false !== parent::delete()) {
			DynamicAttributes::deleteValues($this);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function save($runValidation = true, $attributeNames = null):bool {
		if (parent::save($runValidation, $attributeNames)) {
			DynamicAttributes::setAttributesValues($this, $this->_dynamicAttributesStorage->attributes);
			return true;
		}
		return false;

	}

	/**
	 * see Component::__get()
	 * @inheritDoc
	 */
	public function __get($name):mixed {
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
		if (false !== $knownType = $this->getDynamicAttributeType($name)) {
			if (null !== $knownType && DynamicAttributes::getType($value) !== $knownType) {
				throw new TypeError(DynamicAttributes::TYPE_ERROR_TEXT);
			}
			$this->_dynamicAttributesStorage->$name = $value;
		} else {
			try {
				parent::__set($name, $value);
			} /** @noinspection PhpRedundantCatchClauseInspection Не прокинуто во фреймворке */ catch (UnknownPropertyException $exception) {
				if (DynamicAttributesModule::param('allowRuntimeAttributes', true)) {
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
	 * @return void
	 * @throws Throwable
	 */
	public function addDynamicAttribute(string $name, ?int $type = null):void {
		$this->_dynamicAttributesStorage->defineAttribute($name);
		DynamicAttributes::ensureAttribute($this, $name, $type);
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
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributes::class, [])->onCondition(['model' => DynamicAttributes::getClassAlias($this::class)]);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesValues():ActiveQuery {
		return $this->hasMany(DynamicAttributesValues::class, ['attribute_id' => 'id'])
			->via('relatedDynamicAttributes')
			->andOnCondition(new Expression(DynamicAttributesValues::fieldName('key').' = '.static::fieldName('id')));
	}


}