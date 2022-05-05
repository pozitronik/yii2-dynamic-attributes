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
 *
 * @property-read DynamicAttributes[] $relatedDynamicAttributes
 * @property-read DynamicAttributesValues[] $relatedDynamicAttributesValues
 */
class DynamicAttributesAliases extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * @inheritDoc
	 */
	public static function tableName():string {
		return 'sys_dynamic_attributes_aliases';
	}

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['alias'], 'string', 'max' => 255],
			[['alias'], 'unique', 'targetAttribute' => ['alias']],
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributesValues():ActiveQuery {
		return $this->hasMany(DynamicAttributesValues::class, ['alias_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedDynamicAttributes():ActiveQuery {
		return $this->hasMany(DynamicAttributes::class, ['alias_id' => 'id']);
	}

}