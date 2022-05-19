<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\controllers;

use cusodede\web\default_controller\models\DefaultController;
use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesSearch;

/**
 * Class IndexController
 */
class IndexController extends DefaultController {

	public ?string $modelClass = DynamicAttributes::class;

	public ?string $modelSearchClass = DynamicAttributesSearch::class;


	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return DynamicAttributesModule::param('viewPath', parent::getViewPath());//todo документировать
	}
}