<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var DynamicAttributesSearch $searchModel
 * @var DynamicAttributes $model
 * @var string $modelName
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use kartik\base\AssetBundle;
use kartik\grid\ActionColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;
use pozitronik\dynamic_attributes\models\adapters\Adapter;
use pozitronik\dynamic_attributes\models\DynamicAttributes;
use pozitronik\dynamic_attributes\models\DynamicAttributesSearch;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use pozitronik\dynamic_attributes\widgets\grid\DynamicAttributesGrid;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use pozitronik\traits\traits\ControllerTrait;
use pozitronik\widgets\BadgeWidget;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\View;

AssetBundle::register($this);

$id = "{$modelName}-index-grid";
?>

<?= GridConfig::widget([
	'id' => $id,
	'grid' => GridView::begin([
		'id' => $id,
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'filterOnFocusOut' => false,
		'panel' => [
			'heading' => false,
		],
		'replaceTags' => [
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['запись', 'записи', 'записей']):"Нет записей",
			'{newRecord}' => Html::a('Новая запись', $controller->link('create'), ['class' => 'btn btn-success']),
		],
		'panelBeforeTemplate' => '{newRecord}{toolbarContainer}<div class="clearfix"></div>',
		'summary' => null,
		'showOnEmpty' => true,
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => [
			[
				'class' => ActionColumn::class,
				'template' => '<div class="btn-group">{update}{view}{delete}</div>',
				'dropdown' => true,
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'alias',
				'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
					'items' => $model->relatedDynamicAttributesAliases->alias,
					'tooltip' => DynamicAttributes::getAliasClass($model->relatedDynamicAttributesAliases->alias)
				]),
				'format' => 'raw',
				'group' => true
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
				'attribute' => 'type',
				'value' => static fn(DynamicAttributes $model) => BadgeWidget::widget([
					'items' => DynamicAttributesGrid::GetAttributeTypeLabel($model->type)
				]),
				'format' => 'raw'
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
				'format' => 'boolean'
			]
		]
	])
]) ?>