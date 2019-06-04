<?php

namespace Woption\Fields;

/**
 * Class Text is used to render
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption\Fields
 */
class Input extends BaseField {

	/**
	 * @var string Field type.
	 */
	protected $type;

	/**
	 * @return string
	 */
	public function get_type () {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return $this
	 */
	public function set_type ( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_field () {
		$type  = $this->get_type();
		$for   = $this->get_label_for();
		$name  = $this->get_id();
		$value = $this->get_value();

		return <<<EOT
            <input type="$type" id="$for" name="$name" value="$value">
EOT;
	}
}