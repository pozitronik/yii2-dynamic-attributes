<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var DynamicAttributes $model
 * @var ActiveForm|string $form
 */

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\web\View;

?>

<?= Html::submitButton('Сохранить', [
		'class' => $model->isNewRecord?'btn btn-success float-right':'btn btn-primary float-right',
		'form' => is_object($form)?$form->id:$form
	]
) ?>
