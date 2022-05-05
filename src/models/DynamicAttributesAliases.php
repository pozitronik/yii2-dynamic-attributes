<?php
declare(strict_types = 1);

namespace pozitronik\dynamic_attributes\models;
use pozitronik\dynamic_attributes\models\active_record\DynamicAttributesAliases as DynamicAttributesAliasesAR;

/**
 * Class DynamicAttributesAliases
 */
class DynamicAttributesAliases extends DynamicAttributesAliasesAR {

	/**
	 * @param string $alias
	 * @return static
	 */
	public static function ensureAlias(string $alias):static {
		return self::Upsert([
			'alias' => $alias
		]);

	}
}