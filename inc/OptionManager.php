<?php

namespace Woption;

/**
 * Class OptionManager is a core class to manage options.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption
 */
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
	protected $page_title;

	/**
	 * @var string Menu title
	 */
	protected $menu_title;

	/**
	 * @var string Menu icon.
	 */
	protected $menu_icon;

	/**
	 * @var int Menu position.
	 */
	protected $menu_position;

	/**
	 * @var string User capability to see page.
	 */
	protected $capability = 'manage_options';

	/**
	 * @var array Default options. When options specified in this list do not exist in the form options, default ones will be used instead.
	 */
	protected $default_options;

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
	 * @var OptionManager Instance of current object.
	 */
	private static $_instances;

	/**
	 * AC_Options constructor.
	 */
	public function __construct () {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Init class.
	 */
	public function init () {

		add_action( 'admin_menu', [ $this, 'init_menu' ] );

		$this->init_rest_api();
	}

	/**
	 * Register sidebar menu.
	 *
	 * Notice: it would not be registered in case of missing page or menu title.
	 *
	 * @return bool
	 */
	public function init_menu () {
		if ( empty( $this->page_title ) || empty( $this->menu_title ) ) {
			return false;
		}

		add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->page_slug,
			[ $this, 'render' ],
			$this->menu_icon,
			$this->menu_position
		);

		return true;
	}

	/**
	 * Initiate and register WP REST API and AJAX-related scripts.
	 */
	public function init_rest_api () {
		$action_name = $this->get_page_slug();

		add_action( 'rest_api_init', function () use ( $action_name ) {
			register_rest_route( 'anycomment/v1', "/$action_name/", array(
				'methods'  => 'POST',
				'callback' => [ $this, 'process_rest' ],
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
	});
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
	public function process_rest ( $request ) {

		$response = new \WP_REST_Response();

		if ( ! isset( $request['option_name'] ) ) {
			return new \WP_Error( 403, __( 'Option name is required', 'anycomment' ), [ 'status' => 403 ] );
		}

		$option_name = trim( $request['option_name'] );

		$options = $request->get_params();
		unset( $options['option_name'] );

		$this->update_db_option( $options, $option_name );

		$response->set_data( [
			'success' => true,
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
	public function add_option ( $options ) {
		$this->options[] = $options;
	}

	/**
	 * @return Option
	 */
	public function form () {
		$this->fielder = new Option( [
			'page_slug'    => $this->page_slug,
			'option_name'  => $this->option_name,
			'option_group' => $this->option_group,
		] );

		$this->add_option( $this->fielder );

		return $this->fielder;
	}

	/**
	 * Start section builder.
	 *
	 * @return Section
	 */
	public function section_builder () {
		return new Section( $this->section_options );
	}


	/**
	 * Renders and echoes options page.
	 */
	public function render () {
		$html = '';

		$options      = $this->options;
		$tabs_content = '';

		$html .= '<div class="wrap">';

		if ( ! empty( $this->page_title ) ) {
			$html .= '<h1>' . $this->page_title . '</h1>';
		}

		$form_content = '';
		foreach ( $options as $option ) {
			$sections = $option->get_sections();

			if ( ! empty( $sections ) ) {

				$tabs_content = '<h2 class="nav-tab-wrapper">';

				foreach ( $sections as $section ) {
					$section_title = $section->get_title();
					$form_content  .= <<<EOL

    <a href="#" class="nav-tab">$section_title</a>
</h2>
EOL;

					$form_content .= $section;
				}

				$tabs_content .= '</h2>';
			} else {
				$fields = $option->get_fields();
				foreach ( $fields as $field ) {
					$form_content .= $field;
				}
			}
		}

		$save = 'Save';

		$html .= <<<EOL
<form action="" id="{$this->get_page_slug()}" method="post" novalidate>
	<input type="hidden" name="option_name" value="{$this->option_name}">
		
	$tabs_content
	$form_content
	
	<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="$save">
	</p>
</form>	
EOL;

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Get page slug.
	 *
	 * @return mixed
	 */
	public function get_page_slug () {
		return str_replace( '-', '_', $this->page_slug );
	}

	/**
	 * Check whether there are any options set on model.
	 *
	 * @return bool
	 */
	public function has_options () {
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
	public function get_options () {
		return $this->options;
	}

	/**
	 * Get single option.
	 *
	 * @param string $name Options name to search for.
	 *
	 * @return mixed|null
	 */
	public function get_db_option ( $name ) {
		$options = $this->get_db_options();

		$optionValue = isset( $options[ $name ] ) ? trim( $options[ $name ] ) : null;

		return ! empty( $optionValue ) ? $optionValue : null;
	}

	/**
	 * Get list of social options.
	 *
	 * @return Option[]|null
	 */
	public function get_db_options () {

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
	public function update_db_option ( $value, $option_name = null ) {

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
	public static function instance () {
		$className = get_called_class();

		if ( ! isset( self::$_instances[ $className ] ) ) {
			self::$_instances[ $className ] = new $className( false );
		}

		return self::$_instances[ $className ];
	}
}
