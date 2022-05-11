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

Концепция и примеры использования
---------------------------------

Обычно проектируя приложение, мы заранее знаем, с какими наборами данных будем работать, моделируя
классы с учётом этого, например, создавая структуру БД под эти данные. Но бывают случаи, когда нужно дать
пользователю самому определять набор данных прямо в рантайме.

`- Как в Excel? - Как в Excel!`

Этот компонент добавляет поддержку динамических атрибутов в ActiveRecord-модели ровно в два шага.

1) Добавляем алиас класса записав его [в конфигурацию модели](#Конфигурация), либо динамически:

```php
DynamicAttributes::setClassAlias(MyTableModel::class, 'myTableModel');
```

2) Добавляем трейт `DynamicAttributesTrait` в вашу модель:

```php
<?php
declare(strict_types = 1);

namespace app\models;

use pozitronik\dynamic_attributes\traits\DynamicAttributesTrait;
use yii\db\ActiveRecord;

class MyTableModel extends ActiveRecord {
	use DynamicAttributesTrait;

	/*Прочий обычный код*/
}
```

Теперь в экземплярах классов этой модели можно без ограничений создавать любые атрибуты:

```php
	$tableModel = new MyTableModel();
	
	$tableModel->my_cool_attribute = 'произвольный атрибут'
	/*или даже*/
	$tableModel->{'числовой атрибут'} = 100500;
	/*или даже так*/
	$tableModel->{'❤️Z̮̞̠͙͔ͅḀ̗̞͈̻̗Ḷ͙͎̯̹̞͓G̻O̭̗̮❤️'} = 'yes';
	
	$tableModel->save();
	/*значения динамических атрибутов сохраняются вместе с моделью, и будут доступны и далее*/
	
	$otherTableModel = MyTableModel::find()->where(['id' => $tableModel->id])->one();
	$otherTableModel->{'❤️Z̮̞̠͙͔ͅḀ̗̞͈̻̗Ḷ͙͎̯̹̞͓G̻O̭̗̮❤️'} === 'yes';// => true 

```

Примеры манипуляций с динамическими атрибутами можно посмотреть в
тесте [tests/unit/DynamicAttributesTest.php](https://github.com/pozitronik/yii2-dynamic-attributes/blob/master/tests/unit/DynamicAttributesTest.php)
.

## Использование в search-моделях.

Чтобы использовать динамические атрибуты в поисковых моделях, нужно выполнить несколько шагов:

1) Добавить в поисковую модель трейт `DynamicAttributesSearchTrait`,
2) Если требуется поддержка сортировки, добавить её в настройки сортировки,
3) Добавить поддержку фильтров в поисковый запрос.

Для п.п 2-4 написаны адаптеры, избавляющие вас от лишних телодвижений.

```php
<?php
declare(strict_types = 1);

namespace app\models;

use pozitronik\dynamic_attributes\traits\DynamicAttributesSearchTrait;
use yii\data\ActiveDataProvider;

/**
 * Class UsersSearch
 */
class MyTableModelSearch extends MyTableModel {
    /*Добавляем трейт*/
	use DynamicAttributesSearchTrait;

	/**
	 * @inheritdoc
	 */
	public function rules():array {
	    /*Отдельно описывать правила для динамических атрибутов не нужно, они сгенерируются автоматически*/
		return [
			[['id'], 'integer']
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 */
	public function search(array $params):ActiveDataProvider {
		$query = MyTableModel::find();

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);
        /*Добавляем сортировку*/
		$dataProvider->setSort($this->adaptSort([
			'defaultOrder' => ['id' => SORT_ASC],
			'attributes' => [
				'id' => [
					'asc' => ['id' => SORT_ASC],
					'desc' => ['id' => SORT_DESC]
				],
			]
		]));

		$this->load($params);
		$query->andFilterWhere(['id' => $this->id]);
		/*Добавляем поддержку фильтров*/
		$this->adaptQuery($query);

		return $dataProvider;
	}
}
```

Теперь динамические атрибуты можно использовать в GridView абсолютно аналогично обычным атрибутам (хотя вам
наверняка понадобится добавлять их к гриду динамически, но это несложно).

## Ну почти...

Небольшое ограничение: при генерации html-полей для атрибутов моделей (например, для фильтров в гридах),
используются имена этих атрибутов. Как видно выше, динамический атрибут может быть назван так, что ничего
хорошего из его имени сгенерировать не получится.

Для решения этой проблемы, каждому динамическому атрибуту во время исполнения присваивается алиас, который
используется в поисковой модели (и, соответственно, в генераторах html) вместо непосредственного имени
атрибута. Это сделано, для того, чтобы максимально не напрягать разработчика, но при необходимости можно
посмотреть на логику подстановок в
тесте [tests/unit/DynamicAttributesSearchTest.php](https://github.com/pozitronik/yii2-dynamic-attributes/blob/master/tests/unit/DynamicAttributesSearchTest.php)
и далее по коду.

Ограничения
-----------

- Текущая версия создавалась только для работы с PostgreSQL, и проверялась только на ней.
- На текущий момент не полностью поддерживаются структурированные типы данных (массивы и объекты). Создание и
  использование динамических атрибутов с такими значениями допускается, но поиск и сортировка по ним не
  реализованы.

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
					MyTableModel:class => 'myTableModel'
				],
				'limitFloatPrecision' => true, /* Включает ограничение размера сохраняемых значений с плавающей точкой до 14 десятичных знаков, см. DynamicAttributesValues::$limitFloatPrecision */
			]
		]
	]
	//...
];
```

Запуск локальных тестов
-----------------------

Скопируйте `tests/.env.example` в `tests/.env`, и измените конфигурацию соответственно вашему локальному
окружению. Затем выполните команду `php vendor/bin/codecept run`.

Лицензия
--------
GNU GPL v3.0