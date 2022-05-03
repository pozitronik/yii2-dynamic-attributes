<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use pozitronik\dynamic_attributes\models\AttributesStorage;
use pozitronik\dynamic_attributes\models\DynamicAttributes;

/**
 * Trait DynamicAttributesTrait
 */
trait DynamicAttributesTrait {
	/**
	 * @var AttributesStorage|null Объект динамических атрибутов модели
	 */
	private ?AttributesStorage $_dynamicAttributesStorage = null;

	public function init() {
		parent::init();
		$this->_dynamicAttributesStorage = AttributesStorage::instance();
		$this->_dynamicAttributesStorage->loadAttributes(DynamicAttributes::getAttributesValues($this));
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
		if ($this->hasDynamicAttribute($name)) {
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

	public function addDynamicAttribute(string $name, ?int $type = null):void {
		$this->_dynamicAttributesStorage->defineAttribute($name);
		DynamicAttributes::ensureAttribute($this, $name, $type);
	}
}