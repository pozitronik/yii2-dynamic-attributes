<?php
declare(strict_types = 1);

namespace _app\models;

use pozitronik\dynamic_attributes\traits\DynamicAttributesTrait;
use yii\db\ActiveRecord;

/**
 * Class Dummy
 * Для тестов
 * @property null|string $dummy
 */
class Dummy extends ActiveRecord {

	use DynamicAttributesTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'dummy';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['dummy'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'dummy' => 'dummy',
		];
	}
}