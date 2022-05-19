<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var DynamicAttributes $model
 */

use pozitronik\dynamic_attributes\models\DynamicAttributes;
use yii\web\View;
use yii\widgets\DetailView;

?>

<?= DetailView::widget([
	'model' => $model
]) ?>
