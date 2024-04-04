<?php
/**
 * This file contain admin side code for the plugin and is responsible for following things:
 * 1. Creating an custom endpoint using the WP_REST API.
 * 2. Verify the response(JWT Token) from the providers using third-party library.
 * 3. Once token is verified it is used retrieve user information if already present on the site log him in,
 *    if not create a new user and log him in.
 *
 * @package social login
 */

namespace Admin;

use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;

require dirname( __DIR__ ) . '/vendor/autoload.php';


/**
 * This class contain admin side code for the plugin and is responsible for following things:
 * 1. Creating an custom endpoint using the WP_REST API.
 * 2. Verify the response(JWT Token) from the providers using third-party library.
 * 3. Once token is verified it retrieve user information if already present on the site log him/her in,
 *    if not create a new user and log him/her in.
 */
class Class_Social_Signup_Login {
	/**
	 * This variable contains the object of the Parser Class for interpreting the JWT Token.
	 *
	 * @var parser.
	 */
	private $parser;

	/**
	 * This function initializes the Parser member variable and registers a method on rest_api_init hook so that a
	 * custom endpoint can be created.
	 */
	public function __construct() {
		$this->parser = new Parser( new JoseEncoder() );
		add_action( 'rest_api_init', array( $this, 'custom_endpoint_init' ) );
		add_action( 'wp_ajax_nopriv_continue_with_fb', array( $this, 'fb_signup_login' ) );
		add_filter( 'get_avatar', array( $this, 'custom_local_avatar' ), 10, 5 );
		add_filter( 'wp_login_errors', array( $this, 'custom_login_message' ), 10, 1 );
	}

	/**
	 * This function will notifies the user if login fails.
	 *
	 * @param WP_Error $error error obj.
	 */
	public function custom_login_message( $error ) {
		if ( isset( $_REQUEST['d3v_error_msg'] ) && ! empty( $_REQUEST['d3v_error_msg'] ) ) {
			return new \WP_Error( 'd3v-error-msg', sanitize_text_field( wp_unslash( $_REQUEST['d3v_error_msg'] ) ) );
		}
		return $error;
	}

	/**
	 * To remove dependence from gravatar for profile image this function is created.
	 * It will return locally stored image whenever get_gravatar method is called.
	 *
	 * @param string $avatar current avatar.
	 * @param mixed  $id_or_email user id or email.
	 * @param int    $size Height and width of the avatar in pixels.
	 * @param string $default Default value.
	 * @param string $alt Alt attribute value.
	 */
	public function custom_local_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		$user = false;
		if ( is_numeric( $id_or_email ) ) {
			$id   = (int) $id_or_email;
			$user = get_user_by( 'id', $id );
		} elseif ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				$id   = (int) $id_or_email->user_id;
				$user = get_user_by( 'id', $id );
			}
		} else {
			$user = get_user_by( 'email', $id_or_email );
		}
		if ( $user && is_object( $user ) && true == get_user_meta( $user->ID, 'is_social_acc', true ) ) {
			$local_avatar_url = get_user_meta( $user->ID, 'd3v_avatar', true );
			if ( empty( $alt ) ) {
				$alt = 'Profile Picture';
			}
			if ( ! empty( $local_avatar_url ) ) {
				$avatar = "<img alt='{$alt}' src='{$local_avatar_url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
			}
		}

		return $avatar;
	}

	/**
	 * This function check for the required details when a user logs in using facebook.
	 *
	 * @param array $data Data obtaineD from facebook.
	 */
	private function validate_fb_data( $data ) {
		$errors = array();

		if ( ! isset( $data['id'] ) || empty( $data['id'] ) ) {
			$errors[] = 'Unique Account Number missing';
		} elseif ( ! is_string( $data['first_name'] ) ) {
			$errors[] = 'Unique Account number should be a string';
		}

		if ( ! isset( $data['first_name'] ) || empty( $data['first_name'] ) ) {
			$errors[] = 'First name is missing';
		} elseif ( ! is_string( $data['first_name'] ) ) {
			$errors[] = 'First name should be a string';
		}

		if ( ! isset( $data['last_name'] ) || empty( $data['last_name'] ) ) {
			$errors[] = 'Last name is missing';
		} elseif ( ! is_string( $data['last_name'] ) ) {
			$errors[] = 'Last name should be a string';
		}

		if ( ! isset( $data['email'] ) || empty( $data['email'] ) ) {
			$errors[] = 'Email is missing';
		} elseif ( ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = 'Invalid email format';
		}

		if ( ! isset( $data['picture']['data']['url'] ) || empty( $data['picture']['data']['url'] ) ) {
				$errors[] = 'Picture is missing';
		} elseif ( ! filter_var( $data['picture']['data']['url'], FILTER_VALIDATE_URL ) ) {
			$errors[] = 'Picture should be a URL';
		}

		return $errors;
	}
	/***
	 * This function receives an POST request which is first verified and if further
	 * processing is done. Further processing includes sign-up if user not already present else
	 * log the user in.
	 */
	public function fb_signup_login() {
		check_ajax_referer( 'fb_signup_login', 'nonce' );
		$user_data = isset( $_POST['res'] ) ? $_POST['res'] : '';
		$errors    = $this->validate_fb_data( $user_data );
		if ( ! empty( $errors ) ) {
			$errors = implode( ',', $errors );
			$url    = wp_login_url() . '?d3v_error_msg=' . $errors;
			wp_send_json_error( $url, 400 );
		}
		$users = get_users(
			array(
				'meta_key'     => 'social_acc_fb_number',
				'meta_value'   => $user_data['id'],
				'meta_compare' => '=',
			)
		);
		if ( empty( $users ) ) {
			$user_by_email = get_user_by( 'email', $user_data['email'] );
			if ( ! $user_by_email ) {
				$this->sign_up_user_fb( $user_data );
				return;
			}
			update_user_meta( $user_by_email->ID, 'is_social_acc', true );
			update_user_meta( $user_by_email->ID, 'social_acc_fb_number', $user_data['id'] );
			$this->login_user( $user_by_email, true );
		}
		$this->login_user( $users[0], true );
	}

	/**
	 * This function create user on the site along with required meta.
	 *
	 * @param array $user_data Data using which the user's account will be created.
	 */
	private function sign_up_user_fb( $user_data ) {
		if ( empty( $user_data ) ) {
			return;
		}
		$role         = get_option( 'social_login_options', array() );
		$role         = empty( $role['social-login-facebook-role'] ) ? 'subscriber' : $role['social-login-facebook-role'];
		$user_details = array(
			'user_login' => $user_data['email'],
			'user_email' => $user_data['email'],
			'first_name' => $user_data['first_name'],
			'last_name'  => $user_data['last_name'],
			'role'       => $role,
		);
		$user_id      = wp_insert_user( $user_details );
		update_user_meta( $user_id, 'is_social_acc', true );
		update_user_meta( $user_id, 'd3v_avatar', $user_data['picture']['data']['url'] );
		update_user_meta( $user_id, 'social_acc_fb_number', $user_data['id'] );
		$user = get_user_by( 'ID', $user_id );
		$this->login_user( $user, true );
	}

	/**
	 * This function will create a custom endpoint at wp-json/d3v/v1/social-login. It has been configured only to
	 * receive HTTP Post Request.
	 */
	public function custom_endpoint_init() {
		register_rest_route(
			'd3v/v1',
			'/social-login/',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'custom_endpoint_callback' ),
			),
		);
	}
	/**
	 * This function is triggered when google send us the data via HTTP POST for the user at url wp-json/d3v/v1/social-login.
	 */
	public function custom_endpoint_callback() {
		if ( ! $this->verify_csrf() ) {
			$error_message = 'Unable to pass the csrf check';
			wp_safe_redirect( home_url( '/wp-login.php?d3v_error_msg=' . $error_message ) );
			exit();
		}
		$parsed_token = $this->verify_token();
		if ( false === $parsed_token ) {
			$error_message = 'Unable to verify the token';
			wp_safe_redirect( home_url( '/wp-login.php?d3v_error_msg=' . $error_message ) );
			exit();
		}
		$users = get_users(
			array(
				'meta_key'     => 'social_acc_google_number',
				'meta_value'   => $parsed_token->claims()->get( 'sub' ),
				'meta_compare' => '=',
			)
		);
		if ( empty( $users ) ) {
			$user_by_email = get_user_by( 'email', $parsed_token->claims()->get( 'email' ) );
			if ( ! $user_by_email ) {
				$this->sign_up_user( $parsed_token );
				return;
			}
			update_user_meta( $user_by_email->ID, 'is_social_acc', true );
			update_user_meta( $user_by_email->ID, 'social_acc_google_number', $parsed_token->claims()->get( 'sub' ) );
			$this->login_user( $user_by_email );
		}
		$this->login_user( $users[0] );
	}

	/**
	 * This function verify the CSRF token and prevent the CSRF attack.
	 */
	private function verify_csrf() {
		if ( isset( $_COOKIE['g_csrf_token'] ) && isset( $_REQUEST['g_csrf_token'] ) && $_COOKIE['g_csrf_token'] === $_REQUEST['g_csrf_token'] ) {
			return true;
		}
		return false;
	}

	/** This function will decode JWT token and verifies the authenticity of the same */
	private function verify_token() {
		try {
			$options    = get_option( 'social_login_options', array() );
			$credential = isset( $_POST['credential'] ) ? $_POST['credential'] : '';
			$token      = $this->parser->parse( $credential );
			$aud        = $token->claims()->get( 'aud' );
			$iss        = $token->claims()->get( 'iss' );
			$exp        = $token->claims()->get( 'exp' );
			if ( empty( $iss ) || ( 'accounts.google.com' !== $iss && 'https://accounts.google.com' !== $iss ) ) {
				return false;
			}
			if ( empty( $aud ) || empty( $options['social-login-google-authid'] ) || $options['social-login-google-authid'] !== $aud[0] ) {
				return false;
			}
			if ( empty( $exp ) || time() > $exp->getTimestamp() ) {
				return false;
			}
		} catch ( CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e ) {
			return false;
		}
		return $token;
	}
	/**
	 * This function will check if the user already exist in the system or not.If user
	 * exist then he will be directly logged in.If not user will be created and then logged in.
	 *
	 * @param Object $parsed_token contains decoded JWT token.
	 */
	private function sign_up_user( $parsed_token ) {
		if ( empty( $parsed_token ) ) {
			return;
		}
		$role      = get_option( 'social_login_options', array() );
		$role      = empty( $role['social-login-google-role'] ) ? 'subscriber' : $role['social-login-google-role'];
		$user_data = array(
			'user_login' => $parsed_token->claims()->get( 'email' ),
			'user_email' => $parsed_token->claims()->get( 'email' ),
			'first_name' => $parsed_token->claims()->get( 'given_name' ),
			'last_name'  => $parsed_token->claims()->get( 'family_name' ),
			'role'       => $role,
		);
		$user_id   = wp_insert_user( $user_data );
		update_user_meta( $user_id, 'is_social_acc', true );
		update_user_meta( $user_id, 'd3v_avatar', $parsed_token->claims()->get( 'picture' ) );
		update_user_meta( $user_id, 'social_acc_google_number', $parsed_token->claims()->get( 'sub' ) );
		$user = get_user_by( 'ID', $user_id );
		$this->login_user( $user );
	}
	/**
	 * This function will login the user by setting the authentication cookie.
	 *
	 * @param WP_User $user The user who must be logged in.
	 * @param bool    $is_ajax Whether an ajax request.
	 */
	private function login_user( $user, $is_ajax = false ) {
		if ( false === $user ) {
			return;
		}
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );
		$redirect_to = user_admin_url();
		if ( $is_ajax ) {
			wp_send_json_success( $redirect_to, 200 );
		}
		wp_safe_redirect( $redirect_to );
		exit();
	}
}
