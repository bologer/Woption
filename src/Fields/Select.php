<?php

namespace Woption\Fields;

/**
 * Class Select is used to render regular <select> HTML dropdown element.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption\Fields
 */
class Select extends BaseField {

	/**
	 * @var array List of options rendered in the dropdown.
	 */
	protected $options = [];

	/**
	 * @var null|string Key name of selected option.
	 */
	protected $selected = null;

	/**
	 * Set list of options.
	 *
	 * @param array $options
	 *
	 * @return $this
	 */
	public function set_options ( $options ) {
		$this->options = $options;

		return $this;
	}

	/**
	 * @param null|string $selected
	 *
	 * @return $this
	 */
	public function set_selected ( $selected ) {
		$this->selected = $selected;

		return $this;
	}

	/**
	 * Render options for <select> tag.
	 *
	 * @return string
	 */
	public function render_options () {
		if ( empty( $this->options ) ) {
			return '';
		}

		$html = '';

		foreach ( $this->options as $key => $value ) {
			$selected = '';

			if ( ! empty( $this->selected ) && $this->selected == $key ) {
				$selected = 'selected="selected"';
			}

			$html .= <<<EOL
<option value="$key" $selected>$value</option>
EOL;
		}

		return $html;
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_field () {
		$options = $this->render_options();

		$this->set_field_attributes( [
			'name' => $this->get_id(),
			'id'   => $this->get_id(),
		] );

		$attributes = $this->render_field_attributes();

		return <<<EOL
<select $attributes>
	$options
</select>
EOL;
	}
}