<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var null|ActiveRecordInterface $model
 * @var string $modelClass
 * @var DataProviderInterface $dataProvider
 * @var bool $showValues
 * @var bool $editValues
 * @
 */

use yii\base\View;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\widgets\ActiveField;
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
			'value' => static function(array $data) use ($editValues, $model) {
				return ($editValues)
					?(new ActiveField([
						'form' => new ActiveForm(),
						'model' => $model,
						'attribute' => $data['name']
					]))->textInput()
					:$data['value'];
			},
			'visible' => $showValues
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'type',
			'label' => 'Тип'
		],
	]
]) ?>
