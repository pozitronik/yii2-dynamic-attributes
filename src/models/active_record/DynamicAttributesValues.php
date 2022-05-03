<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class DynamicAttributesValues
 * @property int $key Model ID
 * @property int $attribute_id Dynamic attribute ID
 * @property mixed $value Serialized dynamic attribute value
 *
 * @property-read DynamicAttributes $relatedDynamicAttributes
 */
class DynamicAttributesValues extends ActiveRecord {
	use ActiveRecordTrait;

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
			[['id'], 'integer'],
			[['key', 'attribute_id'], 'unique', 'attributes' => ['key', 'attribute_id']],
			[['key', 'attribute_id'], 'integer'],
			[['value'], 'safe']
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributes::class, ['id' => 'attribute_id']);
	}
}