<?php
declare(strict_types = 1);

namespace pozitronik\sys_options\models;

use pozitronik\dynamic_attributes\DynamicAttributesModule;
use pozitronik\sys_options\models\active_record\DynamicAttributesValues as DynamicAttributesValuesAR;

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
	protected function unserialize(string $value) {
		return (null === $this->serializer)
			?unserialize($value, ['allowed_classes' => true])
			:call_user_func($this->serializer[1], $value);
	}


}