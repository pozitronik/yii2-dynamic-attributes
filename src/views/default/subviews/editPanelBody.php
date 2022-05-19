<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var DynamicAttributes $model
 * @var ActiveForm $form
 */

use kartik\select2\Select2;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

?>

<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'alias')->widget(Select2::class, [
			'data' => DynamicAttributes::getAliasesList()
		]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'attribute_name')->textInput() ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'type')->widget(Select2::class, [
			'data' => DynamicAttributes::typesList()
		]) ?>
	</div>
</div>


