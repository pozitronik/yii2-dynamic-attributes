<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;

/**
 * Class DynamicAttributes
 * @property int $id
 * @property string $alias Model alias
 * @property string $attribute_name Attribute name
 * @property null|int $type Attribute type, see self::TYPES
 *
 * @property-read DynamicAttributesValues[] $relatedDynamicAttributesValues
 */
class DynamicAttributes extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * @inheritDoc
	 */
	public static function tableName():string {
		return 'sys_dynamic_attributes';
	}

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['alias', 'attribute_name'], 'string', 'max' => 255],
			[['alias', 'attribute_name'], 'unique', 'targetAttribute' => ['alias', 'attribute_name']],
			[['type'], 'integer']
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesValues():ActiveQuery {
		return $this->hasMany(DynamicAttributesValues::class, ['alias_id' => 'id']);
	}

}