<?php

namespace Woption;

/**
 * Class AnyCommentOption is used to hold information regarding single section.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption
 */
class Option {
	/**
	 * @var string Options group.
	 */
	protected $option_group;

	/**
	 * @var string Option name.
	 */
	protected $option_name;

	/**
	 * @var string Page slug.
	 */
	protected $page_slug;

	/**
	 * @var BaseField[] List of fields.
	 */
	protected $fields = [];

	/**
	 * @var Section[]|null Section data.
	 */
	protected $sections = null;

	/**
	 * AnyCommentOption constructor.
	 *
	 * @param array $options List of options.
	 */
	public function __construct( $options ) {
		$this->option_group = $options['option_group'];
		$this->option_name  = $options['option_name'];
		$this->page_slug    = $options['page_slug'];
	}

	/**
	 * Set section.
	 *
	 * @param Section $section Associative list of section data.
	 *
	 * @return $this
	 */
	public function add_section( $section ) {
		$this->sections[] = $this->normalize_section( $section );

		return $this;
	}

	/**
	 * Get section.
	 *
	 * @return Section[]|null NULL in case when no section defined.
	 */
	public function get_sections() {
		return $this->sections;
	}

	/**
	 * Get list of fields.
	 *
	 * @return BaseField[]
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Set single field.
	 *
	 * @param BaseField $field Associative array data of single field.
	 *
	 * @return $this
	 */
	public function add_field( BaseField $field ) {
		$this->fields[] = $this->normalize_field( $field );

		return $this;
	}

	/**
	 * Set multiple fields at once.
	 *
	 * @param BaseField[] $fields Non associative list of field. Each item should be associative array of single field.
	 *
	 * @return $this
	 */
	public function add_fields( $fields ) {

		$this->fields = $this->normalize_fields( $fields );

		return $this;
	}

	/**
	 * Normalized fields.
	 *
	 * @param BaseField[] $fields List of fields to be normalized.
	 *
	 * @return BaseField[]|bool False in failure (e.g. empty list of fields). Normalized array of class fields.
	 */
	public function normalize_fields( $fields ) {

		if ( empty( $fields ) ) {
			return false;
		}

		$normalized_fields = [];

		foreach ( $fields as $field ) {
			if ( $fields instanceof BaseField ) {
				$checked_field = $this->normalize_field( $field );

				if ( $checked_field instanceof BaseField ) {
					$normalized_fields[] = $checked_field;
				}
			}
		}

		return $normalized_fields;
	}

	/**
	 * Normalize field.
	 *
	 * @param BaseField $field
	 *
	 * @return BaseField|false
	 */
	public function normalize_field( BaseField $field ) {
		$field_id = $field->get_id();

		if ( empty( $field_id ) ) {
			return false;
		}

		return $field;
	}

	/**
	 * Normalize section.
	 *
	 * @param Section $section
	 *
	 * @return Section|bool
	 */
	public function normalize_section( Section $section ) {
		if ( empty( $section ) ) {
			return false;
		}

		$section_id = $section->get_id();

		if ( empty( $section_id ) ) {
			return false;
		}

		return $section;
	}
}
