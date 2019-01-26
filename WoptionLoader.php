<?php

/**
 * Class WoptionLoader is a loader class.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class WoptionLoader {
	/**
	 * @var array Loader map.
	 */
	public static $classes = [
		// Core
		'inc/Option',
		'inc/OptionManager',
		'inc/Section',

		// Base
		'inc/Base/BaseHtml',

		// Fields
		'inc/Fields/BaseField',
		'inc/Fields/Input',
		'inc/Fields/Color',
		'inc/Fields/Checkbox',
		'inc/Fields/Select',
		'inc/Fields/Textarea',
	];

	/**
	 * Require all classes.
	 */
	public static function load () {
		foreach ( static::$classes as $class ) {
			require_once __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
		}
	}
}
