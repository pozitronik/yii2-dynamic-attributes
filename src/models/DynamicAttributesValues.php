<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributesValues as DynamicAttributesValuesAR;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use Yii;
use yii\caching\TagDependency;
use yii\db\Query;

/**
 * Class DynamicAttributesValues
 */
class DynamicAttributesValues extends DynamicAttributesValuesAR {
	/**
	 * @var null|array the functions used to serialize and unserialize values. Defaults to null, meaning
	 * using the default PHP `serialize()` and `unserialize()` functions. If you want to use some more efficient
	 * serializer (e.g. [igbinary](https://pecl.php.net/package/igbinary)), you may configure this property with
	 * a two-element array. The first element specifies the serialization function, and the second the deserialization
	 * function.
	 */
	public null|array $serializer = null;
	/**
	 * @var bool enable intermediate caching via Yii::$app->cache (must be configured in framework). Default option
	 * value can be set in a module configuration, e.g.
	 * ...
	 * 'dynamic_attributes' => [
	 *        'class' => DynamicAttributesModule::class,
	 *            'params' => [
	 *                'cacheEnabled' => true//defaults to false
	 *            ]
	 *        ],
	 * ...
	 */
	public bool $cacheEnabled = true;

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		$this->cacheEnabled = DynamicAttributesModule::param('cacheEnabled', $this->cacheEnabled);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function serialize(mixed $value):string {
		return (null === $this->serializer)?serialize($value):call_user_func($this->serializer[0], $value);
	}

	/**
	 * @param string $value
	 * @return mixed
	 */
	protected function unserialize(string $value):mixed {
		return (null === $this->serializer)
			?unserialize($value, ['allowed_classes' => true])
			:call_user_func($this->serializer[1], $value);
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public static function unserializeValue(mixed $value):mixed {
		if (is_resource($value) && 'stream' === get_resource_type($value)) {
			$result = stream_get_contents($value);
			fseek($value, 0);
			$serialized = $result;
		} else {
			$serialized = $value;
		}
		return (new static())->unserialize($serialized);
	}

	/**
	 * @param int $attributeIndex
	 * @param int $key
	 * @return string
	 * @throws Throwable
	 */
	protected function retrieveDbValue(int $attributeIndex, int $key):string {
		$value = ArrayHelper::getValue(
			(new Query())
				->select('value')
				->from($this::tableName())
				->where([static::fieldName('attribute_id') => $attributeIndex])
				->andWhere([static::fieldName('key') => $key])
				->one(),
			'value', $this->serialize(null));
		if (is_resource($value) && 'stream' === get_resource_type($value)) {
			$result = stream_get_contents($value);
			fseek($value, 0);
			return $result;
		}
		return $value;
	}

	/**
	 * @param int $attribute_id
	 * @param int $key
	 * @param string $value
	 * @return null|static
	 */
	protected function applyDbValue(int $attribute_id, int $key, string $value):?static {
		try {
			return static::Upsert(compact('key', 'attribute_id', 'value'));
		} catch (Throwable $e) {
			Yii::warning("Unable to update or insert table value: {$e->getMessage()}", __METHOD__);
		}
		return null;
	}

	/**
	 * @param int $attributeIndex
	 * @param int $key
	 * @param mixed|null $value
	 * @return null|static
	 */
	public static function setAttributeValue(int $attributeIndex, int $key, mixed $value = null):?static {
		return (new static())->set($attributeIndex, $key, $value);
	}

	/**
	 * @param int $attributeIndex
	 * @param int $key
	 * @param null $default
	 * @return mixed|null (null by default)
	 * @throws Throwable
	 */
	public function get(int $attributeIndex, int $key, mixed $default = null):mixed {
		$dbValue = ($this->cacheEnabled)
			?Yii::$app->cache->getOrSet(static::class."::get({$attributeIndex}:{$key})", fn() => $this->retrieveDbValue($attributeIndex, $key), null, new TagDependency(['tags' => static::class."::get({{$attributeIndex}:{$key}})"]))
			:$this->retrieveDbValue($attributeIndex, $key);
		return (null === $value = $this->unserialize($dbValue))?$default:$value;
	}

	/**
	 * @param int $attributeIndex
	 * @param int $key
	 * @param mixed|null $value
	 * @return null|static
	 */
	public function set(int $attributeIndex, int $key, mixed $value = null):?static {
		TagDependency::invalidate(Yii::$app->cache, [static::class."::get({$attributeIndex}:{$key}})"]);
		return $this->applyDbValue($attributeIndex, $key, $this->serialize($value));
	}

	/**
	 * @param int $attributeIndex
	 * @param int $key
	 * @param mixed|null $default
	 * @return mixed
	 * @throws Throwable
	 */
	public static function getAttributeValue(int $attributeIndex, int $key, mixed $default = null):mixed {
		return (new static())->get($attributeIndex, $key, $default);
	}
}