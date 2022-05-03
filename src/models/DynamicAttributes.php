<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributes as DynamicAttributesAR;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;

/**
 * Class DynamicAttributes
 */
class DynamicAttributes extends DynamicAttributesAR {

	public const TYPE_BOOL = 1;
	public const TYPE_INT = 2;
	public const TYPE_STRING = 3;

	/**
	 * @var string[] Перечисление класс модели => используемый алиас
	 */
	private static array $_modelsAliases = [];

	/**
	 * @param ActiveRecordInterface $model
	 * @param string $attribute
	 * @param int|null $type
	 * @return void
	 * @throws Throwable
	 */
	public static function ensureAttribute(ActiveRecordInterface $model, string $attribute, ?int $type = null):void {
		static::Upsert([
			'model' => static::getClassAlias($model::class),
			'attribute' => $attribute,
			'type' => $type
		]);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws Throwable
	 */
	public static function listAttributes(ActiveRecordInterface $model):array {
		return static::find()
			->where(['model' => static::getClassAlias($model::class)])
			->all();
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function getAttributesValues(ActiveRecordInterface $model):array {
		return DynamicAttributesValues::find()
			->joinWith(['relatedDynamicAttributes'])
			->where([DynamicAttributes::fieldName('attribute') => static::getClassAlias($model::class)])
			->andWhere([static::fieldName('model') => static::extractKey($model)])
			->all();
	}

	/**
	 * @inheritDoc
	 */
	public function init() {
		parent::init();
		self::$_modelsAliases = DynamicAttributesModule::param('models', self::$_modelsAliases);
	}

	/**
	 * @param ActiveRecordInterface $model
	 * @return null|int
	 * @throws InvalidConfigException
	 */
	private static function extractKey(ActiveRecordInterface $model):?int {
		/** @var array $primaryKeyName */
		$primaryKeyName = $model::primaryKey();
		if (isset($primaryKeyName[0])) {
			$primaryKeyValue = $model->{$primaryKeyName[0]};
			if (is_int($primaryKeyValue) || null === $primaryKeyValue) {
				return $primaryKeyValue;
			}
		}
		throw new InvalidConfigException(sprintf("\"%s\" must have a integer primary key.", $model::class));
	}

	/**
	 * Установить динамически алиас модели
	 * @param string $class
	 * @param null|string $alias
	 * @return void
	 */
	public static function setClassAlias(string $class, ?string $alias = null):void {
		$alias = $alias??$class;
		ArrayHelper::setValue(self::$_modelsAliases, $class, $alias);
	}

	/**
	 * @param string $alias
	 * @return null|string
	 */
	public static function getAliasClass(string $alias):?string {
		return (false === $class = array_search($alias, self::$_modelsAliases, true))?null:$class;
	}

	/**
	 * @param string $class
	 * @return string|null
	 * @throws Throwable
	 */
	public static function getClassAlias(string $class):?string {
		return ArrayHelper::getValue(self::$_modelsAliases, $class);
	}
}