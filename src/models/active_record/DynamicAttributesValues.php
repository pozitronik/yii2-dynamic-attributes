<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class DynamicAttributesValues
 * @property int $model Model ID
 * @property int $attribute Dynamic attribute ID
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
			[['id'], 'integer', 'unique'],
			[['model', 'attribute'], 'unique', 'attributes' => ['model', 'attribute']],
			[['model', 'attribute'], 'integer'],
			[['value'], 'safe']
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributes::class, ['id' => 'attribute']);
	}
}