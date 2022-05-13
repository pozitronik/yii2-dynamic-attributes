<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\adapters;

use yii\db\ActiveRecordInterface;

/**
 * Interface AdapterInterface
 */
interface AdapterInterface {

	/**
	 * Преобразует имя динамического поля в подходящий для запроса формат
	 * @param string $jsonFieldName
	 * @param ActiveRecordInterface|string|null $model
	 * @return string
	 */
	public static function adaptField(string $jsonFieldName, ActiveRecordInterface|string|null $model = null):string;

	/**
	 * Превращает упрощённое условие выборки в массив для QueryBuilder
	 * @param array $condition
	 * @return array
	 */
	public static function adaptWhere(array $condition):array;

	/**
	 * Возвращает атрибут JSON-поля для построения запроса
	 * @param string $jsonFieldName
	 * @param int|null $fieldType Тип поля. Если численный код типа, то адаптер пытается найти подходящий тип pgsql, если null, типизация БД будет проигнорирована
	 * @return string
	 */
	public static function jsonFieldName(string $jsonFieldName, ?int $fieldType):string;

	/**
	 * Возвращает запрос на создание индекса по указанному полю
	 * @param string $jsonFieldName
	 * @param int|null $fieldType
	 * @param array $parameters Дополнительные параметры для запроса
	 * @return string|null null, если не поддерживается
	 */
	public static function indexOnJsonField(string $jsonFieldName, ?int $fieldType, array $parameters = []):?string;

}