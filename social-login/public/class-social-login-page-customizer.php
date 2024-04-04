<?php
/**
 * This file contains the code that will modify the wp-login.php page and add require scripts to
 * render the social login button.
 *
 * @package social login
 */

namespace Public;

/**
 * This class is responsible for performing following actions:
 * 1. It enqueue the required script from google and facebook using the "login_enqueue_scripts" hook.
 * 2. Add the social login buttons in the footer.
 */
class Class_Social_Login_Page_Customizer {
	/**
	 * This method is used to enqueue custom scripts or stylesheets for the login page and to add
	 * social login buttons to the footer section of the login page.
	 */
	public function __construct() {
		add_action( 'login_enqueue_scripts', array( $this, 'custom_scripts' ) );
		add_action( 'login_footer', array( $this, 'add_buttons' ) );
		add_shortcode( 'd3v_social_login', array( $this, 'show_btn_on_frontend' ) );
	}

	/**
	 * This function will allow the users to show the social buttons on a custom login page.
	 */
	public function show_btn_on_frontend() {
		$this->custom_scripts();
		ob_start();
		$this->add_buttons();
		return ob_get_clean();
	}

	/**
	 * This method will be executed once the login_enqueue_scripts hook is triggered. It will check for
	 * user's logged in status and presence of the required settings. If user is not logged in and required
	 * settings is present only then it will add the custom scripts to the document.
	 */
	public function custom_scripts() {
		$options = get_option( 'social_login_options', array() );
		if ( is_user_logged_in() || empty( $options ) ) {
			return;
		}
		if ( 'on' === $options['social-login-google-active'] || 'on' === $options['social-login-facebook-active'] ) {
			wp_enqueue_style(
				'custom-login-style',
				plugin_dir_url( __FILE__ ) . 'css/custom-login-style.css',
				array(),
				D3V_PLUGIN_VER,
			);
		}
		if ( ! empty( $options['social-login-google-active'] ) && 'on' === $options['social-login-google-active'] && ! empty( $options['social-login-google-authid'] ) ) {

			wp_enqueue_script(
				'custom-script',
				'https://accounts.google.com/gsi/client',
				array(),
				D3V_PLUGIN_VER,
				array(
					'strategy'  => 'async',
					'in_footer' => false,
				)
			);
		}
		if ( ! empty( $options['social-login-facebook-active'] ) && 'on' === $options['social-login-facebook-active'] && ! empty( $options['social-login-facebook-authid'] ) ) {
			wp_enqueue_script(
				'd3v-jquery',
				'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
				array(),
				D3V_PLUGIN_VER,
				false,
			);
			wp_enqueue_script(
				'custom-script-fb',
				'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0&appId=' . $options['social-login-facebook-authid'],
				array(),
				D3V_PLUGIN_VER,
				array(
					'strategy'  => 'async',
					'in_footer' => false,
				)
			);
			wp_enqueue_script(
				'custom-script-d3v',
				plugin_dir_url( __FILE__ ) . 'js/custom-login-script.js',
				array( 'custom-script-fb', 'd3v-jquery' ),
				D3V_PLUGIN_VER,
				true
			);
			wp_localize_script(
				'custom-script-d3v',
				'ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'fb_signup_login' ),
				)
			);
		}
	}

	/**
	 * This method will be executed once the login_footer hook is triggered. It will check for
	 * user's logged in status and presence of the required settings. If user is not logged in and required
	 * settings is present only then it will add the social login buttons.
	 */
	public function add_buttons() {
		if ( is_user_logged_in() ) {
			return;
		}
		$options = get_option( 'social_login_options', array() );

		?>
		<div class="sl-container">
			<?php if ( ! empty( $options['social-login-google-active'] ) && 'on' === $options['social-login-google-active'] && ! empty( $options['social-login-google-authid'] ) ) { ?>
			<div id="g_id_onload"
				data-client_id="<?php echo esc_attr( $options['social-login-google-authid'] ); ?>"
				data-context="signin"
				data-ux_mode="popup"
				data-login_uri="<?php echo esc_url( get_site_url() . '/wp-json/d3v/v1/social-login/' ); ?>"
				data-auto_prompt="false">
			</div>
	
			<div class="g_id_signin"
				data-type="standard"
				data-shape="rectangular"
				data-theme="outline"
				data-text="continue_with"
				data-size="large"
				data-logo_alignment="left">
			</div>
				<?php
			}
			if ( ! empty( $options['social-login-facebook-active'] ) && 'on' === $options['social-login-facebook-active'] && ! empty( $options['social-login-facebook-authid'] ) ) {
				?>
			<div class="fb-login-button" 
				data-onlogin="login" 
				data-size="large" 
				data-button-type="continue_with" 
				data-layout="" 
				data-auto-logout-link="false" 
				data-use-continue-as="true">
			</div>
			<?php } ?>
		</div>
		<?php
	}
}
