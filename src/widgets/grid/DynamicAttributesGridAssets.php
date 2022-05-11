<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\widgets\grid;

use yii\web\AssetBundle;

/**
 * Class DynamicAttributesGridAssets
 */
class DynamicAttributesGridAssets extends AssetBundle {

	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		parent::init();
	}

}