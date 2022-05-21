<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use pozitronik\dynamic_attributes\models\DynamicAttributesValues;
use pozitronik\dynamic_attributes\models\DynamicAttributesAliases;

/**
 * Class DynamicAttributes
 * @property int $id
 * @property int $alias_id Alias ID
 * @property string $attribute_name Attribute name
 * @property null|int $type Attribute type, see self::TYPES
 *
 * @property-read DynamicAttributesAliases $relatedDynamicAttributesAliases
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
			[['attribute_name'], 'string', 'max' => 255],
			[['alias_id', 'attribute_name'], 'unique', 'targetAttribute' => ['alias_id', 'attribute_name']],
			[['type', 'alias_id'], 'integer']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function attributeLabels():array {
		return [
			'alias_id' => 'Алиас',
			'attribute_name' => 'Атрибут',
			'type' => 'Тип данных',
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesValues():ActiveQuery {
		return $this->hasMany(DynamicAttributesValues::class, ['alias_id' => 'alias_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesAliases():ActiveQuery {
		return $this->hasOne(DynamicAttributesAliases::class, ['id' => 'alias_id']);
	}

}