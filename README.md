yii2-dynamic-attributes
=================

Динамические атрибуты для ActiveRecord-моделей.

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pozitronik/yii2-dynamic-attributes/CI%20with%20PostgreSQL)

Установка
---------

Предпочтительный вариант установки расширения через [composer](http://getcomposer.org/download/).

Выполните

```
php composer.phar require pozitronik/yii2-dynamic-attributes "^1.0.0"
```

или добавьте

```
"pozitronik/yii2-dynamic-attributes": "^1.0.0"
```

В секцию require файла `composer.json` в вашем проекте.

Миграции
--------

Модуль хранит данные в таблицах, которые будут созданы командой

`php yii migrate/up --migrationPath=@vendor/pozitronik/yii2-dynamic-attributes/migrations`

список названий таблиц, создаваемых миграцией, можно посмотреть в
файле `migrations/m000000_000000_create_dynamic_attributes_tables.php`.

Конфигурация
------------

```php
return [
	// ...
	'modules' => [
		'dynamic_attributes' => [
			'class' => DynamicAttributesModule::class,
			'params' => [
				'models' => [/* Список алиасов классов в формате "Имя класса" => "алиас класса" */
					DummyClass::class => 'dummy'
				],
				'limitFloatPrecision' => true, /* Включает ограничение размера сохраняемых значений с плавающей точкой до 14 десятичных знаков, см. DynamicAttributesValues::$limitFloatPrecision */
			]
		]
	]
	//...
];
```

# Запуск локальных тестов

Скопируйте `tests/.env.example` в `tests/.env`, и измените конфигурацию соответственно вашему локальному окружению. Затем выполните команду `php vendor/bin/codecept run`.

Лицензия
--------
GNU GPL v3.0