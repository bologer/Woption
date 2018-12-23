<?php

namespace Woption;


class OptionManager {
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
	 * @var string Page header.
	 */
	protected $page_header;

	/**
	 * @var array Default options. When options specified in this list do not exist in the form options, default ones will be used instead.
	 */
	protected $default_options;

	/**
	 * @var OptionManager Instance of current object.
	 */
	private static $_instances;

	/**
	 * @var Option|null
	 */
	public $fielder = null;

	/**
	 * @var null|Option[]
	 */
	public $options = null;

	/**
	 * @var string Associative list of properties passed to field class.
	 * @see Section constructor for further information about passed options.
	 */
	protected $section_options = [];

	/**
	 * @var array Associative list of properties passed to field class.
	 * @see AnyCommentField constructor for further information about passed options.
	 */
	protected $field_options = [];

	/**
	 * AC_Options constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Init class.
	 */
	public function init() {
		register_setting( $this->option_group, $this->option_name );

		$action_name = $this->get_page_slug();

		add_action( 'rest_api_init', function () use ( $action_name ) {
			register_rest_route( 'anycomment/v1', "/$action_name/", array(
				'methods'  => 'POST',
				'callback' => [ $this, 'process_rest' ]
			) );
		} );

		add_action( 'admin_footer', function () use ( $action_name ) {
			$form_id         = '#' . $this->get_page_slug();
			$url             = rest_url( 'anycomment/v1/' . $action_name );
			$success_message = __( "Settings saved", 'anycomment' );
			$js              = <<<JS
jQuery('$form_id').on('submit', function(e) {
	e.preventDefault();
	
	var data = jQuery(this).serialize();
	
	if(!data) {
	    return false;
	}
	
	jQuery.ajax({
	    method: 'POST',
	    url: '$url',
	    data: data,
	    success: function(data) {
	        if(data.success) {
	            alert('$success_message');
	        }
	    },
	    error: function(err) {
	        console.log(err);
	    }
	})
	
	console.log();
});
JS;
			echo '<script>' . $js . '</script>';
		} );
	}

	/**
	 * Process REST request to save the form.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_Error|\WP_REST_Response
	 */
	public function process_rest( $request ) {

		$response = new \WP_REST_Response();

		if ( ! isset( $request['option_name'] ) ) {
			return new \WP_Error( 403, __( 'Option name is required', 'anycomment' ), [ 'status' => 403 ] );
		}

		$option_name = trim( $request['option_name'] );

		$options = $request->get_params();
		unset( $options['option_name'] );

		$this->update_db_option( $options, $option_name );

		$response->set_data( [
			'success' => true
		] );

		return rest_ensure_response( $response );
	}

	/**
	 * Add new option to the list.
	 *
	 * @param Option[]
	 *
	 * @return void
	 */
	public function add_option( $options ) {
		$this->options[] = $options;
	}

	/**
	 * @return Option
	 */
	public function form() {
		$this->fielder = new Option( [
			'page_slug'    => $this->page_slug,
			'option_name'  => $this->option_name,
			'option_group' => $this->option_group
		] );

		$this->add_option( $this->fielder );

		return $this->fielder;
	}

	/**
	 * Start section builder.
	 *
	 * @return Section
	 */
	public function section_builder() {
		return new Section( $this->section_options );
	}

	/**
	 * Start building new field.
	 *
	 * @return Field
	 */
	public function field_builder() {

		/**
		 * Set page slug for field when not defined on the class level.
		 */
		$options = $this->field_options;
		if ( ! isset( $options['option_name'] ) ) {
			$options['option_name'] = $this->option_name;
		}

		return new Field( $options );
	}

	public function run() {
		$html = '';

		$options = $this->options;

		$html .= '<div class="wrap">';

		if ( ! empty( $this->page_header ) ) {
			$html .= '<h1>' . $this->page_header . '</h1>';
		}

		$html .= '<table class="form-table">';

		$html .= '<tbody>';

		$html .= '<form action="" id="' . $this->get_page_slug() . '" method="post" class="anycomment-form" novalidate>';

		$html .= '<input type="hidden" name="option_name" value="' . $this->option_name . '">';

		foreach ( $options as $option ) {
			$sections = $option->get_sections();

			if ( ! empty( $sections ) ) {
				foreach ( $sections as $section ) {
					$html .= $section;
				}
			} else {
				$fields = $option->get_fields();
				foreach ( $fields as $field ) {
					$html .= $field;
				}
			}
		}

		$html .= '<input type="submit" value="' . __( 'Save', 'anycomment' ) . '">';

		$html .= '</form>';

		$html .= '</tbody>';

		$html .= '</table>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get page slug.
	 *
	 * @return mixed
	 */
	public function get_page_slug() {
		return str_replace( '-', '_', $this->page_slug );
	}

	/**
	 * Display tabbed menu.
	 *
	 * @param string $page
	 */
	protected function do_tab_menu() {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		echo '<ul>';

		$i = 0;
		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			$activeClass = $i === 0 ? 'class="current"' : '';
			echo '<li ' . $activeClass . ' data-tab="' . $section['id'] . '">
				<a href="#tab-' . $section['id'] . '">' . $section['title'] . '</a>
				</li>';
			$i ++;
		}
		echo '</ul>';

		?>
        <script>
            var $ = jQuery;
            $('.anycomment-tabs__menu li').on('click', function (e) {
                e.preventDefault();
                doTab($(this));
                return false;
            });


            function doTab(el) {
                var $ = jQuery,
                    data = (el.attr('data-tab') || ''),
                    tab_id = (data.indexOf('#tab-') === -1 ? ('#tab-' + data) : data);

                if (!data) {
                    return false;
                }

                $('.anycomment-tabs__menu li').removeClass('current');
                $('.anycomment-tabs__container__tab').removeClass('current');

                el.addClass('current');
                $(tab_id).addClass('current');
            }

            $(document).ready(function () {
                var hash = window.location.hash.trim();
                if (hash !== '') {
                    var cleanedHash = hash.replace('#tab-', '');
                    console.log(cleanedHash);
                    doTab($('[data-tab="' + cleanedHash + '"]'));
                }
            });
        </script>
		<?php
	}

	/**
	 * Check whether there are any options set on model.
	 *
	 * @return bool
	 */
	public function has_options() {
		$options = $this->get_db_options();

		if ( $options === null ) {
			return false;
		}

		$nonEmptyCount = 0;
		foreach ( $options as $key => $optionValue ) {
			if ( ! empty( $optionValue ) ) {
				$nonEmptyCount ++;
			}
		}

		return $nonEmptyCount > 0;
	}

	/**
	 * Get list of available options.
	 *
	 * @return Option[]|null
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get single option.
	 *
	 * @param string $name Options name to search for.
	 *
	 * @return mixed|null
	 */
	public function get_db_option( $name ) {
		$options = $this->get_db_options();

		$optionValue = isset( $options[ $name ] ) ? trim( $options[ $name ] ) : null;

		return ! empty( $optionValue ) ? $optionValue : null;
	}

	/**
	 * Get list of social options.
	 *
	 * @return Option[]|null
	 */
	public function get_db_options() {

		$option = get_option( $this->option_name, null );

		// When options are not defined yet and there are some default ones,
		// set them for user
		if ( $option === null && ! empty( $this->default_options ) ) {
			$this->update_db_option( $this->default_options, $this->option_name );
		}

		return $option;
	}

	/**
	 * Update db option value.
	 *
	 * @param mixed $value Value of the option.
	 * @param null|string $option_name Option name. When not specified current option_name will be used.
	 */
	public function update_db_option( $value, $option_name = null ) {

		$option = null;

		if ( $option_name === null ) {
			$option = $this->option_name;
		} else {
			$option = $option_name;
		}

		update_option( $option, $value );
	}

	/**
	 * Get instance of currently running class.
	 *
	 * @return self
	 */
	public static function instance() {
		$className = get_called_class();

		if ( ! isset( self::$_instances[ $className ] ) ) {
			self::$_instances[ $className ] = new $className( false );
		}

		return self::$_instances[ $className ];
	}
}
