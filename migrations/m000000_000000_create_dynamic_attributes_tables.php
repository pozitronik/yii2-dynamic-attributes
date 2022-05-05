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
			'alias' => $this->string(256)->notNull()->comment('Model alias'),
			'attribute_name' => $this->string(256)->notNull()->comment('Attribute name'),
			'type' => $this->integer()->null()->comment('Attribute type')
		]);

		$this->addCommentOnTable(self::ATTR_TABLE_NAME, 'Dynamic attributes list');

		$this->createTable(self::VALUES_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'model_id' => $this->integer()->notNull()->comment('Model id'),
			'alias_id' => $this->integer()->notNull()->comment('Model alias id'),
			'attributes_values' => $this->json()->null()->comment('JSON serialized attribute value pars'),
		]);

		$this->addCommentOnTable(self::VALUES_TABLE_NAME, 'Dynamic attributes values');

		$this->createIndex(self::ATTR_TABLE_NAME.'_alias_attribute_name_idx', self::ATTR_TABLE_NAME, ['alias', 'attribute_name'], true);
		$this->createIndex(self::ATTR_TABLE_NAME.'_type_idx', self::ATTR_TABLE_NAME, ['type']);

		$this->createIndex(self::VALUES_TABLE_NAME.'_model_id_alias_id_idx', self::VALUES_TABLE_NAME, ['model_id', 'alias_id'], true);
		$this->addForeignKey('fk_alias_id', self::VALUES_TABLE_NAME, 'alias_id', self::ATTR_TABLE_NAME, 'id');

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::ATTR_TABLE_NAME);
		$this->dropTable(self::VALUES_TABLE_NAME);
	}

}
