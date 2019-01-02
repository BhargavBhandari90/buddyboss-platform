<?php
/**
 * BuddyPress Admin Component Functions.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the Pages Setup admin panel.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_core_admin_pages_settings() {
	?>
    <div class="wrap">
        <h1><?php _e( 'BuddyBoss Pages', 'buddyboss' ); ?> </h1>

        <form action="" method="post">
			<?php
			settings_fields( 'bp-pages' );
			do_settings_sections( 'bp-pages' );

			printf(
				'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
				esc_attr( 'Save Settings', 'buddyboss' )
			);
			?>
        </form>
    </div>

	<?php
}

/**
 * Register page fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_register_page_fields() {
	$existing_pages = bp_core_get_directory_page_ids();
	$directory_pages = bp_core_admin_get_directory_pages();

	add_settings_section( 'bp_pages', __( 'Directories', 'buddyboss' ), 'bp_core_admin_directory_pages_description', 'bp-pages' );
	foreach ($directory_pages as $name => $label) {
		add_settings_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_pages', compact('existing_pages', 'name', 'label') );
		register_setting( 'bp-pages', $name, [] );
	}
}
add_action( 'admin_init', 'bp_core_admin_register_page_fields' );

/**
 * Register registration page fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_register_registration_page_fields() {

	add_settings_section( 'bp_registration_pages', __( 'Registration', 'buddyboss' ), 'bp_core_admin_registration_pages_description', 'bp-pages' );

	$existing_pages = bp_core_get_directory_page_ids();
	$static_pages = bp_core_admin_get_static_pages();

	foreach ($static_pages as $name => $label) {
		add_settings_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', 'bp-pages', 'bp_registration_pages', compact('existing_pages', 'name', 'label') );
		register_setting( 'bp-pages', $name, [] );
	}
}
add_action( 'admin_init', 'bp_core_admin_register_registration_page_fields' );

/**
 * Directory page settings section description
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_directory_pages_description() {
    echo wpautop( __( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddyboss' ) );
}

/**
 * Registration page settings section description
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_registration_pages_description() {
	if ( bp_get_signup_allowed() ) :
		echo wpautop( __( 'Associate WordPress Pages with the following BuddyPress Registration pages.', 'buddyboss' ) );
	else :
		if ( is_multisite() ) :
			echo wpautop(
				sprintf(
					__( 'Registration is currently disabled. To enable registration, please select either the "User accounts may be registered" or "Both sites and user accounts can be registered" option on <a href="%s">this page</a>. If "User Invites" is enabled, invited users will still be allowed to register new accounts.', 'buddyboss' ),
					network_admin_url( 'settings.php' )
				)
			);
		else :
			echo wpautop(
				sprintf(
					__( 'Registration is currently disabled. To enable registration, please click on the "Anyone can register" checkbox on <a href="%s">this page</a>. If "User Invites" is enabled, invited users will still be allowed to register new accounts.', 'buddyboss' ),
					network_admin_url( 'options-general.php' )
				)
			);
		endif;
	endif;
}

/**
 * Pages dropdowns callback
 *
 * @since BuddyBoss 1.0.0
 * @param $args
 */
function bp_admin_setting_callback_page_directory_dropdown($args) {
	extract($args);

	if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() );

	echo wp_dropdown_pages( array(
		'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
		'echo'             => false,
		'show_option_none' => __( '- None -', 'buddyboss' ),
		'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
	) );

	if ( !empty( $existing_pages[$name] ) ) :

		printf(
			'<a href="%s" class="button-secondary" target="_bp">%s</a>',
			get_permalink( $existing_pages[$name] ),
			__( 'View', 'buddyboss' )
		);
	endif;

	if ( ! bp_is_root_blog() ) restore_current_blog();
}

/**
 * Save BuddyBoss pages settings
 *
 * @since BuddyBoss 1.0.0
 * @return bool
 */
function bp_core_admin_maybe_save_pages_settings() {

	if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
		return false;
	}

	if ( 'bp-pages' != $_GET['page'] ) {
		return false;
	}

	if ( ! check_admin_referer( 'bp-pages-options' ) ) {
		return false;
    };

	if ( isset( $_POST['bp_pages'] ) ) {
		$valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

		$new_directory_pages = array();
		foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
			if ( isset( $valid_pages[ $key ] ) ) {
				$new_directory_pages[ $key ] = (int) $value;
			}
		}
		bp_core_update_directory_page_ids( $new_directory_pages );
	}

	bp_core_redirect( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages', 'updated' => 'true' ) , 'admin.php' ) ) );
}

add_action( 'bp_admin_init', 'bp_core_admin_maybe_save_pages_settings', 100 );