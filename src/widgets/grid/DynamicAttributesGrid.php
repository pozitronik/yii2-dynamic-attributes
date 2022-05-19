<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\widgets\grid;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\traits\DynamicAttributesTrait;
use pozitronik\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecordInterface;
use yii\widgets\ActiveForm;
use yii\widgets\InputWidget;

/**
 * @property ActiveRecordInterface|DynamicAttributesTrait|string $model Если передано строкой, считаем, что указан класс
 * @property  null|bool $showValues true: показывать значения атрибутов (только если указана модель), false: не показывать, null: показывать, если есть привязка к модели
 * @property  bool $editValues true: разрешить редактирование значений (если отображаются)
 * @property  ActiveForm $form Поскольку виджет составной, нам нужно передавать в него форму, к которой будут присоединяться виджеты редактирования
 *
 * todo: проверить поведение при подключении к модели без трейта атрибутов
 */
class DynamicAttributesGrid extends InputWidget {
	private string $_modelClass;
	public null|bool $showValues = null;
	public bool $editValues = true;
	public ActiveForm $form;
	private array $_dynamicAttributesAliases = [];

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		DynamicAttributesGridAssets::register($this->getView());
		$this->_modelClass = is_string($this->model)?$this->model:$this->model::class;
		$this->_dynamicAttributesAliases  = DynamicAttributes::getDynamicAttributesAliasesMap($this->_modelClass);
		if (null === $this->showValues || true === $this->showValues) {
			$this->showValues = !is_string($this->model);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function run():string {
		$dynamicAttributesTypes = DynamicAttributes::getAttributesTypes($this->_modelClass);
		$dataModels = $this->ConvertAttributeTypes($dynamicAttributesTypes);
		if ($this->showValues) $dataModels = static::EnrichWithValues($dataModels, $this->model->dynamicAttributesValues);

		$attributesDataProvider = new ArrayDataProvider([
			'allModels' => $dataModels,
			'pagination' => [
				'pageSize' => 0,
			],
		]);

		return $this->render('grid', [
			'model' => is_string($this->model)?null:$this->model,
			'modelClass' => $this->_modelClass,
			'dataProvider' => $attributesDataProvider,
			'showValues' => $this->showValues,
			'editValues' => $this->editValues,
			'form' => $this->form
		]);
	}

	/**
	 * Makes an array suitable for dataProvider usage
	 * @param array $attributesTypesArray
	 * @return array
	 */
	private function ConvertAttributeTypes(array $attributesTypesArray):array {
		return array_map(fn(string $key, ?int $value) => [
			'name' => $key,
			'alias' => $this->_dynamicAttributesAliases[$key],
			'type' => static::GetAttributeTypeLabel($value)
		], array_keys($attributesTypesArray), $attributesTypesArray);
	}

	/**
	 * Enriches name-type values array by actual values
	 * @param array $dataModels
	 * @param array $dynamicAttributesValues
	 * @return array
	 */
	private static function EnrichWithValues(array $dataModels, array $dynamicAttributesValues):array {
		return array_map(static fn(array $dataModelItem) => [
			'name' => $dataModelItem['name'],
			'alias' => $dataModelItem['alias'],
			'value' => $dynamicAttributesValues[$dataModelItem['name']]??null,
			'type' => $dataModelItem['type']
		], $dataModels);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public static function GetAttributeTypeLabel(mixed $value):string {
		return ArrayHelper::getValue(DynamicAttributes::typesList(), $value, 'Неизвестный тип');
	}

}