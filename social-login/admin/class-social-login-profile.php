<?php
/**
 * This file contain admin side code for the plugin and is responsible for following things:
 * 1. Creating a shortcode allowing users to see their own profile.
 * 2. Modifying the profile.php in the backend to use local images instead of gravatar.
 * 3. Modifying the user dropdown(wp_user_dropdown()).
 *
 * @package social login
 */

namespace Admin;

/**
 * This class is responsible for following functionalities:
 * 1. Creating a shortcode allowing users to see their own profile.
 * 2. Modifying the profile.php in the backend to use local images instead of gravatar.
 * 3. Modifying the user dropdown(wp_user_dropdown()).
 */
class Class_Social_Login_Profile {

	/**
	 * This function will be triggered as soon as the class is instantiated.
	 */
	public function __construct() {
		add_shortcode( 'd3v_profile', array( $this, 'show_profile' ) );
		add_action( 'wp_ajax_update_d3v_user_profile', array( $this, 'update_d3v_user_profile' ) );
		add_filter( 'user_profile_picture_description', array( $this, 'add_avatar_update_btn' ), 10, 2 );
		add_filter( 'wp_dropdown_users', array( $this, 'd3v_modify_dropdown' ), 10, 1 );
	}
	/**
	 * This function change the settings of the profile.php allowing user to upload an image to be used as an avatar
	 * instead of having an profile on gravatar.
	 *
	 * @param string  $description HTML content.
	 * @param WP_User $user logged-in user.
	 */
	public function add_avatar_update_btn( $description, $user ) {
		if ( empty( $user ) || true != get_user_meta( $user->ID, 'is_social_acc', true ) ) {
			return $description;
		}
		wp_enqueue_script(
			'd3v-jquery',
			'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
			array(),
			D3V_PLUGIN_VER,
			false,
		);
		wp_enqueue_script(
			'custom-profile-script-backend',
			plugin_dir_url( __FILE__ ) . 'js/custom-profile-backend.js',
			array(),
			D3V_PLUGIN_VER,
			true,
		);
		wp_localize_script(
			'custom-profile-script-backend',
			'ajax_object',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'update_d3v_user_profile' ),
				'selected_user' => $user->ID,
			)
		);
		return '<input type="file" id="d3v-profile-img" accept="image/*">';
	}
	/**
	 * This function will disable the user dropdown for users without manage-options permission.
	 *
	 * @param string $output HTML of the dropdown.
	 */
	public function d3v_modify_dropdown( $output ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			$output = substr_replace( $output, 'disabled ', 8, 0 );
		}
		return $output;
	}
	/**
	 * This function will update allow user to update their profile and admins
	 * to update their profiles as well as other's profile.
	 */
	public function update_d3v_user_profile() {
		check_ajax_referer( 'update_d3v_user_profile', 'nonce' );
		if ( ! isset( $_POST['selected_user'] ) || empty( $_POST['selected_user'] ) ) {
			wp_send_json_error( 'Please select a user.', 400 );
		}
		do_action( 'personal_options_update', sanitize_text_field( wp_unslash( $_POST['selected_user'] ) ) );
		if ( isset( $_POST['profile_pic'] ) ) {
			$profile_pic = empty( $_POST['profile_pic'] ) ? wp_send_json_error( 'Image missing', 400 ) : $_POST['profile_pic'];
			$result      = update_user_meta( sanitize_text_field( wp_unslash( $_POST['selected_user'] ) ), 'd3v_avatar', $profile_pic );
			if ( ! $result ) {
				wp_send_json_error( 'You have uploaded the same image.', 400 );
			}
		}
		if ( ! empty( $_POST['profile_update'] ) && true == $_POST['profile_update'] ) {
			if ( empty( $_POST['fn'] ) || empty( $_POST['ln'] ) || empty( $_POST['email'] ) ) {
				wp_send_json_error( 'All fields are required', 400 );
			}
			$user = get_user_by( 'ID', sanitize_text_field( wp_unslash( $_POST['selected_user'] ) ) );
			$user->__set( 'first_name', sanitize_text_field( wp_unslash( $_POST['fn'] ) ) );
			$user->__set( 'last_name', sanitize_text_field( wp_unslash( $_POST['ln'] ) ) );
			$user->__set( 'user_email', sanitize_email( wp_unslash( $_POST['email'] ) ) );
			$result = wp_update_user( $user );
			if ( is_int( $result ) && intval( $_POST['selected_user'] ) === get_current_user_id() ) {
				wp_send_json_success(
					'Profile has been updated. Kindly review the new email inbox in case the email address has been updated.',
					200
				);
			}
			if ( is_int( $result ) && intval( $_POST['selected_user'] ) !== get_current_user_id() ) {
				wp_send_json_success(
					'Profile has been updated.',
					200
				);
			}
			wp_send_json_error( $result->get_error_message(), 400 );
		}
	}
	/**
	 * This function will output profile details of the logged in user and for users with manage_options permission it
	 * also allow to switch to different user.
	 */
	public function show_profile() {
		if ( 0 === get_current_user_id() ) {
			?>
			<p>Kindly <a href="<?php echo esc_url( wp_login_url() ); ?>">login</a> first</p>
			<?php
			return;
		}
		wp_enqueue_style(
			'custom-profile-style',
			plugin_dir_url( __FILE__ ) . 'css/custom-profile.css',
			array(),
			D3V_PLUGIN_VER,
		);
		wp_enqueue_script(
			'd3v-jquery',
			'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
			array(),
			D3V_PLUGIN_VER,
			false,
		);
		wp_enqueue_script(
			'custom-profile-script',
			plugin_dir_url( __FILE__ ) . 'js/custom-profile.js',
			array(),
			D3V_PLUGIN_VER,
			true,
		);
		wp_localize_script(
			'custom-profile-script',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'update_d3v_user_profile' ),
			)
		);
		$user          = isset( $_GET['selected_user'] ) ? get_user_by( 'ID', $_GET['selected_user'] ) : wp_get_current_user();
		$profile_img   = $user->get( 'd3v_avatar' );
		$first_name    = $user->get( 'first_name' );
		$last_name     = $user->get( 'last_name' );
		$email         = $user->get( 'user_email' );
		$is_social_acc = boolval( $user->get( 'is_social_acc' ) );
		ob_start();
		?>

		<div class="d3v-container">
			<div class="user-selector-container">
			<?php
					wp_dropdown_users(
						array(
							'selected' => isset( $_GET['selected_user'] ) ? ( empty( $_GET['selected_user'] ) ? get_current_user_id() : $_GET['selected_user'] ) : get_current_user_id(),
							'show'     => 'user_email',
							'id'       => 'user-selector',
						)
					);

			?>
			</div>
			<div class="d3v-inner-container">
				<div class="d3v-outer">
					<div id="d3v-profile-image" class="d3v-inner">
						<p id="d3v-edit-text"><?php echo ( $is_social_acc ) ? 'Click to edit image' : 'Not a social acc.change img on gravatar'; ?></p>
						<input type="hidden" id="d3v-img-data" value="<?php echo ( $is_social_acc ) ? esc_attr( $profile_img ) : esc_url( get_avatar_url( $user->ID ) ); ?>" />
					</div>
				</div>
				<?php
				if ( $is_social_acc ) {
					?>
					<input type="file" id="d3v-profile-img" accept="image/*">
					<?php
				}
				?>
			</div>
			<div class="d3v-inner-container">
				<input type="text" placeholder="First Name" id="d3v-fn" value="<?php echo esc_attr( $first_name ); ?>"/>
				<input type="text" placeholder="Last Name"  id="d3v-ln" value="<?php echo esc_attr( $last_name ); ?>" />
			</div>
			<div class="d3v-inner-container">
				<input type="email" placeholder="Email Addess" id="d3v-email" value="<?php echo esc_attr( $email ); ?>"/>
			</div>
			<div class="d3v-inner-container">
				<button id="d3v-btn" >Update Details</button>
			</div>
		</div>
					<?php
					return ob_get_clean();
	}
}
