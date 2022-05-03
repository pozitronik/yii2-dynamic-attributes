<?php
declare(strict_types = 1);
use yii\db\Migration;

/**
 * Class m000000_000000_create_dynamic_attributes_tables
 */
class m000000_000000_create_dynamic_attributes_tables extends Migration {
	private const ATTR_TABLE_NAME = 'sys_dynamic_attributes';
	private const VALUES_TABLE_NAME = 'sys_dynamic_attributes_values';

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::ATTR_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'model' => $this->string(256)->notNull()->comment('Model alias'),
			'attribute_name' => $this->string(256)->notNull()->comment('Attribute name'),
			'type' => $this->integer()->null()->comment('Attribute type')
		]);

		$this->createTable(self::VALUES_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'attribute_id' => $this->integer()->notNull()->comment('Attribute id'),
			'key' => $this->integer()->notNull()->comment('Model id'),
			'value' => $this->binary()->null()->comment('Serialized attribute value'),
		]);

		$this->createIndex(self::ATTR_TABLE_NAME.'_model_attribute_name_idx', self::ATTR_TABLE_NAME, ['model', 'attribute_name'], true);
		$this->createIndex(self::ATTR_TABLE_NAME.'_type_idx', self::ATTR_TABLE_NAME, ['type']);

		$this->createIndex(self::VALUES_TABLE_NAME.'_key_attribute_name_idx', self::VALUES_TABLE_NAME, ['key', 'attribute_id'], true);
		$this->addForeignKey('fk_'.self::ATTR_TABLE_NAME.'_attribute_id'.self::ATTR_TABLE_NAME.'_id', self::VALUES_TABLE_NAME, 'attribute_id', self::ATTR_TABLE_NAME, 'id');

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::ATTR_TABLE_NAME);
		$this->dropTable(self::VALUES_TABLE_NAME);
	}

}
