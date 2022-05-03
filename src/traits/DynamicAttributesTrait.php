<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use pozitronik\dynamic_attributes\models\AttributesStorage;
use pozitronik\dynamic_attributes\models\DynamicAttributes;

/**
 * Trait DynamicAttributesTrait
 * @property null|AttributesStorage $dynamicAttributes
 */
trait DynamicAttributesTrait {
	/**
	 * @var AttributesStorage|null Объект динамических атрибутов модели
	 */
	private ?AttributesStorage $_dynamicAttributes = null;

	public function init() {
		parent::init();
		$this->dynamicAttributes = AttributesStorage::instance();
		$this->dynamicAttributes->loadAttributes(DynamicAttributes::getAttributesValues($this));
	}

	/**
	 * see Component::__get()
	 * @inheritDoc
	 */
	public function __get($name):mixed {
		if ($this->hasDynamicAttribute($name)) {
			return $this->_dynamicAttributes->$name;
		}
		return parent::__get($name);
	}

	/**
	 * @see Component::__set()
	 * @inheritDoc
	 */
	public function __set($name, $value):void {
		if ($this->hasDynamicAttribute($name)) {
			$this->_dynamicAttributes->$name = $value;
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
	 * @return AttributesStorage|null
	 */
	public function getDynamicAttributes():?AttributesStorage {
		return $this->_dynamicAttributes;
	}

	/**
	 * @param AttributesStorage|null $dynamicAttributes
	 */
	public function setDynamicAttributes(?AttributesStorage $dynamicAttributes):void {
		$this->_dynamicAttributes = $dynamicAttributes;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasDynamicAttribute(string $name):bool {
		return (null !== $this->_dynamicAttributes && $this->_dynamicAttributes->hasAttribute($name));
	}

	public function addDynamicAttribute(string $name, ?int $type = null):void {
		$this->_dynamicAttributes->defineAttribute($name);
		DynamicAttributes::ensureAttribute($this, $name, $type);
	}
}