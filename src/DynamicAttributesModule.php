<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes;

use pozitronik\traits\traits\ModuleTrait;
use Yii;
use yii\base\Module;

/**
 * Class DynamicAttributesModule
 */
class DynamicAttributesModule extends Module {
	use ModuleTrait;

	/**
	 * {@inheritDoc}
	 */
	public function getControllerPath() {
		return Yii::getAlias('@vendor/pozitronik/yii2-dynamic-attributes/src/controllers');
	}
}
