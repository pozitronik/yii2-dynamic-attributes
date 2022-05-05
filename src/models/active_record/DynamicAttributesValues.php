<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class DynamicAttributesValues
 * @property int $alias_id Alias ID
 * @property int $model_id Model ID
 * @property array $attributes_values JSON-serialized dynamic attributes values
 *
 * @property-read DynamicAttributesAliases[] $relatedDynamicAttributes
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
			[['model_id', 'alias_id'], 'unique', 'targetAttribute' => ['model_id', 'alias_id']],
			[['model_id', 'alias_id'], 'integer'],
			[['attributes_values'], 'safe']
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributesAliases::class, ['id' => 'alias_id']);
	}

}