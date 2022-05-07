<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;

/**
 * Trait DynamicAttributesSearchTrait
 * This trait add filtering and sorting support with dynamic attributes in search models
 */
trait DynamicAttributesSearchTrait {

	/**
	 * @var array Dynamic attributes name-value storage
	 */
	private $_dynamicAttributes = [];
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
		$this->_dynamicAttributesAliases = static::getDynamicAttributesAliasesMap();
	}

	/**
	 * @inheritDoc
	 * If requested attribute is dynamic, return corresponding value from internal storage
	 */
	public function __get($name):mixed {
		if (in_array($name, $this->_dynamicAttributesAliases)) {
			return $this->_dynamicAttributes[$name]??null;
		}
		return parent::__get($name);
	}

	/**
	 * @inheritDoc
	 * If requested attribute is dynamic, set corresponding name-value pair in internal storage
	 */
	public function __set($name, $value):void {
		if (in_array($name, $this->_dynamicAttributesAliases)) {
			$this->_dynamicAttributes[$name] = $value;
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
	 * @inheritdoc
	 * Modifies search rules by adding dynamic attributes rules set to them
	 */
	public function rules():array {
		return array_merge(static::rules(), [$this->_dynamicAttributesAliases, 'safe']);//todo: generator should rely on attribute type, i guess
	}

	/**
	 * @param array $attributes
	 * @return array [attribute name => attribute alias]
	 */
	public static function getDynamicAttributesAliasesMap():array {
		$attributes = DynamicAttributes::listAttributes(parent::class);
		$old_attributes = $attributes;
		array_walk($attributes, fn(&$value, $key) => $value = 'da'.$key);
		return array_combine($old_attributes, $attributes);
	}

	/**
	 * @return array
	 * @throws \Throwable
	 */
	public function dynamicAttributesSort():array {
		$result = [];
		foreach (DynamicAttributes::getAttributesTypes(parent::class) as $name => $type) {
			$result[$this->_dynamicAttributesAliases[$name]] = [
				'asc' => [Adapter::adaptField($name, parent::class) => SORT_ASC],
				'desc' => [Adapter::adaptField($name, parent::class) => SORT_DESC]
			];
		}
		return $result;
	}

	//DataProviderAdapter!

}