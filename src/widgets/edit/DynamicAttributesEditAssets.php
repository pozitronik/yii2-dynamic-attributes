<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\widgets\edit;

use yii\web\AssetBundle;

/**
 * Class DynamicAttributesEditAssets
 */
class DynamicAttributesEditAssets extends AssetBundle {

	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		parent::init();
	}

}