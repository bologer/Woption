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
	 * @var array Associative list of properties passed to field class.
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
		add_action( 'admin_post_' . $this->option_name, [ $this, 'handle_form_request' ] );

		$this->handlers();
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
	 * Generic handlers.
	 */
	public function handlers () {
		$transient_key = $this->get_transient_key();
		$transient     = get_transient( $transient_key );

		if ( ! empty( $transient ) ) {
			$transient = (array) $transient;
			$type      = (string) $transient['type'];
			$message   = (string) $transient['message'];
			delete_transient( $transient_key );
			add_action( 'admin_notices', function () use ( $type, $message ) {
				?>
                <div class="notice notice-<?php echo $type ?> is-dismissible">
                    <p><?php esc_html_e( $message ) ?></p>
                </div>
				<?php
			} );
		}
	}

	/**
	 * Handle form request.
	 */
	public function handle_form_request () {

		$transient_key = $this->get_transient_key();

		$save_form = $this->save_form();

		if ( ! ( $save_form instanceof \WP_Error ) ) {
			set_transient( $transient_key, [
				'type'    => 'success',
				'message' => 'Saved successfully',
			], 60 );
		} else {
			set_transient( $transient_key, [
				'type'    => 'error',
				'message' => $save_form->get_error_message(),
			], 60 );
		}
		wp_redirect( isset( $_POST['redirect'] ) ? $_POST['redirect'] : '/' );
		exit();
	}

	/**
	 * Get transient key.
	 *
	 * @return string
	 */
	public function get_transient_key () {
		return $this->option_name . get_current_user_id();
	}


	/**
	 * Process form submission.
	 *
	 * @return mixed|\WP_Error
	 */
	public function save_form () {
		if ( ! wp_verify_nonce( $_POST['nonce'], $this->option_name ) ) {
			return new \WP_Error( '', 'Invalid nonce' );
		}

		$options = $_POST;

		unset( $options['redirect'] );
		unset( $options['nonce'] );

		// Removes issue when e.g. ' were changed to \' and after a few saves it was already \\ and so on
		$options = array_map( 'stripslashes_deep', $options );

		$opt = $this->get_options();

		/**
		 * @var $option Option
		 */
		foreach ( $opt as $option ) {
			$sections = $option->get_sections();

			if ( ! empty( $sections ) ) {
				foreach ( $sections as $section ) {
					$batch_insert = [];
					foreach ( $section->get_fields() as $field ) {
						$field_name = $field->get_id();
						if ( isset( $options[ $field_name ] ) ) {
							$batch_insert[ $field_name ] = $options[ $field_name ];
							unset( $options[ $field_name ] );
						}
					}

					$this->update_db_option( $batch_insert, $section->get_id() );
				}
			} else {
				$this->update_db_option( $options, $this->option_name );
			}
		}

		return true;
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
		$html = '<div class="wrap">';

		$options = $this->options;
		$html    .= '<form action="' . esc_url( admin_url( "admin-post.php" ) ) . '" id="' . $this->get_page_slug() . '" method="post" novalidate>';

		$redirect_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url( $_SERVER['REQUEST_URI'] ) : '';

		$html .= '<input type="hidden" name="redirect" value="' . $redirect_url . '">';
		$html .= '<input type="hidden" name="action" value="' . $this->option_name . '">';
		$html .= '<input type="hidden" name="nonce" value="' . wp_create_nonce( $this->option_name ) . '" />';

		foreach ( $options as $option ) {
			$sections = $option->get_sections();
			if ( ! empty( $sections ) ) {


				$section_tabs     = [];
				$section_contents = [];

				foreach ( $sections as $section ) {
					$section_url = esc_url( admin_url( 'admin.php?page=' . $this->get_page_slug() . '&tab=' . $section->get_id() ) );

					$section_tabs[] = '<a href="' . $section_url . '" class="nav-tab">' . $section->get_title() . '</a>';

					if ( isset( $_GET['tab'] ) && $_GET['tab'] === $section->get_id() ) {
						$section_contents[] = $section;
					}
				}

				$html .= '<h2 class="nav-tab-wrapper">';
				$html .= implode( "\n", $section_tabs );
				$html .= '</h2>';

				$html .= implode( "\n", $section_contents );


			} else {
				$fields = $option->get_fields();
				foreach ( $fields as $field ) {
					$html .= $field;
				}
			}
		}
		$html .= '<input type="submit" class="button" value="Save">';
		$html .= '</form>';

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Get page slug.
	 *
	 * @return mixed
	 */
	public function get_page_slug () {
		return $this->page_slug;
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
