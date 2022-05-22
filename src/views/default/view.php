<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var DynamicAttributes $model
 */

use kartik\grid\DataColumn;
use kartik\grid\GridView;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use pozitronik\dynamic_attributes\widgets\grid\DynamicAttributesGrid;
use pozitronik\widgets\BadgeWidget;
use yii\web\View;
use yii\widgets\DetailView;

?>

<?= DetailView::widget([
	'model' => $model,
	'attributes' => [
		[
			'class' => DataColumn::class,
			'attribute' => 'alias',
			'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
				'items' => $model->relatedDynamicAttributesAliases->alias,
				'tooltip' => DynamicAttributes::getAliasClass($model->relatedDynamicAttributesAliases->alias)
			]),
			'format' => 'raw',
			'group' => true,
			'filter' => DynamicAttributes::getAliasesList(),
			'filterType' => GridView::FILTER_SELECT2,
			'filterInputOptions' => ['placeholder' => 'Зарегистрированные классы'],
			'filterWidgetOptions' => [
				'pluginOptions' => ['allowClear' => true, 'multiple' => true]
			],
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'attribute',
			'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
				'items' => $model->attribute_name,
			]),
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'types',
			'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
				'items' => DynamicAttributesGrid::GetAttributeTypeLabel($model->type)
			]),
			'format' => 'raw',
			'filter' => DynamicAttributes::typesList(),
			'filterType' => GridView::FILTER_SELECT2,
			'filterInputOptions' => ['placeholder' => 'Тип данных'],
			'filterWidgetOptions' => [
				'pluginOptions' => ['allowClear' => true, 'multiple' => true]
			],

		],
		[
			'class' => DataColumn::class,
			'attribute' => 'count',
			'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
				'items' => DynamicAttributesValues::find()->where(Adapter::adaptWhere(['is not', $model->attribute_name, null]))->count()
			]),
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'indexed',
			'value' => static fn(DynamicAttributes $model) => false,
			'format' => 'boolean',
			'visible' => false //tbd
		]
	]
]) ?>
