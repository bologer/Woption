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
		'inc/Field',
		'inc/Option',
		'inc/OptionManager',
		'inc/Section'
	];

	/**
	 * Loader.
	 */
	public static function load() {
		foreach ( static::$classes as $class ) {
			require_once __DIR__ . '/' . $class . '.php';
		}
	}
}