<?php

namespace Woption\Fields;

/**
 * Class Checkbox is used to render regular <input> of checkbox type.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption\Fields
 */
class Checkbox extends Input {

	/**
	 * {@inheritdoc}
	 */
	protected $type = 'checkbox';

	/**
	 * {@inheritdoc}
	 */
	public function render_field () {
		$label             = $this->get_label();
		$for               = $this->get_label_for();
		$name              = $this->get_id();
		$value             = $this->get_value();
		$checked_attribute = $value !== null ? 'checked="checked"' : '';

		return <<<EOL
<fieldset>
	<legend class="screen-reader-text"><span>$label</span></legend>
	<label for="$for">
		<input name="$name" type="checkbox" id="$for" $checked_attribute> 
		$label
	</label>
</fieldset>
EOL;
	}
}