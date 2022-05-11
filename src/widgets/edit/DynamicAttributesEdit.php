<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\widgets\edit;

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\traits\DynamicAttributesTrait;
use yii\db\ActiveRecordInterface;
use yii\widgets\InputWidget;

/**
 * @property ActiveRecordInterface|DynamicAttributesTrait $model
 */
class DynamicAttributesEdit extends InputWidget {

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		DynamicAttributesEditAssets::register($this->getView());
	}

	/**
	 * @inheritDoc
	 */
	public function run():string {
		$activeField = match ($this->model->getDynamicAttributeType($this->attribute)) {
			DynamicAttributes::TYPE_BOOL => $this->field->checkbox()->label(false),
			DynamicAttributes::TYPE_INT => $this->field->textInput(['numeric' => true])->label(false),
			default => $this->field->textInput()->label(false)
		};
		return $activeField->render();
	}


}