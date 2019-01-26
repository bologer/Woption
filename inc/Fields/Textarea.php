<?php

namespace Woption\Fields;

/**
 * Class Textarea is used to render <textarea> HTML element.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption\Fields
 */
class Textarea extends BaseField {

	/**
	 * @var int Number of rows in textarea. Default: 5.
	 */
	protected $rows = 5;

	/**
	 * @return int
	 */
	public function get_rows () {
		return $this->rows;
	}

	/**
	 * @param int $rows
	 *
	 * @return $this
	 */
	public function set_rows ( $rows ) {
		$this->rows = $rows;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_field () {
		$value = $this->get_value();

		$this->set_field_attributes( [
			'rows' => $this->get_rows(),
			'for'  => $this->get_label_for(),
			'name' => $this->get_id(),
			'id'   => $this->get_id(),
		] );

		$attributes = $this->render_field_attributes();

		return <<<EOT
            <textarea $attributes>$value</textarea>
EOT;
	}
}