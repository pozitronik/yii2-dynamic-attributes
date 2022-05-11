<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var null|ActiveRecordInterface $model
 * @var string $modelClass
 * @var DataProviderInterface $dataProvider
 * @var bool $showValues
 * @var bool $editValues
 * @var ActiveForm $form
 */

use pozitronik\dynamic_attributes\widgets\edit\DynamicAttributesEdit;
use yii\base\View;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

?>

<?= GridView::widget([
	'dataProvider' => $dataProvider,
	'layout' => "{items}\n{pager}",
	'columns' => [
		[
			'class' => DataColumn::class,
			'attribute' => 'name',
			'label' => 'Название атрибута'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'value',
			'label' => 'Значение',
			'value' => static function(array $data) use ($editValues, $model, $form) {
				return ($editValues)
					?$form->field($model, $data['alias'])->widget(DynamicAttributesEdit::class)->label(false)
					:$data['value'];
			},
			'format' => 'raw',
			'visible' => $showValues
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'type',
			'label' => 'Тип'
		],
	]
]) ?>
