<?php

/**
 * User Signups Admin
 *
 * @package Plugins/User/Signups/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add menus in network and site dashboards
 *
 * @since 0.1.0
 */
function wp_user_signups_add_menu_item() {

	// Define empty array
	$hooks = array();

	// Network admin page
	if ( is_network_admin() ) {
		$hooks[] = add_submenu_page( 'users.php', esc_html__( 'Signups', 'wp-user-signups' ), esc_html__( 'Signups', 'wp-user-signups' ), 'manage_user_signups', 'user_signups',    'wp_user_signups_output_list_page' );
		$hooks[] = add_submenu_page( 'users.php', esc_html__( 'Signups', 'wp-user-signups' ), esc_html__( 'Signups', 'wp-user-signups' ), 'edit_user_signups',   'site_alias_edit', 'wp_user_signups_output_edit_page' );
		remove_submenu_page( 'users.php', 'user_signups'    );
		remove_submenu_page( 'users.php', 'site_alias_edit' );

		$hooks[] = add_submenu_page( 'users.php', esc_html__( 'Signups', 'wp-user-signups' ), esc_html__( 'Signups', 'wp-user-signups' ), 'manage_network_aliases', 'network_user_signups', 'wp_user_signups_output_network_list_page' );

	// Blog admin page
	} elseif ( is_blog_admin() ) {
		$hooks[] = add_users_page( esc_html__( 'Signups', 'wp-user-signups' ), esc_html__( 'Signups', 'wp-user-signups' ), 'manage_aliases', 'user_signups',    'wp_user_signups_output_list_page' );
		$hooks[] = add_users_page( esc_html__( 'Signups', 'wp-user-signups' ), esc_html__( 'Signups', 'wp-user-signups' ), 'edit_aliases',   'site_alias_edit', 'wp_user_signups_output_edit_page' );
		remove_submenu_page( 'users.php', 'site_alias_edit' );
	}

	// Load the list table
	foreach ( $hooks as $hook ) {
		add_action( "load-{$hook}", 'wp_user_signups_handle_actions'     );
		add_action( "load-{$hook}", 'wp_user_signups_load_list_table'    );
		add_action( "load-{$hook}", 'wp_user_signups_fix_menu_highlight' );
	}
}

/**
 * Load the list table and populate some essentials
 *
 * @since 0.1.0
 */
function wp_user_signups_load_list_table() {
	global $wp_list_table;

	// Include the list table class
	require_once dirname( __FILE__ ) . '/class-wp-user-signups-list-table.php';

	// Get site ID being requested
	$site_id = wp_user_signups_get_site_id();

	// Create a new list table object
	$wp_list_table = new WP_User_Signups_List_Table( array(
		'site_id' => $site_id
	) );

	$wp_list_table->prepare_items( $site_id );
}

/**
 * Override network files, to correct main submenu navigation highlighting
 *
 * @since 0.1.0
 *
 * @global string $parent_file
 * @global string $submenu_file
 */
function wp_user_signups_fix_menu_highlight() {
	global $parent_file, $submenu_file;

	if ( is_network_admin() ) {
		$parent_file  = 'users.php';
		$submenu_file = 'network_user_signups';
	} elseif ( is_blog_admin() ) {
		$parent_file  = 'index.php';
		$submenu_file = 'user_signups';
	}
}

/**
 * Add site list column to list
 *
 * @since 0.1.0
 *
 * @param   array  $columns  Column map of ID => title
 *
 * @return  array
 */
function wp_user_signups_add_site_list_column( $columns ) {
	$columns['signups'] = esc_html__( 'Signups', 'wp-user-signups' );
	return $columns;
}

/**
 * Output the site list column
 *
 * @since 0.1.0
 *
 * @param  string  $column   Column ID
 * @param  int     $site_id  Site ID
 */
function wp_user_signups_output_site_list_column( $column, $site_id ) {

	// Bail if not for aliases column
	if ( 'signups' !== $column ) {
		return;
	}

	// Get aliases
	$signups = WP_User_Signups::get_by_domain_and_path( $site_id );

	// Show all aliases
	if ( ! empty( $signups ) ) {
		foreach ( $signups as $signup ) {
			echo esc_html( $signup->get_domain() ) . '<br>';
		}

	// No aliases
	} else {
		esc_html_e( '&mdash;', 'wp-user-signups' );
	}
}

/**
 * Add tab to end of tabs array
 *
 * @since 0.1.0
 *
 * @param array $tabs
 * @return array
 */
function wp_user_signups_add_site_tab( $tabs = array() ) {

	// "Signupes" tab
	$tabs['user-signups'] = array(
		'label' => esc_html__( 'Signups', 'wp-user-signups' ),
		'url'   => add_query_arg( array( 'page' => 'user_signups' ), 'users.php' ),
		'cap'   => 'manage_user_signups'
	);

	// Return tabs
	return $tabs;
}

/**
 * Output the admin page header
 *
 * @since 0.1.0
 *
 * @param  int  $site_id  Site ID
 */
function wp_user_signups_output_page_header( $site_id ) {
	global $title;

	// Network
	if ( is_network_admin() ) :

		// Header
		$title = sprintf( esc_html__( 'Edit Signup: %s' ), get_blog_option( $site_id, 'blogname' ) );

		// This is copied from WordPress core (sic)
		?><div class="wrap">
			<h1 id="edit-site"><?php echo $title; ?></h1>
			<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $site_id, '/' ) ); ?>"><?php esc_html_e( 'Visit', 'wp-user-signups' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $site_id ) ); ?>"><?php esc_html_e( 'Dashboard', 'wp-user-signups' ); ?></a></p><?php

			// Admin notices
			do_action( 'wp_user_signups_admin_notices' );

			// Tabs in network admin
			network_edit_site_tabs( array(
				'blog_id'  => $site_id,
				'selected' => 'user-signups'
			) );

	// Site
	else :
		?><div class="wrap">
			<h1 id="edit-site"><?php esc_html_e( 'User Signups', 'wp-user-signups' ); ?></h1><?php

		// Admin notices
		do_action( 'wp_user_signups_admin_notices' );
	endif;
}

/**
 * Close the .wrap div
 *
 * @since 0.1.0
 */
function wp_user_signups_output_page_footer() {
	?></div><?php
}

/**
 * Handle submission of the list page
 *
 * Handles bulk actions for the list page. Redirects back to itself after
 * processing, and exits.
 *
 * @since 0.1.0
 *
 * @param  string  $action  Action to perform
 */
function wp_user_signups_handle_actions() {

	// Bail if no action
	if ( empty( $_REQUEST['action'] ) ) {
		return;
	}

	// Get action
	$action      = sanitize_key( $_REQUEST['action'] );
	$site_id     = wp_user_signups_get_site_id();
	$redirect_to = remove_query_arg( array( 'did_action', 'processed', 'signups', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_user_signups_admin_url();
	}

	// Get aliases being bulk actioned
	$processed = array();
	$signups   = ! empty( $_REQUEST['signups'] )
		? array_map( 'absint', (array) $_REQUEST['signups'] )
		: array();

	// Redirect ags
	$args = array(
		'page'       => 'user_signups',
		'id'         => $site_id,
		'did_action' => $action,
	);

	// What's the action?
	switch ( $action ) {

		// Bulk activate
		case 'activate' :
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signups::get( $signup_id );

				// Skip erroneous aliases
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Process switch
				if ( $signup->set_status( 'active' ) ) {
					$processed[] = $signup_id;
				}
			}
			break;

		// Bulk resend
		case 'resend':
			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signups::get( $signup_id );

				// Skip erroneous aliases
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Process switch
				if ( $signup->set_status( 'inactive' ) ) {
					$processed[] = $signup_id;
				}
			}
			break;

		// Single/Bulk Delete
		case 'delete':
			$args['domains'] = array();

			foreach ( $signups as $signup_id ) {
				$signup = WP_User_Signups::get( $signup_id );

				// Skip erroneous aliases
				if ( is_wp_error( $signup ) ) {
					continue;
				}

				// Signupes don't exist after we delete them, so pass the
				// domain for messages and such
				if ( $signup->delete() ) {
					$args['domains'][] = $signup->get_domain();
					$processed[] = $signup_id;
				}
			}

			break;

		// Single Edit
		case 'edit' :
			check_admin_referer( "site_alias_edit-{$site_id}" );

			// Check that the parameters are correct first
			$params = wp_user_signups_validate_signup_parameters( wp_unslash( $_POST ) );

			// Error messages
			if ( is_wp_error( $params ) ) {
				$messages[] = $params->get_error_message();

				if ( $params->get_error_code() === 'wp_user_signups_domain_invalid_chars' ) {
					$messages[] = esc_html__( 'Internationalized domain names must use the ASCII version (e.g, <code>xn--bcher-kva.example</code>)', 'wp-user-signups' );
				}

				return $messages;
			}

			$signup_id = $signups[0];
			$signup    = WP_User_Signups::get( $signup_id );

			if ( is_wp_error( $signup ) ) {
				$messages[] = $signup->get_error_message();
				return $messages;
			}

			// Update
			$result = $signup->update( $params );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$messages[] = $result->get_error_message();
				return $messages;
			}

			$processed[] = $signup_id;

			break;

		// Any other bingos
		default:
			check_admin_referer( "user_signups-bulk-{$site_id}" );
			do_action_ref_array( "aliases_bulk_action-{$action}", array( $signups, &$processed, $action ) );

			break;
	}

	// Add processed aliases to redirection
	$args['processed'] = $processed;
	$redirect_to = add_query_arg( $args, $redirect_to );

	// Redirect
	wp_safe_redirect( $redirect_to );
	exit();
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_user_signups_output_edit_page() {

	// Get site ID
	$site_id = wp_user_signups_get_site_id();

	// Edit
	if ( ! empty( $_REQUEST['signups'] ) ) {
		$signup_id = absint( $_REQUEST['signups'] );
		$signup    = WP_User_Signups::get( $signup_id );
		$action   = 'edit';

	// Add
	} else {
		$signup_id = 0;
		$signup    = null;
		$action   = 'add';
	}

	// URL
	$action_url = wp_user_signups_admin_url( array(
		'page'   => 'user_signups',
		'action' => $action
	) );

	// Add
	if ( empty( $signup ) || ! empty( $_POST['_wpnonce'] ) ) {
		$active = ! empty( $_POST['active'] );
		$domain = ! empty( $_POST['domain'] )
			? wp_unslash( $_POST['domain'] )
			: '';

	// Edit
	} else {
		$active = ( 'active' === $signup->get_status() );
		$domain = $signup->get_domain();
	}

	// Output the header, maybe with network site tabs
	wp_user_signups_output_page_header( $site_id );

	?><form method="post" action="<?php echo esc_url( $action_url ); ?>">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="blog_alias"><?php echo esc_html_x( 'Domain Name', 'field name', 'wp-user-signups' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text code" name="domain" id="blog_alias" value="<?php echo esc_attr( $domain ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php echo esc_html_x( 'Status', 'field name', 'wp-user-signups' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="status" <?php checked( $active ); ?>>

						<?php esc_html_e( 'Active', 'wp-user-signups' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<input type="hidden" name="action"  value="<?php echo esc_attr( $action   ); ?>">
		<input type="hidden" name="id"      value="<?php echo esc_attr( $site_id  ); ?>">
		<input type="hidden" name="aliases" value="<?php echo esc_attr( $signup_id ); ?>"><?php

		// Add
		if ( 'add' === $action ) {
			wp_nonce_field( "site_alias_add-{$site_id}" );
			$submit_text = esc_html__( 'Add Signup', 'wp-user-signups' );

		// Edit
		} else {
			wp_nonce_field( "site_alias_edit-{$site_id}" );
			$submit_text = esc_html__( 'Save Signup', 'wp-user-signups' );
		}

		// Submit button
		submit_button( $submit_text );

	?></form><?php

	// Footer
	wp_user_signups_output_page_footer();
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_user_signups_output_list_page() {
	global $wp_list_table;

	// Get site ID being requested
	$site_id = wp_user_signups_get_site_id();

	// Action URLs
	$form_url = wp_user_signups_admin_url( array( 'page' => 'user_signups' ) );

	// Output header, maybe with tabs
	wp_user_signups_output_page_header( $site_id ); ?>

	<div class="form-wrap">
		<form method="post" action="<?php echo esc_url( $form_url ); ?>">
			<?php $wp_list_table->display(); ?>
		</form>
	</div><?php

	// Footer
	wp_user_signups_output_page_footer();
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_user_signups_output_network_list_page() {
	global $wp_list_table;

	// Action URLs
	$form_url = wp_user_signups_admin_url( array( 'page' => 'user_signups' ) );

	?><div class="wrap">
		<h1 id="edit-site"><?php esc_html_e( 'User Signups', 'wp-user-signups' ); ?></h1>

		<div class="form-wrap">
			<form method="post" action="<?php echo esc_url( $form_url ); ?>">
				<?php $wp_list_table->display(); ?>
			</form>
		</div>
	</div><?php
}

/**
 * Output admin notices
 *
 * @since 0.1.0
 *
 * @global type $wp_list_table
 */
function wp_user_signups_output_admin_notices() {

	// Default messages array
	$messages = array();

	// Add messages for bulk actions
	if ( empty( $_REQUEST['did_action'] ) ) {
		return;
	}

	$did_action = sanitize_key( $_REQUEST['did_action'] );
	$processed  = ! empty( $_REQUEST['processed'] ) ? wp_parse_id_list( (array) $_REQUEST['processed'] ) : array();
	$processed  = array_map( 'absint', $processed );

	// Special case for single, as it's not really a "bulk" action
	if ( $processed === 1 ) {
		$bulk_messages = array(
			'activate'   => esc_html__( 'Activated %s',   'wp-user-signups' ),
			'deactivate' => esc_html__( 'Deactivated %s', 'wp-user-signups' ),
			'delete'     => esc_html__( 'Deleted %s',     'wp-user-signups' ),
			'add'        => esc_html__( 'Added %s',       'wp-user-signups' ),
			'edit'       => esc_html__( 'Updated %s',     'wp-user-signups' ),
		);

		if ( 'delete' === $did_action ) {
			$domain = ! empty( $_REQUEST['domains'] )
				? $_REQUEST['domains'][0]
				: array();
		} else {
			$signup  = WP_User_Signups::get( $processed[0] );
			$domain = $signup->get_domain();
		}

		$placeholder = '<code>' . esc_html( $domain ) . '</code>';

	// Note: we still use _n for languages which have special cases on
	// e.g. 3, 5, 10, etc
	} else {
		$count         = count( $processed );
		$placeholder   = number_format_i18n( $count );
		$bulk_messages = array(
			'activate'   => _n( '%s alias activated.',   '%s aliases activated.',   $count, 'wp-user-signups' ),
			'deactivate' => _n( '%s alias deactivated.', '%s aliases deactivated.', $count, 'wp-user-signups' ),
			'delete'     => _n( '%s alias deleted.',     '%s aliases deleted.',     $count, 'wp-user-signups' ),
			'add'        => _n( '%s alias added.',       '%s aliases added.',       $count, 'wp-user-signups' ),
			'edit'       => _n( '%s alias updated.',     '%s aliases updated.',     $count, 'wp-user-signups' )
		);
	}

	// Filter bulk messages, allowing for custom ones
	$bulk_messages = apply_filters( 'wp_user_signups_bulk_messages', $bulk_messages, $processed );

	// Insert the placeholder
	if ( ! empty( $bulk_messages[ $did_action ] ) ) {
		$messages[] = sprintf( $bulk_messages[ $did_action ], $placeholder );
	}

	// Bail if no messages
	if ( empty( $messages ) ) {
		return;
	}

	// Start a buffer
	ob_start();

	?><div id="message" class="notice notice-success"><p><?php echo implode( '</p><p>', $messages ); ?></p></div><?php

	// Output the buffer
	ob_end_flush();
}