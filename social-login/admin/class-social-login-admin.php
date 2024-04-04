<?php
/**
 * This file contain admin side code for the plugin and is responsible for following things:
 * 1. Creating a menu page.
 * 2. Adding custom settings to the above created menu page using the Settings API.
 *
 * @package social login
 */

namespace Admin;

/**
 * This class is responsible for creating a menu page and adding settings to the same.
 */
class Class_Social_Login_Admin {
	/**
	 * This Function will create an custom setting page allowing users to set up their
	 * own integration with google and facebook.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'd3v_add_menu_page' ), 99 );
		add_action( 'admin_init', array( $this, 'd3v_settings_init' ) );
	}
	/**
	 * This is a wrapper function which adds a custom menu page.
	 */
	public function d3v_add_menu_page() {
		add_menu_page(
			__( 'Social Login Settings', 'd3v-social-login' ),
			__( 'Social Login Settings', 'd3v-social-login' ),
			'manage_options',
			'd3v-social-login-options',
			array( $this, 'd3v_render_menu' ),
			'dashicons-admin-generic',
			65
		);
	}
	/**
	 * This function will add sections and settings to the menu page.
	 */
	public function d3v_render_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		settings_errors();
		?>
		<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<form action="options.php" method="post">
		<?php
		settings_fields( 'd3v-social-login-options' );
		do_settings_sections( 'd3v-social-login-options' );
		submit_button( 'Save Settings' );
		?>
		</form>
		</div>
		<?php
	}
	/**
	 * This is a wrapper function which creates settings.
	 */
	public function d3v_settings_init() {
		register_setting( 'd3v-social-login-options', 'social_login_options' );
		$this->render_settings_section( 'd3v-social-login-google', 'Google' );
		$this->render_settings_section( 'd3v-social-login-facebook', 'Facebook' );
		$this->render_settings_field(
			'social-login-google-active',
			'Active',
			'd3v-social-login-google',
			array(
				'label_for' => 'social-login-google-active',
				'type'      => 'checkbox',
			)
		);
		$this->render_settings_field(
			'social-login-google-authid',
			'App ID',
			'd3v-social-login-google',
			array(
				'label_for' => 'social-login-google-authid',
				'type'      => 'textbox',
			)
		);
		$this->render_settings_field(
			'social-login-google-role',
			'Select default role',
			'd3v-social-login-google',
			array(
				'label_for' => 'social-login-google-role',
				'type'      => 'role-dropdown',
			)
		);
		$this->render_settings_field(
			'social-login-facebook-active',
			'Active',
			'd3v-social-login-facebook',
			array(
				'label_for' => 'social-login-facebook-active',
				'type'      => 'checkbox',
			)
		);
		$this->render_settings_field(
			'social-login-facebook-authid',
			'App ID',
			'd3v-social-login-facebook',
			array(
				'label_for' => 'social-login-facebook-authid',
				'type'      => 'textbox',
			)
		);
		$this->render_settings_field(
			'social-login-facebook-role',
			'Select default role',
			'd3v-social-login-facebook',
			array(
				'label_for' => 'social-login-facebook-role',
				'type'      => 'role-dropdown',
			)
		);
	}
	/**
	 * This is a wrapper function which creates settings.
	 *
	 * @param string $id ID.
	 * @param string $title Title.
	 * @param string $section section id where field must be displayed.
	 * @param string $additional_data Information about the field type i.e textbox,radio.
	 */
	private function render_settings_field( $id, $title, $section, $additional_data ) {
		add_settings_field(
			$id,
			__( $title, 'd3v-social-login' ),
			array( $this, 'render_field_cb' ),
			'd3v-social-login-options',
			$section,
			$additional_data
		);
	}
	/**
	 * This is a wrapper function which creates sections.
	 *
	 * @param string $slug Unique Identifier.
	 * @param string $title Title.
	 */
	private function render_settings_section( $slug, $title ) {
		add_settings_section(
			$slug,
			__( $title, 'd3v-social-login' ),
			array( $this, 'section_callback' ),
			'd3v-social-login-options',
		);
	}
	/**
	 * This is a wrapper function which creates settings.
	 *
	 * @param string $additional_data Information about the field type i.e textbox,radio.
	 */
	public function render_field_cb( $additional_data ) {
		$val = get_option( 'social_login_options', array() );
		switch ( $additional_data['type'] ) {
			case 'checkbox':
				?>
					<input 
						type="checkbox" 
						name="social_login_options[<?php echo esc_attr( $additional_data['label_for'] ); ?>]" 
						id="<?php echo esc_attr( $additional_data['label_for'] ); ?>"
						<?php echo 'on' === $val[ $additional_data['label_for'] ] ? 'checked' : ''; ?> 
					/>
				<?php
				break;
			case 'textbox':
				?>
					<input 
						type="text" 
						name="social_login_options[<?php echo esc_attr( $additional_data['label_for'] ); ?>]" 
						id="<?php echo esc_attr( $additional_data['label_for'] ); ?>"
						value = "<?php echo esc_attr( $val[ $additional_data['label_for'] ] ); ?>" 
					/>
				<?php
				break;
			case 'role-dropdown':
				?>
				<select
					name="social_login_options[<?php echo esc_attr( $additional_data['label_for'] ); ?>]" 
					id="<?php echo esc_attr( $additional_data['label_for'] ); ?>"
				>
				<?php
				$roles         = wp_roles()->get_names();
				$selected_role = empty( $val[ $additional_data['label_for'] ] ) ? 'subscriber' : $val[ $additional_data['label_for'] ];
				foreach ( $roles as $role => $name ) {
					echo '<option value="' . esc_attr( $role ) . '"' . selected( $selected_role, $role, false ) . '>' . esc_html( $name ) . '</option>';
				}
				?>
				</select>
				<?php
			default:
				// code...
				break;
		}
	}
	/**
	 * This function can be used to add description to a section.
	 */
	public function section_callback() {
	}
}
