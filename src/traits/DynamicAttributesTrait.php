<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use pozitronik\dynamic_attributes\models\AttributesStorage;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
use TypeError;
use yii\base\InvalidConfigException as InvalidConfigExceptionAlias;

/**
 * Trait DynamicAttributesTrait
 */
trait DynamicAttributesTrait {
	/**
	 * @var AttributesStorage|null Объект динамических атрибутов модели
	 */
	private ?AttributesStorage $_dynamicAttributesStorage = null;

	/**
	 * @inheritDoc
	 */
	public function init() {
		parent::init();
		$this->_dynamicAttributesStorage = new AttributesStorage();
		$this->reloadDynamicAttributes();
	}

	/**
	 * @inheritDoc
	 */
	public function afterFind() {
		parent::afterFind();
		$this->reloadDynamicAttributes();
	}

	/**
	 * @inheritDoc
	 */
	public function afterRefresh() {
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
	public function delete() {
		if (false !== parent::delete()) {
			//todo: delete values
		}
	}

	/**
	 * @inheritDoc
	 */
	public function save($runValidation = true, $attributeNames = null) {
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
			parent::__set($name, $value);
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
}