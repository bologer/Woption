<?php

namespace Woption\Fields;

use Woption\BaseHtml;

/**
 * Class Field is used to hold information regarding single field item in the form.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption
 */
abstract class BaseField {
	/**
	 * @var string Field id.
	 */
	protected $id;

	/**
	 * @var string Field title.
	 */
	protected $title;

	/**
	 * @var string Label value.
	 */
	protected $label;

	/**
	 * @var string|null For attribute type pointing to the form field itself.
	 */
	protected $label_for;

	/**
	 * @var string|null Description displayed below the field.
	 */
	protected $description = null;

	/**
	 * @var null|string|callable Custom content displayed before field.
	 */
	protected $before = null;

	/**
	 * @var null|string|callable Custom content displayed after field.
	 */
	protected $after = null;

	/**
	 * @var string|null
	 */
	protected $hint = null;

	/**
	 * @var array List of additional arguments.
	 */
	protected $args = [];

	/**
	 * @var array List of field attributes.
	 */
	protected $field_attributes = [];

	/**
	 * @var array List of on events.
	 */
	protected $client_events = [];

	/**
	 * @var null|string Page slug to which option belongs.
	 */
	protected $option_name = null;

	/**
	 * @var string Wrapper class name.
	 */
	protected $wrapper_class = 'woption-field';

	/**
	 * @var string Field wrapping element.
	 */
	protected $wrapper = '<div {attributes}>{content}</div>';

	/**
	 * AnyCommentField constructor.
	 *
	 * @param array $options Associative list of options to set object properties.
	 */
	public function __construct ( array $options = [] ) {
		if ( ! empty( $options ) ) {
			foreach ( $options as $key => $value ) {
				$this->$key = $value;
			}
		}

		$this->init();
	}

	/**
	 * Invoked after constructor.
	 */
	public function init () {

	}

	/**
	 * @return string
	 */
	public function get_id () {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function set_id ( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_title () {
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function set_title ( $title ) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Set label.
	 *
	 * @param string $label
	 *
	 * @return $this
	 */
	public function set_label ( $label ) {
		$this->label = $label;

		return $this;
	}

	/**
	 * Get label value.
	 *
	 * @return string
	 */
	public function get_label () {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function get_label_for () {

		$label_for = $this->label_for;

		if ( ! empty( $label_for ) ) {
			return $label_for;
		}

		return $this->get_id();
	}

	/**
	 * @param string $label_for
	 *
	 * @return $this
	 */
	public function set_label_for ( $label_for ) {
		$this->label_for = $label_for;

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function get_description () {
		return $this->description;
	}

	/**
	 * @param null|string $description
	 *
	 * @return $this
	 */
	public function set_description ( $description ) {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return null|callable|string
	 */
	public function get_before () {
		return $this->before;
	}

	/**
	 * @param null|callable|string $before
	 *
	 * @return $this
	 */
	public function set_before ( $before ) {
		$this->before = $before;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_field_attributes () {
		return $this->field_attributes;
	}

	/**
	 * @param array $field_attributes
	 *
	 * @return $this
	 */
	public function set_field_attributes ( $field_attributes ) {
		$this->field_attributes = $field_attributes;

		return $this;
	}

	/**
	 * Add new field attribute.
	 *
	 * @param string $key Attribute key.
	 * @param string $value Attribute value.
	 */
	public function add_field_attribute ( $key, $value ) {
		$this->field_attributes[ $key ] = $value;
	}

	/**
	 * @return callable|string
	 */
	public function get_after () {
		return $this->after;
	}

	/**
	 * @param callable|string $after
	 *
	 * @return $this
	 */
	public function set_after ( $after ) {

		if ( is_callable( $after ) ) {
			$this->after = call_user_func( $after );
		} else {
			$this->after = $after;
		}


		return $this;
	}

	/**
	 * @return null|string
	 */
	public function get_hint () {
		return $this->hint;
	}

	/**
	 * @param null|string $hint
	 *
	 * @return $this
	 */
	public function set_hint ( $hint ) {
		$this->hint = $hint;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_args () {
		return $this->args;
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_args ( $args ) {
		$this->args = $args;

		return $this;
	}

	/**
	 * Get field value is was previously set.
	 *
	 * @return null|string
	 */
	public function get_value () {
		$options = get_option( $this->option_name, null );

		if ( null === $options ) {
			return null;
		}

		$value = isset( $options[ $this->get_id() ] ) ? $options[ $this->get_id() ] : null;

		if ( $value === null ) {
			return null;
		}

		switch ( $value ) {
			case '1':
			case 'true':
			case 'on':
				return true;
			case '0':
			case 'false':
			case 'off':
				return false;
		}

		return $value;
	}


	/**
	 * Places client event on current field.
	 *
	 * @param string $event Event function name, e.g. click, change, etc.
	 * @param string $animation Function name or complete structure as animate() to be used for animation.
	 * @param array|string $elements Non associative list of elements IDs or classes. When no "#" or "." specified,
	 * "#" will be automatically added to such elements.
	 *
	 * @return $this
	 */
	public function on ( $event, $animation, $elements ) {
		$this->client_events[] = [
			'event'     => $event,
			'animation' => $animation,
			'elements'  => $elements,
		];

		return $this;
	}

	/**
	 * Render client events into JavaScript events.
	 *
	 * @return string
	 */
	public function render_client_events () {
		$events = $this->client_events;

		if ( empty( $events ) ) {
			return '';
		}

		$event_rendered = '';

		foreach ( $events as $event ) {
			$event_name = isset( $event['event'] ) ? $event['event'] : null;
			$animation  = isset( $event['animation'] ) ? $event['animation'] : null;
			$elements   = isset( $event['elements'] ) ? $event['elements'] : null;

			if ( $event_name === null || $animation === null || $elements === null ) {
				continue;
			}

			foreach ( $elements as $key => $element ) {
				if ( ! preg_match( '/^[#.]/', $element ) ) {
					$elements[ $key ] = '#' . $element;
				}
			}

			$imploded_elements = implode( ', ', $elements );

			// When plain function name passed, can add () to it,
			// as it can be passed as animate({ ... })
			if ( false === strpos( $animation, '(' ) ) {
				$animation = $animation . '()';
			}

			$label_for = $this->get_label_for();

			$event_rendered .= <<<JS
$('#$label_for').on('$event_name', function() {
    $('$imploded_elements').$animation;
});
JS;
		}

		return <<<JS
<script>
	jQuery(document).ready(function() {
    	$event_rendered
	});
</script>
JS;
	}

	/**
	 * Override of magic method to convert class to string.
	 *
	 * @return string
	 */
	public function __toString () {
		return $this->render();
	}

	/**
	 * Render field HTML.
	 *
	 * @return string
	 */
	abstract public function render_field ();

	/**
	 * Render field attributes.
	 *
	 * @return string
	 */
	public function render_field_attributes () {
		$attr_html        = '';
		$field_attributes = $this->field_attributes;


		if ( empty( $field_attributes ) ) {
			return $attr_html;
		}

		foreach ( $field_attributes as $attribute => $value ) {
			$attr_html .= " $attribute=\"{$value}\" ";
		}

		return $attr_html;
	}

	/**
	 * Renders field into HMTL.
	 *
	 * @return string
	 */
	protected function render () {

		$label_for   = $this->get_label_for();
		$label_title = $this->get_title();
		$description = $this->get_description();

		$label       = '<label for="' . $label_for . '">' . $label_title . '</label>';
		$description = ! empty( $description ) ?
			'<p class="description">' . $description . '</p>' :
			'';

		$html = '';

		$html .= '<tr scope="row">';

		$html .= $this->get_before();

		$html .= '<th>';
		$html .= $label;
		$html .= '</th>';

		$html       .= '<td>';
		$attributes = [
			'class' => $this->wrapper_class . ' ' . ( $this->wrapper_class . '-' . $this->get_id() ),
		];
		$html       .= str_replace( [ '{content}', '{attributes}' ], [
			$this->render_field(),
			BaseHtml::renderTagAttributes( $attributes ),
		], $this->wrapper );

		$html .= $description;
		$html .= '<td>';

		$html .= $this->get_after();

		$html .= $this->render_client_events();

		$html .= '</tr>';

		return $html;
	}
}
