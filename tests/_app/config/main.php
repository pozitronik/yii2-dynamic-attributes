<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

use app\models\Dummy;
use app\models\Users;
use pozitronik\dynamic_attributes\DynamicAttributesModule;
use yii\log\FileTarget;
use yii\caching\DummyCache;
use yii\web\AssetManager;
use yii\web\ErrorHandler;

$db = require __DIR__.'/db.php';

$config = [
	'id' => 'basic',
	'basePath' => dirname(__DIR__),
	'bootstrap' => ['log'],
	'aliases' => [
		'@vendor' => './vendor',
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'modules' => [
		'dynamic_attributes' => [
			'class' => DynamicAttributesModule::class,
			'params' => [
				'models' => [
					DummyClass::class => 'dummy'
				]
			]
		],
	],
	'components' => [
		/* \yii\data\Sort::getAttributeOrders() всегда перезагружает атрибуты сортировки из запроса, если он установлен. При этом Request относится к CoreComponents
		 * и будет пересоздан фреймворком принудительно. Поэтому мы его подменяем на заглушку.
		 */
		'request' => [
			'class' => Dummy::class
		],
		'cache' => [
			'class' => DummyCache::class,
		],
		'user' => [
			'identityClass' => Users::class,
			'enableAutoLogin' => true,
		],
		'errorHandler' => [
			'class' => ErrorHandler::class,
			'errorAction' => 'site/error',
		],
		'log' => [
			'traceLevel' => YII_DEBUG?3:0,
			'targets' => [
				[
					'class' => FileTarget::class,
					'levels' => ['error', 'warning'],
				],
			],
		],
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'rules' => [
			],
		],
		'assetManager' => [
			'class' => AssetManager::class,
			'basePath' => '@app/assets'
		],
		'db' => $db
	],
	'params' => [
		'bsVersion' => '4'
	],
];

return $config;