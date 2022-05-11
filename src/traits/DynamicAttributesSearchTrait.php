<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\traits;

use ArrayObject;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use Throwable;
use yii\base\Model;
use yii\db\ActiveQueryInterface;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\SafeValidator;

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
		$this->_dynamicAttributesAliases = DynamicAttributes::getDynamicAttributesAliasesMap(parent::class);
	}

	/**
	 * inheritDoc
	 * Добавляет к прописанным в модели валидаторам валидаторы для алиасов динамических атрибутов
	 */
	public function createValidators():ArrayObject {
		/** @noinspection PhpDynamicAsStaticMethodCallInspection Метод вызывается в контексте поисковой модели, для создания валидаторов текущего экземпляра класса */
		$validators = Model::createValidators();
		foreach ($this->_dynamicAttributesAliases as $attribute => $alias) {
			$validators->append($this->getDynamicAttributeValidator($attribute, $alias));
		}
		return $validators;
	}

	/**
	 * @inheritDoc
	 * If the requested attribute is dynamic, returns corresponding value from the internal storage.
	 * If the requested attribute is a dynamic attribute alias, also returns corresponding value.
	 */
	public function __get($name):mixed {
		/*Запрос атрибута по алиасу имени атрибута*/
		if (in_array($name, $this->_dynamicAttributesAliases, true)) {
			return $this->_dynamicAttributes[$name]??null;
		}
		/*Запрос значения по имени атрибута => взять алиас имени и вернуть*/
		if (array_key_exists($name, $this->_dynamicAttributesAliases)) {
			return $this->_dynamicAttributes[$this->_dynamicAttributesAliases[$name]]??null;
		}
		return parent::__get($name);
	}

	/**
	 * @inheritDoc
	 * If requested attribute is dynamic, set corresponding name-value pair in internal storage
	 */
	public function __set($name, $value):void {
		if (in_array($name, $this->_dynamicAttributesAliases, true)) {
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
	 * Добавляет к правилам сортировки правила для динамических атрибутов
	 * @param array $sort
	 * @return array
	 * @throws Throwable
	 */
	private function adaptSort(array $sort):array {
		$sort['attributes'] = array_merge($sort['attributes']??[], $this->dynamicAttributesSort());
		return $sort;
	}

	/**
	 * Добавляет в запрос условия для корректной фильтрации по динамическим атрибутам
	 * @param ActiveQueryInterface $query
	 * @return void
	 * @throws Throwable
	 */
	private function adaptQuery(ActiveQueryInterface $query):void {
		$query->joinWith(['relatedDynamicAttributesValues']);
		foreach (DynamicAttributes::getAttributesTypes(parent::class) as $name => $type) {
			switch ($type) {
				case DynamicAttributes::TYPE_BOOL:
				case DynamicAttributes::TYPE_INT:
				case DynamicAttributes::TYPE_FLOAT:
					$query->andFilterWhere(Adapter::adaptWhere([$name => $this->$name]));
				break;
				case DynamicAttributes::TYPE_STRING:
					$query->andFilterWhere(Adapter::adaptWhere(['like', $name, $this->$name]));
				break;
			}
		}
	}

	/**
	 * @return array
	 * @throws Throwable
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

}