<?php
declare(strict_types = 1);

namespace pozitronik\sys_options\models\active_record;

use yii\db\ActiveRecord;

/**
 * Class DynamicAttributesValues
 * @property int $model Model ID
 * @property int $attribute Dynamic attribute ID
 * @property mixed $value Serialized dynamic attribute value
 */
class DynamicAttributesValues extends ActiveRecord {

	/**
	 * @inheritDoc
	 */
	public static function tableName():string {
		return 'sys_dynamic_attributes_values';
	}

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer', 'unique'],
			[['model', 'attribute'], 'unique', 'attributes' => ['model', 'attribute']],
			[['model', 'attribute'], 'integer'],
			[['value'], 'safe']
		];
	}
}