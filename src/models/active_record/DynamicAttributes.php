<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models\active_record;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveRecord;

/**
 * Class DynamicAttributes
 * @property string $model Model alias
 * @property string $attribute Attribute name
 * @property null|int $type Attribute type, see self::TYPES
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
			[['id'], 'integer', 'unique'],
			[['model', 'attribute'], 'string', 'max' => 255],
			[['model', 'attribute'], 'unique', 'attributes' => ['model', 'attribute']],
			[['type'], 'integer']
		];
	}

}