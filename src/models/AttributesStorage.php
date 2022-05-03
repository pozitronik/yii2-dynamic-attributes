<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use yii\base\DynamicModel;

/**
 * Class AttributesStorage
 */
class AttributesStorage extends DynamicModel {

	/**
	 * @param array $attributes
	 * @return void
	 */
	public function loadAttributes(array $attributes):void {
		foreach ($attributes as $name => $value) {
			$this->defineAttribute($name, $value);
		}
	}
}