<?php
declare(strict_types = 1);
use yii\db\JsonExpression;
use yii\db\Migration;

/**
 * Class m000000_000000_create_dynamic_attributes_tables
 */
class m000000_000000_create_dynamic_attributes_tables extends Migration {
	private const ALIASES_TABLE_NAME = 'sys_dynamic_attributes_aliases';
	private const ATTRIBUTES_TABLE_NAME = 'sys_dynamic_attributes';
	private const VALUES_TABLE_NAME = 'sys_dynamic_attributes_values';

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::ALIASES_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'alias' => $this->string(256)->notNull()->comment('Model alias'),
		]);
		$this->addCommentOnTable(self::ALIASES_TABLE_NAME, 'Dynamic attributes classes aliases');
		$this->createIndex(self::ALIASES_TABLE_NAME.'_alias_idx', self::ALIASES_TABLE_NAME, ['alias'], true);

		$this->createTable(self::ATTRIBUTES_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'alias_id' => $this->integer()->notNull()->comment('Model alias id'),
			'attribute_name' => $this->string(256)->notNull()->comment('Attribute name'),
			'type' => $this->integer()->null()->comment('Attribute type')
		]);
		$this->addCommentOnTable(self::ATTRIBUTES_TABLE_NAME, 'Dynamic attributes attributes list');
		$this->createIndex(self::ATTRIBUTES_TABLE_NAME.'_alias_id_attribute_name_id', self::ATTRIBUTES_TABLE_NAME, ['alias_id', 'attribute_name'], true);
		$this->createIndex(self::ATTRIBUTES_TABLE_NAME.'_type_idx', self::ATTRIBUTES_TABLE_NAME, ['type']);
		$this->addForeignKey('fk_alias_id', self::ATTRIBUTES_TABLE_NAME, 'alias_id', self::ALIASES_TABLE_NAME, 'id');

		$this->createTable(self::VALUES_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'model_id' => $this->integer()->notNull()->comment('Model id'),
			'alias_id' => $this->integer()->notNull()->comment('Model alias id'),
			'attributes_values' => $this->json()->notNull()->defaultValue('[]')->comment('JSON serialized attribute value pars'),
		]);
		$this->addCommentOnTable(self::VALUES_TABLE_NAME, 'Dynamic attributes values');
		$this->createIndex(self::VALUES_TABLE_NAME.'_model_id_alias_id_idx', self::VALUES_TABLE_NAME, ['model_id', 'alias_id'], true);
		$this->addForeignKey('fk_alias_id', self::VALUES_TABLE_NAME, 'alias_id', self::ALIASES_TABLE_NAME, 'id');

		/*GIN Index на хранилище*/
		//$this->execute("CREATE INDEX ".self::VALUES_TABLE_NAME."_attributes_values_idx ON ".self::VALUES_TABLE_NAME." USING GIN (attributes_values);");

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::ALIASES_TABLE_NAME);
		$this->dropTable(self::ATTRIBUTES_TABLE_NAME);
		$this->dropTable(self::VALUES_TABLE_NAME);
	}

}
